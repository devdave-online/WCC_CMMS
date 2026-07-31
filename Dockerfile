# WCC CMMS — application image (PHP 8.2 + Apache).
# Built and run by docker-compose.yml; you rarely invoke this directly.
FROM php:8.2-apache

# Extensions the app needs, plus Apache rewrite so .htaccess rules apply.
RUN docker-php-ext-install pdo_mysql mysqli \
 && a2enmod rewrite

# date.timezone MUST be set: WCC promotes PHP warnings to fatal errors, and an
# unset timezone makes every date() call warn — which would break every page.
RUN { \
      echo 'date.timezone = UTC'; \
      echo 'upload_max_filesize = 20M'; \
      echo 'post_max_size = 22M'; \
      echo 'memory_limit = 256M'; \
      echo 'display_errors = Off'; \
    } > /usr/local/etc/php/conf.d/wcc.ini

# Let the app's .htaccess files take effect (webroot only — root stays locked).
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
      > /etc/apache2/conf-enabled/wcc.conf

# Application code. Webroot = repo root, exactly like htdocs under XAMPP.
# .dockerignore keeps backups, the .rar, the DB snapshot and dev artefacts out.
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
