#!/usr/bin/env bash
# ============================================================
# Mahru — فهرست IP لبه‌های CDN آروان (۲۰۲۶-۱۰-۲۴)
#
#   bash deploy/single/arvan-ips.sh               ← TRUSTED_PROXIES در .env.server را از فهرست رسمی آروان پر می‌کند
#   sudo bash deploy/single/arvan-ips.sh --firewall [IP_شما]
#        ← علاوه بر آن، پورت‌های ۸۰ و ۴۴۳ کانتینر را فقط برای لبه‌های آروان (و IP اختیاری شما) باز می‌گذارد (زنجیره‌ی DOCKER-USER؛
#          ufw روی پورت‌های Docker اثری ندارد). SSH (۲۲) دست نمی‌خورد. فقط وقتی فایروال CDN پنل آروان را ندارید.
#   sudo bash deploy/single/arvan-ips.sh --no-cdn
#        ← حالت «بدون CDN» (بسته‌ی مجوز 5د): TRUSTED_PROXIES خالی (IP مستقیم واقعی است)، قاعده‌ی DOCKER-USER برداشته می‌شود و
#          ۸۰/۴۴۳ برای همه باز است. HTTPS را خود کانتینر می‌دهد (گواهی در ./tls، ARVAN_SERVER.md بخش ۸). اینترنت نمی‌خواهد.
#
# از پوشه‌ی /srv/mahru اجرا شود. منبع: ARVAN_IPS_URL (پیش‌فرض https://www.arvancloud.ir/en/ips.txt) — اگر باز نشد،
# فهرست را از پنل/پشتیبانی آروان بگیرید و خط‌به‌خط در فایل arvan-ips.txt کنار همین پوشه بگذارید (اسکریپت آن را برمی‌دارد).
# بعد از تغییر TRUSTED_PROXIES:  docker compose -f docker-compose.server.yml up -d
# قاعده‌های --firewall با ری‌استارت سرور پاک می‌شوند: crontab ‏«@reboot sleep 60 && cd /srv/mahru && bash deploy/single/arvan-ips.sh --firewall …»
# ============================================================
set -euo pipefail

DIR="$(pwd)"
MODE="${1:-}"
URL="${ARVAN_IPS_URL:-https://www.arvancloud.ir/en/ips.txt}"
LOCAL_LIST="$DIR/arvan-ips.txt"

die() { echo "❌ $*" >&2; exit 1; }
[[ -f "$DIR/.env.server" ]] || die ".env.server اینجا نیست — از /srv/mahru اجرا کنید"

set_trusted() {
    if grep -qE '^TRUSTED_PROXIES=' "$DIR/.env.server"; then
        sed -i "s#^TRUSTED_PROXIES=.*#TRUSTED_PROXIES=$1#" "$DIR/.env.server"
    else
        printf 'TRUSTED_PROXIES=%s\n' "$1" >> "$DIR/.env.server"
    fi
}

remove_firewall() {
    while iptables -S DOCKER-USER 2>/dev/null | grep -q -- '-j MAHRU_ARVAN'; do
        iptables -S DOCKER-USER | grep -- '-j MAHRU_ARVAN' | head -n 1 | sed 's/^-A /-D /' | xargs iptables
    done
    iptables -F MAHRU_ARVAN 2>/dev/null || true
    iptables -X MAHRU_ARVAN 2>/dev/null || true
}

if [[ "$MODE" == "--no-cdn" ]]; then
    [[ "$(id -u)" == "0" ]] || die "--no-cdn با sudo/root"
    set_trusted ""
    remove_firewall
    echo "✅ حالت بدون CDN: TRUSTED_PROXIES خالی (IP مستقیم واقعی است)؛ ۸۰/۴۴۳ برای همه باز است."
    echo "   اگر crontab خط «@reboot … arvan-ips.sh --firewall» دارد، پاکش کنید (crontab -e)."
    echo "   گواهی HTTPS در /srv/mahru/tls باید باشد (ARVAN_SERVER.md بخش ۸)؛ بعد: docker compose -f docker-compose.server.yml up -d"
    exit 0
