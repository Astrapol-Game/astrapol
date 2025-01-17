server {
    listen 80;
    server_name test.astrapol.com www.test.astrapol.com;

    root /var/www/test.astrapol.com/public;
    index test.html;

    access_log /var/log/nginx/test.astrapol.access.log;
    error_log /var/log/nginx/test.astrapol.error.log;

    location / {
        try_files $uri $uri/ =404;
    }

    # app dizinindeki PHP dosyalarına erişim sağla
    location ~ ^/app/.*\.php$ {
        root /var/www/test.astrapol.com;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # PHP sürümünüze göre değiştirin
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~* ^/(css|image)/ {
        root /var/www/test.astrapol.com/public;
    }

    # Güvenlik ayarları
    location ~ /\.ht {
        deny all;
    }

    location ^~ /app/config/ {
        deny all;
        return 403;
    }
}
