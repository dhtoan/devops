<?php

namespace FlyingPress;

class AdvancedCache
{
  public static function init()
  {
    register_activation_hook(FLYING_PRESS_FILE_NAME, [__CLASS__, 'add_advanced_cache']);
    register_deactivation_hook(FLYING_PRESS_FILE_NAME, [__CLASS__, 'remove_advanced_cache']);
    add_action('flying_press_update_config:after', [__CLASS__, 'add_advanced_cache']);
  }

  public static function add_advanced_cache()
  {
    $advanced_cache = file_get_contents(FLYING_PRESS_PLUGIN_DIR . 'assets/advanced-cache.php');

    $config = Config::$config;

    // Add default ignored query string parameters to the 'cache_ignore_queries' list
    $config['cache_ignore_queries'] += Caching::$default_ignore_queries;

    // Inejct the config into the advanced-cache.php file
    $config = var_export($config, true);

    // Replace the config placeholder with the actual config
    $advanced_cache = str_replace('CONFIG_TO_REPLACE', $config, $advanced_cache);

    // Allow plugins to modify the advanced-cache.php file
    $advanced_cache = apply_filters('flying_press_advanced_cache', $advanced_cache);

    file_put_contents(WP_CONTENT_DIR . '/advanced-cache.php', $advanced_cache);
  }

  public static function remove_advanced_cache()
  {
    if (is_file(WP_CONTENT_DIR . '/advanced-cache.php')) {
      unlink(WP_CONTENT_DIR . '/advanced-cache.php');
    }
  }
}
