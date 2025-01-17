server {
    server_name avaxtars.com;
    root /var/www/avaxtars.com/public/;

    add_header 'X-Http-Secure' 1;

    index index.html index.htm index.php;

    charset utf-8;

    location /auth.json {
	deny all;

    }

    location / {
	add_header 'Access-Control-Allow-Origin' '*';
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ /\.ht {
        deny all;
    }

    location = /favicon.ico { access_log off; log_not_found off; }

    location /health-check/ {
      rewrite ^/health-check/(?<domain>[a-zA-Z0-9\.]+) /  break;
      proxy_set_header "Host" $domain;
      proxy_pass http://127.0.0.1:8080;
    }

    access_log /var/log/nginx/access.log;
    error_log  /var/log/nginx/avaxtars.com.error.log error;
    server_tokens off;
    sendfile off;

    client_max_body_size 100m;

    location ~ \.php$ {
	add_header 'Access-Control-Allow-Origin' '*';
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $document_root;
        fastcgi_intercept_errors off;

        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
	fastcgi_read_timeout 60000;
    }

    location ~ /\.well-known\/ {
        allow all;
    }

    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ \.py$ {
        deny all;
        return 404;
    }

    location /library/ {
        deny all;
        return 404;
    }

    location /tests/ {
        deny all;
        return 404;
    }

    location /var/ {
        deny all;
        return 404;
    }

    location /vendors/ {
        deny all;
        return 404;
    }

    gzip  on;
    gzip_vary on;
    gzip_min_length 10240;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;
    gzip_disable "MSIE [1-6]\.";
    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/avaxtars.com-0001/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/avaxtars.com-0001/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot



}
server {
    if ($host = avaxtars.com) {
        return 301 https://$host$request_uri;
    } # managed by Certbot


    server_name avaxtars.com;

    listen 80 default_server;
    return 404; # managed by Certbot


}

server {
    server_name www.avaxtars.com;
    listen 80;
    return 301 $scheme://avaxtars.com$request_uri;
}
