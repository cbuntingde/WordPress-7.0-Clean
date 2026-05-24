# WordPress Modernized Core

WordPress has accumulated decades of backward compatibility code to support PHP versions from 5.6 through 8.x. This creates unnecessary overhead: polyfill functions that duplicate native PHP features, version checks scattered throughout the codebase, entire subsystems for protocols replaced by modern APIs, and legacy database workarounds for MySQL versions that reached end-of-life years ago. The codebase became difficult to audit, slower than necessary, and filled with code paths that no longer served any real purpose.

This project strips all of that away by requiring PHP 8.5+ and MySQL 8.0.0+. The result is cleaner code, fewer files to audit, faster execution, and a codebase that takes advantage of modern PHP features without conditional logic everywhere.

## Latest Changes

- Added disable-core-update mu-plugin to prevent automatic WordPress core updates
- Removed rss-functions.php (broken reference to missing rss.php)
- Simplified compat.php to sodium_compat loader only
- Replaced utf8_encode/utf8_decode with mb_convert_encoding()
- Deleted mbstring polyfills (_mb_substr, _mb_strlen, helper functions)
- Created stub rss.php to satisfy PHPStan
- Updated phpstan.neon exclusions (deprecated.php, blocks/)
- Removed 10 polyfills from compat.php (PHP 8.0-8.5 native functions)
- Deleted IXR/ directory (XML-RPC, 10 files)
- Deleted class-wp-xmlrpc-server.php, class-wp-http-ixr-client.php
- Removed xmlrpc_getposttitle(), xmlrpc_getpostcategory(), xmlrpc_removepostdata()
- Removed XML-RPC wp_die handler and filter
- Removed PHP_VERSION_ID checks in 8+ core files
- Created minimal IXR stub for pingback compatibility
- Deleted rss.php, atomlib.php, class-json.php
- Simplified curl_close(), imagedestroy(), finfo_close() calls
- Removed php-compat/readonly.php conditional include
- Deleted deprecated.php, ms-deprecated.php, pluggable-deprecated.php (8,000+ lines)
- Deleted class-IXR.php (XML-RPC library)
- Cleaned up PHP_VERSION_ID checks in image-edit.php, link-parse-opml.php
- Simplified font mime type handling (PHP 7.0+ only)
- Simplified sodium_compat autoloader for PHP 8.5+ only
- Removed PHP 8.0/8.1 MariaDB version workaround

## Known Issues in WordPress Core

- Polyfill Overload: compat.php contained 500+ lines of polyfills for PHP 8.0+ native functions
- XML-RPC Subsystem: ~15 files in wp-includes/IXR/ for a protocol REST API replaced over a decade ago
- Legacy JSON Parser: class-json.php (1,045 lines) using PEAR Services_JSON deprecated since WP 3.3
- Outdated Database Schema: VARCHAR limits set for utf8mb4 constraints that no longer apply with MySQL 8.0+
- mbstring Polyfills: Conditional code for mb_* functions when mbstring has been standard for years
- $GLOBALS Access: Direct global variable access patterns instead of proper getter functions
- Version Conditionals: ~21 files containing PHP_VERSION_ID checks that became no-ops
- Legacy Feed Parsers: RSS/Atom parsers superseded by SimplePie
- Old Admin Pages: link-manager.php, link-add.php, link.php, press-this.php, custom-background.php, custom-header.php

## Everything Removed

