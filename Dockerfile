FROM node:20-alpine AS frontend-build

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

COPY index.html tsconfig.json vite.config.ts ./
COPY src ./src
COPY public ./public
ARG VITE_API_BASE=/api
ENV VITE_API_BASE=${VITE_API_BASE}
RUN npm run build

FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY --from=frontend-build /app/dist/ /var/www/html/
RUN echo "=== CHECKING FRONTEND FILES ===" \
    && find /var/www/html -maxdepth 2 -type f -printf '%p (%s bytes)\n' | sort
COPY api/ /var/www/html/api/
COPY database/demo-catalog.json /var/www/html/database/demo-catalog.json
COPY docker/entrypoint.sh /usr/local/bin/ameba-entrypoint

RUN chmod +x /usr/local/bin/ameba-entrypoint \
    && mkdir -p /var/lib/ameba \
    && chown -R www-data:www-data /var/lib/ameba

ENV DB_PATH=/var/lib/ameba/ameba.sqlite
EXPOSE 80
ENTRYPOINT ["/usr/local/bin/ameba-entrypoint"]
