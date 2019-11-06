FROM fedora:30

ARG MSGPACK_VERSION=2.0.3
ARG ASYNC_VERSION=8c1da46
ARG SWOOLE_VERSION=4.4.12
ARG PARALLEL_VERSION=1.1.3

RUN dnf install -y php-devel php-zts php-opcache php-composer-installers git make

RUN git clone --branch=php7.3 --depth=1 https://github.com/SonSergei/tarantool-php.git /usr/src/php-tarantool && \
    cd /usr/src/php-tarantool && \
    phpize && ./configure --with-php-config=/usr/bin/php-config && make && make install && \
    make clean && \
    phpize && ./configure --with-php-config=/usr/bin/zts-php-config && make && make install

RUN git clone --branch=msgpack-${MSGPACK_VERSION} --depth=1 https://github.com/msgpack/msgpack-php.git /usr/src/ext-msgpack && \
    cd /usr/src/ext-msgpack && \
    phpize && ./configure --with-php-config=/usr/bin/php-config && make && make install && \
    make clean && \
    phpize && ./configure --with-php-config=/usr/bin/zts-php-config && make && make install

RUN git clone https://github.com/dreamsxin/ext-async.git /usr/src/ext-async && \
    cd /usr/src/ext-async && \
    git checkout ${ASYNC_VERSION} && \
    phpize && ./configure --with-php-config=/usr/bin/php-config && make && make install && \
    make clean && \
    phpize && ./configure --with-php-config=/usr/bin/zts-php-config && make && make install

RUN git clone --branch=v${SWOOLE_VERSION} --depth=1 https://github.com/swoole/swoole-src.git /usr/src/ext-swoole && \
    cd /usr/src/ext-swoole && \
    phpize && ./configure --with-php-config=/usr/bin/php-config && make && make install && \
    make clean && \
    phpize && ./configure --with-php-config=/usr/bin/zts-php-config && make && make install

RUN git clone --branch=v${PARALLEL_VERSION} --depth=1 https://github.com/krakjoe/parallel.git /usr/src/ext-parallel && \
    cd /usr/src/ext-parallel && \
    phpize && ./configure --with-php-config=/usr/bin/zts-php-config && make && make install