fi

# فقط سطرهایی که واقعاً CIDR یا IP هستند (HTML/خطای صفحه هرگز وارد TRUSTED_PROXIES نشود)
parse_v4() { printf '%s\n' "$1" | tr -d '\r' | grep -Eo '^[[:space:]]*[0-9]{1,3}(\.[0-9]{1,3}){3}(/[0-9]{1,2})?[[:space:]]*$' | tr -d ' \t' || true; }
parse_v6() { printf '%s\n' "$1" | tr -d '\r' | grep -Eo '^[[:space:]]*[0-9a-fA-F:]*:[0-9a-fA-F:]*(/[0-9]{1,3})?[[:space:]]*$' | tr -d ' \t' || true; }

V4=""; V6=""
if RAW="$(curl -fsS --max-time 20 "$URL" 2>/dev/null)"; then
    V4="$(parse_v4 "$RAW")"; V6="$(parse_v6 "$RAW")"
    [[ -n "$V4$V6" ]] && echo "📥 فهرست از $URL"
fi
if [[ -z "$V4$V6" && -s "$LOCAL_LIST" ]]; then
    RAW="$(cat "$LOCAL_LIST")"
    V4="$(parse_v4 "$RAW")"; V6="$(parse_v6 "$RAW")"
    [[ -n "$V4$V6" ]] && echo "📄 فهرست از $LOCAL_LIST"
fi
[[ -n "$V4$V6" ]] || die "هیچ رنج IP معتبری نیامد — نه از $URL نه از $LOCAL_LIST (فهرست را از پنل/پشتیبانی آروان بگیرید)"
COUNT="$(printf '%s\n%s\n' "$V4" "$V6" | grep -c . || true)"

LIST="$(printf '%s\n%s\n' "$V4" "$V6" | grep . | paste -sd, -)"
set_trusted "$LIST"
echo "✅ TRUSTED_PROXIES: $COUNT رنج — اعمال: docker compose -f docker-compose.server.yml up -d"

if [[ "$MODE" == "--firewall" ]]; then
    [[ "$(id -u)" == "0" ]] || die "--firewall با sudo/root"
    ADMIN_IP="${2:-}"
    IFACE="$(ip route show default 2>/dev/null | awk '{print $5; exit}' || true)"
    [[ -n "$IFACE" ]] || IFACE="$(awk '$2 == "00000000" {print $1; exit}' /proc/net/route)"
    [[ -n "$IFACE" ]] || die "کارت شبکه‌ی پیش‌فرض پیدا نشد"

    iptables -N MAHRU_ARVAN 2>/dev/null || iptables -F MAHRU_ARVAN
    for cidr in $V4; do iptables -A MAHRU_ARVAN -s "$cidr" -j RETURN; done
    [[ -n "$ADMIN_IP" ]] && iptables -A MAHRU_ARVAN -s "$ADMIN_IP" -j RETURN
    iptables -A MAHRU_ARVAN -j DROP

    # ۸۰ و ۴۴۳ (HTTPS روی خود مبدا، بسته‌ی مجوز 5د)
    for port in 80 443; do
        RULE=(-i "$IFACE" -p tcp -m conntrack --ctorigdstport "$port" --ctdir ORIGINAL -j MAHRU_ARVAN)
        iptables -C DOCKER-USER "${RULE[@]}" 2>/dev/null || iptables -I DOCKER-USER "${RULE[@]}"
    done
    echo "🔒 پورت‌های ۸۰ و ۴۴۳ روی $IFACE فقط برای $(printf '%s\n' "$V4" | grep -c .) رنج آروان${ADMIN_IP:+ و $ADMIN_IP} باز است."
    echo "   آزمون از بیرون: curl -m 5 -sI http://<IP سرور>/up  ← باید timeout شود"
fi
