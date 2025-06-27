# Usa una imagen base con PHP y Apache
FROM php:8.1-apache

# Habilita mod_rewrite si es necesario
RUN a2enmod rewrite

# Instala la extensión SQLite para PHP
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Crear directorios necesarios con permisos apropiados
RUN mkdir -p /app/db /app/teveo /app/public \
    && chown -R www-data:www-data /app \
    && chmod -R 755 /app \
    && chmod -R 775 /app/teveo /app/db

# Copia todos los archivos del proyecto al contenedor
COPY . /app/

# Copia los archivos públicos al directorio de Apache
COPY public/ /var/www/html/

# Asegura que los directorios tengan los permisos correctos después de copiar
RUN chown -R www-data:www-data /app /var/www/html \
    && chmod -R 755 /app \
    && chmod -R 775 /app/teveo /app/db \
    && chmod 644 /app/db/licencias.sqlite 2>/dev/null || true

# Configura el DocumentRoot de Apache (opcional, si quieres cambiar la raíz)
# RUN sed -i 's|/var/www/html|/app/public|g' /etc/apache2/sites-available/000-default.conf

# Expone el puerto 80
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]