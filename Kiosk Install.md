sudo apt update

sudo apt install --no-install-recommends xorg openbox chromium-browser

sudo apt install xterm

sudo usermod -aG tty iceburg

sudo usermod -aG video iceburg

sudo apt update

sudo apt install x11vnc

mkdir -p /home/iceburg/kiosk

nano /home/iceburg/kiosk/start.sh

boot script /home/iceburg/kiosk/start.sh 

```
#!/bin/bash

export DISPLAY=:0
export XAUTHORITY=/home/iceburg/.Xauthority

exec >/tmp/kiosk.log 2>&1
set -x

# Wait for X
until xset q >/dev/null 2>&1; do
    sleep 1
done

xset s off
xset s noblank
xset -dpms

openbox-session &
sleep 2

# Wait for touchscreen
for t in {1..30}; do
    TOUCH_ID=$(xinput list --id-only "Goodix Capacitive TouchScreen" 2>/dev/null)
    [ -n "$TOUCH_ID" ] && break
    sleep 1
done

if [ -n "$TOUCH_ID" ]; then
    xinput set-prop "$TOUCH_ID" \
      "Coordinate Transformation Matrix" \
      0 1 0 -1 0 1 0 0 1
fi

x11vnc -display :0 -auth guess -forever -shared &

exec chromium-browser \
  --kiosk \
  --no-first-run \
  --disable-infobars \
  --no-sandbox \
  --disable-gpu \
  http://localhost
```

chmod +x /home/iceburg/kiosk/start.sh
sudo nano /etc/systemd/system/kiosk.service

kiosk.service
```
[Unit]
Description=Kiosk
After=systemd-logind.service

[Service]
User=iceburg
PAMName=login
TTYPath=/dev/tty1
TTYReset=yes
TTYVHangup=yes
TTYVTDisallocate=yes
StandardInput=tty
StandardOutput=journal
ExecStart=/usr/bin/startx /home/iceburg/kiosk/start.sh
Restart=always
KillMode=control-group
SendSIGKILL=yes
TimeoutStopSec=5


[Install]
WantedBy=multi-user.target

```
sudo systemctl daemon-reload

sudo systemctl enable kiosk

sudo nano /etc/default/grub

```
GRUB_CMDLINE_LINUX_DEFAULT="quiet splash fbcon=rotate:1 video=efifb:rotate=1"
```

sudo update-grub

sudo nano /etc/X11/xorg.conf.d/10-monitor.conf

```
Section "Monitor"
    Identifier "Monitor0"
    Option "Rotate" "right"
EndSection

Section "Device"
    Identifier "Device0"
    Driver "modesetting"
EndSection

Section "Screen"
    Identifier "Screen0"
    Device "Device0"
    Monitor "Monitor0"
EndSection
```

sudo apt -y install php8.3 php8.3-{common,cli,gd,mysql,mbstring,bcmath,xml,fpm,curl,zip} mariadb-server nginx tar unzip

sudo mysql 

```
CREATE USER 'iceburg'@'%' IDENTIFIED BY 'jfu5itjfitiejit5kfsfdgfdge8t43w';
CREATE DATABASE iceburg;
GRANT ALL PRIVILEGES ON iceburg.* TO 'iceburg'@'%' WITH GRANT OPTION;

CREATE USER 'iceburg'@'localhost' IDENTIFIED BY 'jfu5itjfitiejit5kfsfdgfdge8t43w';
GRANT ALL PRIVILEGES ON iceburg.* TO 'iceburg'@'localhost' WITH GRANT OPTION;
```

sudo mysql  iceburg < iceburg.sql

sudo mkdir /var/www/iceburg

sudo chmod -R 777 /var/www/iceburg

Upload /server Side Files/Server PHP/ Here

sudo mkdir -p /etc/nginx/ssl

sudo openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \ -keyout /etc/nginx/ssl/iceburg.key \ -out /etc/nginx/ssl/iceburg.crt

sudo nano /etc/nginx/sites-enabled/iceburg.config

```
server {
    # Replace the example <domain> with your domain name or IP address
    listen 80;
    server_name iceburg;

    root /var/www/iceburg/;
    index index.html index.htm index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    access_log off;
    error_log  /var/log/nginx/iceburg.log error;

    # allow larger file uploads and longer script runtimes
    client_max_body_size 100m;
    client_body_timeout 120s;

    sendfile off;

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size=100M";
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTP_PROXY "";
        fastcgi_intercept_errors off;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }

}
server {
    listen 443 ssl;
    server_name iceburg;

    ssl_certificate /etc/nginx/ssl/iceburg.crt;
    ssl_certificate_key /etc/nginx/ssl/iceburg.key;

    error_log  /var/log/nginx/iceburg.log error;

    root /var/www/iceburg/;
    index index.html index.htm index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
    client_max_body_size 100M;
    client_body_timeout 120s;
}
```

sudo rm -rf /etc/nginx/sites-enabled/default

sudo systemctl restart nginx

sudo reboot
