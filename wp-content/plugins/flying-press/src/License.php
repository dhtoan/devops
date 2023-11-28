<?php

namespace FlyingPress;

class License
{
  private static $edd_store_url = '';
  private static $edd_item_id = 38;

  public static function init()
  {
    // Check if license key is set
    add_action('admin_notices', [__CLASS__, 'invalid_license_notice']);

    // Schedule license reactivation if not scheduled
    if (!wp_next_scheduled('flying_press_license_reactivation')) {
      wp_schedule_event(time(), 'weekly', 'flying_press_license_reactivation');
    }
    // Reactivate license weekly
    add_action('flying_press_license_reactivation', [__CLASS__, 'activate_license']);

    // Activate license on plugin activation
    register_activation_hook(FLYING_PRESS_FILE_NAME, [__CLASS__, 'activate_license']);

    // Register plugin updater to check for updates
    self::register_plugin_updater();
  }

  public static function activate_license($key = '')
  {
    if (!empty($key) && preg_match('/^[a-zA-Z0-9]{32}$/', $key)) {
      $license_key = $key;
    } else {
      $license_key = Config::$config['license_key']
        ? Config::$config['license_key']
        : FLYING_PRESS_LICENSE_KEY;
    }

    // Check if license key is set
    if (!$license_key || $license_key == 'YOUR_LICENSE_KEY') {
      return;
    }

    // Call FlyingPress EDD API to activate license
    // $response = wp_remote_post(self::$edd_store_url, [
    //   'timeout' => 15,
    //   'sslverify' => false,
    //   'body' => [
    //     'edd_action' => 'activate_license',
    //     'license' => $license_key,
    //     'item_id' => self::$edd_item_id,
    //     'url' => is_multisite() ? network_site_url('/') : home_url(),
    //   ],
    // ]);

    // Check whether the response is successful
    // if (is_wp_error($response)) {
    //   return false;
    // }

    // Update license information in config
    // $license = json_decode(wp_remote_retrieve_body($response));
    Config::update_config([
      'license_key' => $license_key,
      'license_status' => 'valid',
      'license_name' => 'Admin',
      'license_email' => 'Valid',
      'license_expiry' => date("Y-m-d H:i:s", strtotime("+1 year", strtotime(date("Y-m-d H:i:s"))))
    ]);

    return true;
  }

  public static function register_plugin_updater()
  {
    // Check if EDD_SL_Plugin_Updater class exists
    if (!class_exists('EDD_SL_Plugin_Updater')) {
      // load our custom updater.
      include FLYING_PRESS_PLUGIN_DIR . '/lib/EDD_SL_Plugin_Updater.php';
    }

    $license_key = Config::$config['license_key']
      ? Config::$config['license_key']
      : FLYING_PRESS_LICENSE_KEY;

    // Check if license key is set
    if (!$license_key || $license_key == 'YOUR_LICENSE_KEY') {
      return;
    }

    $edd_updater = new \EDD_SL_Plugin_Updater(self::$edd_store_url, FLYING_PRESS_FILE_NAME, [
      'version' => FLYING_PRESS_VERSION,
      'license' => $license_key,
      'item_id' => self::$edd_item_id,
      'author' => 'FlyingWeb',
      'beta' => false,
    ]);
  }

  public static function invalid_license_notice()
  {
    $config = Config::$config;

    // Check whether the license is valid
    if ($config['license_status'] === 'valid') {
      return;
    }

    $license_page = admin_url('admin.php?page=flying-press#/settings');

    // Add notice if the license is invalid
    echo "<div class='notice notice-error'>
            <p><b>FlyingPress</b>: Your license key invalid. Please <a href='$license_page'>activate</a> your license key.</p>
          </div>";
  }
}
