FROM mariadb:latest

# Initial SQL setup
COPY migrations.sql /docker-entrypoint-initdb.d/01-migrations.sql
COPY user_roles.sql /docker-entrypoint-initdb.d/02-user_roles.sql

VOLUME /var/lib/mysql