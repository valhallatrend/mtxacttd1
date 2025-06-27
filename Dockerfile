# Imagen base con Apache + PHP 8.2
FROM php:8.2-apache

# Crear carpetas necesarias
RUN mkdir -p /var/www/html/db /var/www/html/teveo && chmod -R 775 /var/www/html/teveo

# Copiar todo el contenido del proyecto

COPY db/ /var/www/html/db/
COPY teveo/ /var/www/html/teveo/



# Asegurar permisos correctos
RUN chmod -R 755 /var/www/html

# Exponer el puerto estándar de Apache
EXPOSE 80


RUN mkdir -p /app/public /app/db
COPY public/ /app/public/
COPY db/ /app/db/
COPY teveo/ /app/teveo/
RUN mkdir -p /app/teveo && chmod -R 775 /app/teveo

WORKDIR /app/public
#CMD ["php", "-S", "0.0.0.0:8080"]
# Copiar archivos del proyecto
COPY . /var/www/html/

# Dar permisos correctos a todos los archivos PHP
RUN chmod -R 755 /var/www/html


# Copiar solo el contenido de Public al directorio público de Apache
COPY public/ /var/www/html/

# Copiar base de datos
COPY db/ /var/www/html/db/
