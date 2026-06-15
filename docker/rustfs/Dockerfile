FROM rustfs/rustfs:1.0.0-beta.8@sha256:fa19210ac4697c79d7ccca1ec9b0eb91aebacc6691991ffb14014bb3c67e6cc3

USER root

RUN apk upgrade --no-cache \
    && apk del --no-cache curl

USER rustfs
