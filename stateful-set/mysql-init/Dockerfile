from alpine

env SCHEMA_MAJOR_REV=1 \
  SCHEMA_MINOR_REV=5 \
  SCHEMA_PATCH_REV=4

run apk add --update --no-cache mysql-client pwgen py2-jinja2 coreutils
copy init.sh template.py disable-remote-root.sql /
copy mysql-init.d /mysql-init.d/


cmd ["/init.sh"]
