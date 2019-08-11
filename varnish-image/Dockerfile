FROM debian:stretch-slim

ENV VARNISH_VERSION 5.2.0-1~stretch

RUN apt-get update && apt-get install -y curl
RUN curl -s https://packagecloud.io/install/repositories/varnishcache/varnish52/script.deb.sh | bash
RUN apt-get install -y --no-install-recommends varnish=$VARNISH_VERSION

WORKDIR /etc/varnish

COPY docker-varnish-entrypoint /usr/local/bin/
ENTRYPOINT ["docker-varnish-entrypoint"]

EXPOSE 80
CMD ["varnishd", "-F", "-f", "/etc/varnish/default.vcl"]