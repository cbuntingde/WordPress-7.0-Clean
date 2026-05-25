#!/bin/bash
set -e

echo "Waiting for database..."
until php -r "try { \$c = @mysqli_connect('wp_mariadb','root','root'); if(\$c) mysqli_close(\$c); echo \"ok\"; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
    sleep 2
done
echo "Database is ready!"

# Fix wp-content ownership
if [ -d /var/www/html/wp-content ]; then
    chown -R www-data:www-data /var/www/html/wp-content
    chmod -R 755 /var/www/html/wp-content
fi

# Wait for WordPress to be ready
while [ ! -f /var/www/html/wp-load.php ]; do
    sleep 1
done

# Run WordPress schema migrations (only if tables exist)
if [ -f /var/www/html/wp-config.php ]; then
    echo "Running WordPress schema migrations..."
    php -r "
    \$c = mysqli_connect('wp_mariadb','root','root','wordpress');
    // Check if WordPress tables exist
    \$result = mysqli_query(\$c, \"SHOW TABLES LIKE 'wp_%'\");
    if (mysqli_num_rows(\$result) > 0) {
        // wp_options: Optimize for utf8mb4
        mysqli_query(\$c, \"ALTER TABLE wp_options MODIFY option_name VARCHAR(255) NOT NULL\");
        mysqli_query(\$c, \"ALTER TABLE wp_options MODIFY autoload ENUM('yes','no') NOT NULL DEFAULT 'yes'\");

        // wp_posts: Drop unused column, optimize guid
        @mysqli_query(\$c, \"ALTER TABLE wp_posts DROP COLUMN post_content_filtered\");
        mysqli_query(\$c, \"ALTER TABLE wp_posts MODIFY guid VARBINARY(255) NOT NULL\");

        // Add indexes (ignore if already exist)
        @mysqli_query(\$c, \"ALTER TABLE wp_posts ADD INDEX post_modified_id (post_modified, ID)\");
        @mysqli_query(\$c, \"ALTER TABLE wp_posts ADD INDEX type_name (post_type, post_name)\");
        @mysqli_query(\$c, \"ALTER TABLE wp_postmeta ADD INDEX post_meta_idx (post_id, meta_key(255))\");

        echo 'Migrations complete.';
    } else {
        echo 'WordPress not installed yet - skipping migrations.';
    }
    mysqli_close(\$c);
    "
fi

echo "Starting PHP-FPM..."
exec php-fpm