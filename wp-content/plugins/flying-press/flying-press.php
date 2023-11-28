<?php

/**
 * Plugin Name: FlyingPress
 * Plugin URI: https://flyingpress.com
 * Description: Lightning-Fast WordPress on Autopilot
 * Version: 4.6.8
 */

defined('ABSPATH') or die('No script kiddies please!');

require_once dirname(__FILE__) . '/vendor/autoload.php';

define('FLYING_PRESS_LICENSE_KEY', '44388001ad1c795be4254237ce2519c8');
define('FLYING_PRESS_VERSION', '4.6.8');
define('FLYING_PRESS_FILE_NAME', plugin_basename(__FILE__));
define('FLYING_PRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FLYING_PRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLYING_PRESS_CACHE_DIR', WP_CONTENT_DIR . '/cache/flying-press/');
define('FLYING_PRESS_CACHE_URL', WP_CONTENT_URL . '/cache/flying-press/');

FlyingPress\WPCache::init();
FlyingPress\Htaccess::init();
FlyingPress\AdvancedCache::init();
FlyingPress\Integrations::init();
FlyingPress\AutoPurge::init();
FlyingPress\Config::init();
FlyingPress\Cron::init();
FlyingPress\Caching::init();
FlyingPress\RestApi::init();
FlyingPress\AdminBar::init();
FlyingPress\Optimizer::init();
FlyingPress\Dashboard::init();
FlyingPress\Database::init();
FlyingPress\Compatibility::init();
FlyingPress\Permalink::init();
FlyingPress\Shortcuts::init();
FlyingPress\License::init();
FlyingPress\Tracking::init();