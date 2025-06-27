# Imagen base con Apache + PHP 8.2
FROM php:8.2-apache

# Crear carpetas necesarias
RUN mkdir -p /var/www/html/db /var/www/html/teveo && chmod -R 775 /var/www/html/teveo

# Copiar todo el contenido del proyecto
COPY public/ /var/www/html/
COPY db/ /var/www/html/db/
COPY teveo/ /var/www/html/teveo/

# Copiar archivos raíz como index.php, auth.php, etc.
COPY index.php /var/www/html/
COPY auth.php /var/www/html/

# Asegurar permisos correctos
RUN chmod -R 755 /var/www/html

# Exponer el puerto estándar de Apache
EXPOSE 80
