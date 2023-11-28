<?php

namespace FlyingPress;

class WPCache
{
  public static function init()
  {
    register_activation_hook(FLYING_PRESS_FILE_NAME, [__CLASS__, 'add_constant']);
    register_deactivation_hook(FLYING_PRESS_FILE_NAME, [__CLASS__, 'remove_constant']);
    add_action('admin_notices', [__CLASS__, 'add_no_constant_error']);
  }

  public static function add_constant()
  {
    $wp_config_path = ABSPATH . 'wp-config.php';

    // If wp-config.php is not found in the current directory,
    // look for it in the parent directory
    if (!file_exists($wp_config_path)) {
      $parent_dir = dirname(ABSPATH);
      $wp_config_path = $parent_dir . '/wp-config.php';
    }

    // Skip if file doesn't exist or isn't writable
    if (!file_exists($wp_config_path) || !is_writable($wp_config_path)) {
      return;
    }

    $wp_config = file_get_contents($wp_config_path);

    // Remove any existing WP_CACHE constant
    $regex_for_wp_cache = '/define\(\s*["\']WP_CACHE[\'\"].*/';
    $wp_config = preg_replace($regex_for_wp_cache, '', $wp_config);

    // Add our WP_CACHE constant
    $constant = "\ndefine('WP_CACHE', true); // Added by FlyingPress";
    $wp_config = str_replace('<?php', '<?php' . $constant, $wp_config);
    file_put_contents($wp_config_path, $wp_config);
  }

  public static function remove_constant()
  {
    $wp_config_path = ABSPATH . 'wp-config.php';

    // If wp-config.php is not found in the current directory,
    // look for it in the parent directory
    if (!file_exists($wp_config_path)) {
      $parent_dir = dirname(ABSPATH);
      $wp_config_path = $parent_dir . '/wp-config.php';
    }

    // Skip if file doesn't exist or isn't writable
    if (!file_exists($wp_config_path) || !is_writable($wp_config_path)) {
      return;
    }

    $wp_config = file_get_contents($wp_config_path);

    // Remove any existing WP_CACHE constant
    $regex_for_wp_cache = '/define\(\s*["\']WP_CACHE[\'\"].*/';
    $wp_config = preg_replace($regex_for_wp_cache, '', $wp_config);
    file_put_contents($wp_config_path, $wp_config);
  }

  public static function add_no_constant_error()
  {
    if (get_current_screen()->id !== 'toplevel_page_flying-press') {
      return;
    }

    if (defined('WP_CACHE') && WP_CACHE) {
      return;
    }

    echo '<div class="notice notice-error">';
    echo '<p><strong>FlyingPress</strong> requires the WP_CACHE constant to be defined and set to true in your <b>wp-config.php</b> file.</p>';
    echo '<p>FlyingPress was unable to automatically write this change. ';
    echo 'Please add the following to your wp-config.php file: <code>define(\'WP_CACHE\', true); // Added by FlyingPress</code></p>';
    echo '</div>';
  }
}
