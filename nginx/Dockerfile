FROM nginx:1.22.1-alpine


RUN apk add --no-cache gettext

COPY ./default.conf /etc/nginx/default.conf
COPY ./entrypoint.sh /entrypoint.sh

USER root
RUN chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
