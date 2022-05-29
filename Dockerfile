FROM ubuntu:21.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP
ARG NODE_VERSION=16

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
	&& apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python2 \
	&& mkdir -p ~/.gnupg \
	&& chmod 600 ~/.gnupg \
	&& echo "disable-ipv6" >> ~/.gnupg/dirmngr.conf \
	&& apt-key adv --homedir ~/.gnupg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys E5267A6C \
	&& apt-key adv --homedir ~/.gnupg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C300EE8C \
	&& echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu hirsute main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
	&& apt-get update \
	&& apt-get install -y php7.3-cli php7.3-dev \
	php7.3-pgsql php7.3-sqlite3 php7.3-gd \
	php7.3-curl php7.3-memcached \
	php7.3-imap  php7.3-mbstring \
	php7.3-xml php7.3-zip php7.3-bcmath php7.3-soap \
	php7.3-intl php7.3-readline php7.3-pcov \
	php7.3-msgpack php7.3-igbinary php7.3-ldap \
	php7.3-redis php7.3-xdebug \
	&& php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
	&& curl -sL https://deb.nodesource.com/setup_$NODE_VERSION.x | bash - \
	&& apt-get install -y nodejs \
	&& npm install -g npm \
	&& curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
	&& echo "deb https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list \
	&& apt-get update \
	&& apt-get install -y yarn \
	&& apt-get install -y mysql-client \
	&& apt-get -y autoremove \
	&& apt-get clean
RUN apt-get install -y php7.3-mysql
RUN setcap "cap_net_bind_service=+ep" /usr/bin/php7.3

RUN useradd -ms /bin/bash --no-user-group  -u 1337 sail

COPY ./scripts/start-container.sh /usr/local/bin/start-container
COPY ./scripts/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./scripts/php.ini /etc/php/7.3/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 8000

ENTRYPOINT ["start-container"]
