
FROM composer:2.5 as builder

WORKDIR /app/
RUN mkdir web
COPY ./web/composer.* /app/web/

RUN cd web && composer install --no-dev --prefer-dist --ignore-platform-reqs

COPY . /app

FROM node:20 as buildernpm

WORKDIR /app/

COPY --from=builder /app /app

RUN npm install
RUN npm run build:templates
RUN npm run build:css
RUN npm run prebuild:js
#RUN npm run build:js
RUN npm run builddev:js
RUN npm run dist-clean
