# PHP آدرس اصلی اتصال را می‌گیرد، نه آدرسی که real_ip ساخته: TrustProxies خود Laravel با همان TRUSTED_PROXIES تصمیم
# می‌گیرد و X-Forwarded-Proto لبه‌ی مورد اعتماد را همچنان می‌بیند (وگرنه پشت CDN همه‌ی لینک‌ها http می‌شد).
fastcgi_param REMOTE_ADDR $realip_remote_addr;
