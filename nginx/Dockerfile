FROM nginx:stable-alpine
RUN rm /etc/nginx/conf.d/default.conf
RUN mkdir -p /var/log/nginx/healthd/ && chown nginx:nginx /var/log/nginx/healthd/
COPY files/ /etc/nginx/