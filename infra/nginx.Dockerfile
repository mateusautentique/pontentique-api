FROM nginx:latest

COPY ./infra/nginx.conf /etc/nginx/conf.d/default.conf