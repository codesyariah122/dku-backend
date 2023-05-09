# Development on windows
***Adding windows vhost***
```
C:\Windows\System32\drivers\etc\hosts
```  
Edit on hosts file : 
```
127.0.0.1           codot.com
```  


### If using laradock
***Adding new line for mysql db***
on .env file laradock directory
```DB_HOST=mysql```  

**Setup nginx vhost**  
```
# cp default.conf your-vhost.conf
```  
**Edit nginx vhost file**
*For example: *
```
#server {
#    listen 80;
#    server_name laravel.com.co;
#    return 301 https://laravel.com.co$request_uri;
#}

server {

    listen 80;
    listen [::]:80;

    # For https
    # listen 443 ssl;
    # listen [::]:443 ssl ipv6only=on;
    # ssl_certificate /etc/nginx/ssl/default.crt;
    # ssl_certificate_key /etc/nginx/ssl/default.key;

    server_name codot.com;
    root /var/www/cod-ot/public;
    index index.php index.html index.htm;

    location / {
         try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        try_files $uri /index.php =404;
        fastcgi_pass php-upstream;
        fastcgi_index index.php;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        #fixes timeouts
        fastcgi_read_timeout 600;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location /.well-known/acme-challenge/ {
        root /var/www/letsencrypt/;
        log_not_found off;
    }

    error_log /var/log/nginx/laravel_error.log;
    access_log /var/log/nginx/laravel_access.log;
}

```  

### If Using ubuntu / debian base
**Edit hostname**

```
127.0.0.1       codot.com
```

Restart system : ```service network-manager restart```
Restart nginx laradock : ```docker-compose restart nginx```

### Development laravel project inside laradock
***Using workspace***

Start laradock : ```docker-compose up -d nginx mysql phpmyadmin workspace```
Running workspace : ```docker-compose exec workspace bash``` 

***Access mysql***  
```
docker-compose exec mysql bash

bash-4.4# mysql -u root -proot
```  

***Accessing phpmyadmin workspace***
```
docker-compose exec phpmyadmin bash
```