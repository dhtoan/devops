<?php

namespace FlyingPress\Integrations;

class APO
{
  public static function init()
  {
    if (!class_exists('CF\WordPress\Hooks') || !self::is_apo_enabled()) {
      return;
    }

    // When Cloudflare plugin is active Purge Cloudflare APO cache before purging FP cache
    add_action('flying_press_purge_pages:before', [__CLASS__, 'purge_cloudflare_cache']);

    // Purge Cloudflare cache before when entire FP cache is purged
    add_action('flying_press_purge_everything:before', [__CLASS__, 'purge_cloudflare_cache']);
  }

  public static function purge_cloudflare_cache()
  {
    $cfapi = new \CF\WordPress\Hooks();
    $cfapi->purgeCacheEverything();
  }

  private static function is_apo_enabled()
  {
    if (!class_exists('CF\API\Plugin')) {
      return false;
    }

    $datastore = new \CF\WordPress\DataStore(new \CF\Integration\DefaultLogger(false));
    $apo_config = $datastore->get('automatic_platform_optimization');
    if (!is_array($apo_config)) {
      return false;
    }
    if (
      array_key_exists('id', $apo_config) &&
      $apo_config['id'] === 'automatic_platform_optimization' &&
      $apo_config['value'] == '1'
    ) {
      return true;
    }
    return false;
  }
}
