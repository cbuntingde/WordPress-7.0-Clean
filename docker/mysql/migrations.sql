-- WordPress 7.0 PHP 8.5+ Schema Migrations
-- Run against MariaDB 12+ / MySQL 8.0+

-- wp_options: Optimize for utf8mb4
ALTER TABLE wp_options 
  MODIFY option_name VARCHAR(255) NOT NULL,
  MODIFY autoload ENUM('yes', 'no') NOT NULL DEFAULT 'yes',
  DROP INDEX IF EXISTS autoload,
  ADD KEY option_name_autoload (option_name, autoload);

-- wp_posts: Drop unused column, optimize guid
ALTER TABLE wp_posts
  DROP COLUMN IF EXISTS post_content_filtered,
  MODIFY guid VARBINARY(255) NOT NULL,
  ADD KEY post_modified_id (post_modified, ID),
  ADD KEY type_name (post_type, post_name);

-- wp_postmeta: Add composite index
ALTER TABLE wp_postmeta
  ADD KEY post_meta_idx (post_id, meta_key(255));