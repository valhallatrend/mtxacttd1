FROM php:8.2-cli

RUN mkdir -p /app/public /app/db
COPY public/ /app/public/
COPY db/ /app/db/

WORKDIR /app/public
CMD ["php", "-S", "0.0.0.0:8080"]
# Copiar archivos del proyecto
COPY . /var/www/html/

# Dar permisos correctos a todos los archivos PHP
RUN chmod -R 755 /var/www/html