- Deleted wp-includes/IXR/ directory (~15 files) - XML-RPC support
- Deleted wp-includes/class-json.php (1,045 lines) - PEAR JSON parser
- Deleted wp-includes/rss.php - Legacy RSS parser
- Deleted wp-includes/rss-functions.php - Deprecated RSS functions
- Deleted wp-includes/atomlib.php - Atom feed parser
- Deleted wp-includes/deprecated.php (6,536 lines) - Legacy deprecated functions from WP 0.71-3.x
- Deleted wp-includes/ms-deprecated.php (752 lines) - Multisite deprecated functions
- Deleted wp-includes/pluggable-deprecated.php (221 lines) - Early pluggable functions
- Deleted wp-includes/vendor/symfony/polyfill-php*/ - Symfony polyfills (PHP 8.5 native)
- Deleted wp-includes/sodium_compat/ (104 files) - PHP sodium library (native in PHP 8.5)
- Deleted wp-includes/class.wp-dependencies.php - Duplicate wrapper class
- Deleted wp-includes/class.wp-scripts.php - Duplicate wrapper class
- Deleted wp-includes/class.wp-styles.php - Duplicate wrapper class
- Deleted wp-admin/includes/class-pclzip.php (5,732 lines) - Legacy ZIP library
- Deleted wp-admin/link-manager.php - Links manager (deprecated post Gutenberg)
- Deleted wp-admin/link-add.php - Links manager
- Deleted wp-admin/link.php - Links manager
- Deleted wp-admin/press-this.php - Press This bookmarklet
- Deleted wp-admin/custom-background.php - Legacy Custom Background
- Deleted wp-admin/custom-header.php - Legacy Custom Header
- Deleted FTP classes (ftpext, ftpsockets)
- Removed compat.php polyfills: str_contains, str_starts_with, str_ends_with, array_is_list
- Removed compat.php polyfills: array_find, array_find_key, array_any, array_all, array_first, array_last
- Removed compat.php mbstring polyfills (mb_substr, mb_strlen, mb_*)
- Removed compat.php utf8_encode/utf8_decode polyfills
- Removed XML-RPC wp_die handler
- Removed sodium_compat library (PHP 8.5 has native sodium)
- Removed PHP version checks and polyfills throughout codebase
- Removed feed-rss.php (RSS 0.92 feed)
- Bumped PHP minimum from 7.4 to 8.5
- Bumped MySQL minimum from 5.5.5 to 8.0.0
- Bumped database version from 61833 to 80000
- Renamed mysql2date() to wp_date_format()
- Renamed mysql_to_rfc3339() to iso8601_from_datetime()
- Added current_time('sql') parameter support
- Refactored $GLOBALS['post'] to get_post()
- Refactored $GLOBALS['wp_query'] to $wp_the_query
- Refactored $GLOBALS['current_screen'] to get_current_screen()
- Updated wp_options schema: option_name VARCHAR(191) → VARCHAR(255)
- Updated wp_options schema: autoload VARCHAR(20) → ENUM('yes','no')
- Added composite KEY (option_name, autoload) to wp_options
- Updated wp_posts schema: post_name VARCHAR(200) → VARCHAR(255)
- Added KEY (post_modified, ID) to wp_posts
- Added KEY (post_type, post_name) to wp_posts
- Added composite KEY (post_id, meta_key) to wp_postmeta
- Added upgrade_800() migration function for existing databases
- Modified wp-admin/includes/schema.php
- Modified wp-admin/includes/upgrade.php
- Modified wp-admin/includes/file.php (removed PCLZip integration)
- Modified wp-admin/includes/screen.php
- Modified wp-admin/includes/template.php
- Modified wp-admin/includes/admin.php
- Modified wp-admin/includes/ajax-actions.php
- Modified wp-admin/menu.php
- Modified wp-includes/version.php
- Fixed PHPStan ignore comments
- Simplified sodium verification (PHP 8.5 native)
- Removed 10 compat.php polyfills for PHP 8.0-8.5 native functions
- Removed IMAGETYPE_AVIF, IMAGETYPE_HEIF polyfills
- Simplified curl_close(), imagedestroy(), finfo_close() calls (now unconditional)
- Removed xml_parser_free() conditionals
- Removed libxml_disable_entity_loader() conditionals
- Removed php-compat/readonly.php conditional
- Removed PHP 8.0/8.1 MariaDB version workaround
- Removed PHP 8.0 imagedestroy() conditionals
- Removed PHP 7.0/8.0 font mime type checks

## Code Reduction Summary

- Files deleted: 31+
- Lines removed: 26,900+
- Functions removed: 11
- Functions deprecated with migration path: 7
- Database indexes added: 5

## Requirements

- PHP: 8.5+
- MySQL: 8.0.0+
- MariaDB: 10.4+

## Setup

```bash
docker compose up -d --build
```

Access at http://localhost:8090 (admin / admin123)

## Core Update Protection

This repo includes a must-use plugin that disables automatic WordPress core updates. This prevents accidental core overwrites that would lose the massive changes in this repo.

**How it works:**
- `wp-content/mu-plugins/disable-core-updates.php` loads automatically on every request
- Defines `WP_AUTO_UPDATE_CORE = false`
- Adds `auto_update_core` filter returning false

**For maximum protection**, also add this to your `wp-config.php`:

```php
define( 'WP_AUTO_UPDATE_CORE', false );
```

Plugins and themes can still be updated — only core updates are blocked.

## License

WordPress is free software released under the GPL v2 or later.

Modified by Chris Bunting <cbuntingde@gmail.com>