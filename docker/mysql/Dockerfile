FROM mariadb:latest

# Note: Schema migrations moved to docker/php/entrypoint.sh
# to run AFTER WordPress is installed.
# user_roles.sql also disabled - it runs before WordPress tables exist.
# Role updates must happen after WP install, not at DB init.

VOLUME /var/lib/mysql