sudo apt update

sudo apt install --no-install-recommends xserver-xorg x11-xserver-utils xinit openbox chromium-browser unclutter watchdog -y
  
sudo apt install x11vnc

sudo apt install chromium -y

sudo mkdir -p /etc/systemd/system/getty@tty1.service.d

sudo nano /etc/systemd/system/getty@tty1.service.d/override.conf

```
[Service]
ExecStart=
ExecStart=-/sbin/agetty --autologin iceburg --noclear %I $TERM
```

sudo systemctl daemon-reload

nano ~/kiosk.sh
```
#!/bin/bash

xset -dpms
xset s off
xset s noblank

unclutter -idle 0.5 &
x11vnc -display :0 -auth guess -forever -shared &

while true; do
    chromium-browser \
        --kiosk \
        --noerrdialogs \
        --disable-infobars \
        --disable-session-crashed-bubble \
        --check-for-update-interval=31536000 \
        --disable-pinch \
        --overscroll-history-navigation=0 \
        --autoplay-policy=no-user-gesture-required \
        http://localhost

    echo "Chromium crashed. Restarting in 5 seconds..."
    sleep 5
done

```
chmod +x ~/kiosk.sh

nano ~/.bash_profile

```
if [[ -z $DISPLAY ]] && [[ $(tty) = /dev/tty1 ]]; then
  startx
fi
```

mkdir -p ~/.config/openbox/

nano ~/.config/openbox/autostart

```
~/kiosk.sh &
```

nano ~/.xinitrc

```
exec openbox-session
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

sudo openssl req -x509 -nodes -days 3650 -newkey rsa:2048  -keyout /etc/nginx/ssl/iceburg.key  -out /etc/nginx/ssl/iceburg.crt

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
