FROM nginx:1.19-alpine

RUN apk add --no-cache curl

# Set up HEALTHCHECK
HEALTHCHECK --interval=10s --timeout=3s --retries=3 \
    CMD \
    curl -f http://127.0.0.1:80/php-fpm/ping || exit 1

EXPOSE 80

# main()
COPY data/nginx.conf /etc/nginx/nginx.conf
COPY data/website.conf /etc/nginx/conf.d/default.conf
