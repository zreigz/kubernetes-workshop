FROM alpine
MAINTAINER Lukasz Zajaczkowski "zreigz@gmail.com"

WORKDIR /home
RUN apk add --update python py-pip
COPY start.sh start.sh
EXPOSE  8080
ENTRYPOINT ["sh", "start.sh"]


