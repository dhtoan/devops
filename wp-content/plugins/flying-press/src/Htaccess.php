<?php

namespace FlyingPress;

class Htaccess
{
  public static function init()
  {
    register_activation_hook(FLYING_PRESS_FILE_NAME, [__CLASS__, 'add_htaccess_rules']);
    register_deactivation_hook(FLYING_PRESS_FILE_NAME, [__CLASS__, 'remove_htaccess_rules']);
    add_action('flying_press_update_config:after', [__CLASS__, 'add_htaccess_rules']);
  }

  public static function add_htaccess_rules()
  {
    $htaccess_file = ABSPATH . '.htaccess';

    if (!file_exists($htaccess_file) || !is_writeable($htaccess_file)) {
      return;
    }

    // Get the contents of the .htaccess file
    $htaccess_contents = file_get_contents($htaccess_file);

    // Get the rules we want to add
    $flying_press_rules = file_get_contents(FLYING_PRESS_PLUGIN_DIR . 'assets/htaccess.txt');

    $config = Config::$config;

    // If separate mobile caching is enabled, replace MOBILE_CACHING_FLAG:0 with MOBILE_CACHING_FLAG:1
    if ($config['cache_mobile']) {
      $flying_press_rules = str_replace(
        'MOBILE_CACHING_FLAG:0',
        'MOBILE_CACHING_FLAG:1',
        $flying_press_rules
      );
    }

    // Allow others to modify the rules
    $flying_press_rules = apply_filters('flying_press_htaccess_rules', $flying_press_rules);

    $marker_regex = '/# BEGIN FlyingPress.*# END FlyingPress/s';

    // If the rules is already in the file, replace it
    if (preg_match($marker_regex, $htaccess_contents)) {
      $htaccess_contents = preg_replace($marker_regex, $flying_press_rules, $htaccess_contents);
    }
    // Otherwise, add it to the top of the file
    else {
      $htaccess_contents = "$flying_press_rules\n$htaccess_contents";
    }

    file_put_contents($htaccess_file, $htaccess_contents);
  }

  public static function remove_htaccess_rules()
  {
    $htaccess_file = ABSPATH . '.htaccess';

    if (!file_exists($htaccess_file) || !is_writeable($htaccess_file)) {
      return;
    }

    $htaccess = file_get_contents($htaccess_file);

    // Remove our rules
    $htaccess = preg_replace('/# BEGIN FlyingPress.*# END FlyingPress\n*/s', '', $htaccess);

    // Write back to htaccess
    file_put_contents($htaccess_file, $htaccess);
  }
}
