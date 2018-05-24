FROM alpine
RUN apk add --update mysql-client bash openssh-client && rm -rf /var/cache/apk/*
COPY dump.sh /
ENTRYPOINT ["/dump.sh"]
