<?php

namespace FlyingPress;
use FlyingPress\Utils;

class Caching
{
  public static $default_ignore_queries = [
    'cache_bust',
    'age-verified',
    'ao_noptimize',
    'usqp',
    'cn-reloaded',
    'sscid',
    'ef_id',
    's_kwcid',
    '_bta_tid',
    '_bta_c',
    'dm_i',
    'fb_action_ids',
    'fb_action_types',
    'fb_source',
    'fbclid',
    'utm_id',
    'utm_source',
    'utm_campaign',
    'utm_medium',
    'utm_expid',
    'utm_term',
    'utm_content',
    '_ga',
    'gclid',
    'campaignid',
    'adgroupid',
    'adid',
    '_gl',
    'gclsrc',
    'gdfms',
    'gdftrk',
    'gdffi',
    '_ke',
    'trk_contact',
    'trk_msg',
    'trk_module',
    'trk_sid',
    'mc_cid',
    'mc_eid',
    'mkwid',
    'pcrid',
    'mtm_source',
    'mtm_medium',
    'mtm_campaign',
    'mtm_keyword',
    'mtm_cid',
    'mtm_content',
    'msclkid',
    'epik',
    'pp',
    'pk_source',
    'pk_medium',
    'pk_campaign',
    'pk_keyword',
    'pk_cid',
    'pk_content',
    'redirect_log_mongo_id',
    'redirect_mongo_id',
    'sb_referer_host',
    'ref',
    'ttclid',
  ];

  public static function init()
  {
    add_action('init', [__CLASS__, 'setup_cache_lifespan']);
    add_action('flying_press_cache_lifespan', [__CLASS__, 'run_cache_lifespan']);
    add_action('init', [__CLASS__, 'add_logged_in_roles']);
    add_action('wp_logout', [__CLASS__, 'remove_logged_in_roles']);
  }

  public static function run_cache_lifespan()
  {
    Purge::purge_pages();
    Preload::preload_cache();
  }

  public static function setup_cache_lifespan()
  {
    $lifespan = Config::$config['cache_lifespan'];
    $action_name = 'flying_press_cache_lifespan';

    if ($lifespan == 'never') {
      wp_clear_scheduled_hook($action_name);
      return;
    }

    if (!wp_next_scheduled($action_name) || wp_get_schedule($action_name) != $lifespan) {
      wp_clear_scheduled_hook($action_name);
      wp_schedule_event(time(), $lifespan, $action_name);
    }
  }

