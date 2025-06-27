FROM php:8.2-apache

# Copiar solo lo necesario al servidor Apache
COPY Public/ /var/www/html/

# Copiar la base de datos al mismo contenedor (dentro del path del proyecto)
COPY db/ /var/www/html/db/

# Dar permisos correctos
RUN chmod -R 755 /var/www/html