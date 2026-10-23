#!/bin/sh
# تا MariaDB داخل همین کانتینر جواب ندهد صبر کن، بعد دستور را با کاربر www اجرا کن (workerها و scheduler در supervisord)
i=0
until mariadb-admin --socket=/run/mysqld/mysqld.sock ping --silent >/dev/null 2>&1; do
    i=$((i + 1))
    [ "$i" -ge 120 ] && echo "wait-db: MariaDB not ready" >&2 && exit 1
    sleep 1
done
cd /var/www/html
exec su-exec www "$@"