  public static function cache_page($html)
  {
    // Get the cache file name
    $cache_file_name = self::get_cache_file_name();

    // Get cache file path
    $host = $_SERVER['HTTP_HOST'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $cache_file_path = FLYING_PRESS_CACHE_DIR . $host . $path;

    // Create cache directory if it doesn't exist
    @mkdir($cache_file_path, 0755, true);

    // Write the HTML to the cache file
    file_put_contents($cache_file_path . $cache_file_name, $html);
  }

  public static function get_cache_file_name()
  {
    $config = Config::$config;
    $file_name = 'index';

    // Append "-logged-in" to the file name if the user is logged in
    $file_name .= $config['cache_logged_in'] && is_user_logged_in() ? '-logged-in' : '';

    // Add user role to cache file name
    $file_name .= isset($_COOKIE['fp_logged_in_roles']) ? '-' . $_COOKIE['fp_logged_in_roles'] : '';

    // Add currency code to cache file name if Aelia Currency Switcher is active
    $file_name .= isset($_COOKIE['aelia_cs_selected_currency'])
      ? '-' . $_COOKIE['aelia_cs_selected_currency']
      : '';

    // Add currency code to cache file name if YITH Currency Switcher is active
    $file_name .= isset($_COOKIE['yith_wcmcs_currency'])
      ? '-' . $_COOKIE['yith_wcmcs_currency']
      : '';

    // Add currency code to cache file name if WCML Currency Switcher is active
    $file_name .= isset($_COOKIE['wcml_currency']) ? '-' . $_COOKIE['wcml_currency'] : '';

    // Append the '-mobile' if mobile caching is enabled and the current device is mobile
    $file_name .= $config['cache_mobile'] && wp_is_mobile() ? '-mobile' : '';

    // Remove ignored query string parameters from the URL
    // Add default ignored query string parameters to the 'cache_ignore_queries' list
    $ignore_queries = $config['cache_ignore_queries'] + self::$default_ignore_queries;
    $query_strings = array_diff_key($_GET, array_flip($ignore_queries));
    $file_name .= !empty($query_strings) ? '-' . md5(serialize($query_strings)) : '';

    // Append the '.html' extension
    $file_name .= '.html';

    return apply_filters('flying_press_cache_file_name', $file_name);
  }

  public static function is_cacheable($content)
  {
    // Get the configuration
    $config = Config::$config;

    // Check for ?no_optimize in URL
    if (isset($_GET['no_optimize'])) {
      return false;
    }

    // Get the current full URL
    $current_url = site_url($_SERVER['REQUEST_URI']);

    // Check if current page is a WordPress page that should not be cached
    $regex = '/wp-(admin|login|register|comments-post|cron|json|sitemap)|\.(txt|xml)/';
    if (preg_match($regex, $current_url)) {
      return false;
    }

    // Check if it's a preview page of popular page builders
    $preview_queries = [
      'elementor-preview', // Elementor
      'preview', // WordPress
      'bricks', // Bricks
      'fl_builder', // Beaver Builder
      'et_fb', // Divi
      'vc_inline', // Visual Composer
      'tve', // Thrive Architect
      'siteorigin_panels_live_editor', // Page Builder by SiteOrigin
      'ct_builder', // Oxygen Builder
      'brizy_edit', // Brizy
      'vc_editable', // Visual Composer
      'fusion_builder', // Fusion Builder
      'stackable', // Stackable
      'generate_page_builder', // GeneratePress
      'breakdance', // Breakdance
      'cornerstone', // Cornerstone
      'ff_preview', // Beaver Themer
    ];
    foreach ($preview_queries as $query) {
      if (isset($_GET[$query])) {
        return false;
      }
    }

    // Check if request method is GET
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'GET') {
      return false;
    }

    // Check if current page is an AJAX request
    if (wp_doing_ajax()) {
      return false;
    }

    // Check if user is logged in and if the 'cache_logged_in' option is enabled
    if (is_user_logged_in() && !$config['cache_logged_in']) {
      return false;
    }

    // Check if current page is on the excluded pages list
    if (Utils::any_keywords_match_string($config['cache_bypass_urls'], $current_url)) {
      return false;
    }

    // Check if current page has any cookies set that should not be cached
    foreach ($config['cache_bypass_cookies'] as $cookie) {
      if (preg_grep("/$cookie/i", array_keys($_COOKIE))) {
        return false;
      }
    }

    // Check if current user is an admin or if current page does not respond with status code 200
    if (is_admin() || http_response_code() !== 200) {
      return false;
    }

    // Check if content is HTML
    if (!preg_match('/<!DOCTYPE\s*html\b[^>]*>/i', $content)) {
      return false;
    }

    // Check if AMP endpoint is enabled and if current page is an AMP page
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
      return false;
    }

    // Check if the post is password protected
    if (is_singular() && post_password_required()) {
      return false;
    }

    return apply_filters('flying_press_is_cacheable', true);
  }

  public static function count_pages($path)
  {
    $count = 0;
    if (is_file($path) && preg_match("/\.html$/", $path)) {
      $count++;
    } else {
      $files = glob($path . '/*');
      if (!is_iterable($files)) {
        return $count;
      }
      foreach ($files as $file) {
        $count += self::count_pages($file);
      }
      unset($files);
    }
    return $count;
  }

  public static function get_file_path_from_url($url)
  {
    $file_relative_path = parse_url($url, PHP_URL_PATH);
    $site_path = parse_url(site_url(), PHP_URL_PATH);
    $file_path = ABSPATH . preg_replace("$^$site_path$", '', $file_relative_path);
    return $file_path;
  }

  public static function add_logged_in_roles()
  {
    if (is_user_logged_in() && !isset($_COOKIE['fp_logged_in_roles'])) {
      $user = wp_get_current_user();
      $user_role = implode('-', $user->roles);
      $expiry = time() + 14 * DAY_IN_SECONDS;
      setcookie('fp_logged_in_roles', $user_role, $expiry, COOKIEPATH, COOKIE_DOMAIN, false);
    }
  }

  public static function remove_logged_in_roles()
  {
    if (isset($_COOKIE['fp_logged_in_roles'])) {
      // Unset the cookie
      unset($_COOKIE['fp_logged_in_roles']);
      // Set the cookie to expire in the past so it will be deleted by the browser
      setcookie('fp_logged_in_roles', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false);
    }
  }
}
