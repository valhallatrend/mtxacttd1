FROM php:8.2-cli

RUN mkdir -p /app/public /app/db
COPY public/ /app/public/
COPY db/ /app/db/

WORKDIR /app/public
CMD ["php", "-S", "0.0.0.0:8080"]
