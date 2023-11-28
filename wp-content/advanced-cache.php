<?php
// FlyingPress

$config = array (
  'cache_lifespan' => 'never',
  'cache_ignore_queries' => 
  array (
    0 => 'cache_bust',
    1 => 'age-verified',
    2 => 'ao_noptimize',
    3 => 'usqp',
    4 => 'cn-reloaded',
    5 => 'sscid',
    6 => 'ef_id',
    7 => 's_kwcid',
    8 => '_bta_tid',
    9 => '_bta_c',
    10 => 'dm_i',
    11 => 'fb_action_ids',
    12 => 'fb_action_types',
    13 => 'fb_source',
    14 => 'fbclid',
    15 => 'utm_id',
    16 => 'utm_source',
    17 => 'utm_campaign',
    18 => 'utm_medium',
    19 => 'utm_expid',
    20 => 'utm_term',
    21 => 'utm_content',
    22 => '_ga',
    23 => 'gclid',
    24 => 'campaignid',
    25 => 'adgroupid',
    26 => 'adid',
    27 => '_gl',
    28 => 'gclsrc',
    29 => 'gdfms',
    30 => 'gdftrk',
    31 => 'gdffi',
    32 => '_ke',
    33 => 'trk_contact',
    34 => 'trk_msg',
    35 => 'trk_module',
    36 => 'trk_sid',
    37 => 'mc_cid',
    38 => 'mc_eid',
    39 => 'mkwid',
    40 => 'pcrid',
    41 => 'mtm_source',
    42 => 'mtm_medium',
    43 => 'mtm_campaign',
    44 => 'mtm_keyword',
    45 => 'mtm_cid',
    46 => 'mtm_content',
    47 => 'msclkid',
    48 => 'epik',
    49 => 'pp',
    50 => 'pk_source',
    51 => 'pk_medium',
    52 => 'pk_campaign',
    53 => 'pk_keyword',
    54 => 'pk_cid',
    55 => 'pk_content',
    56 => 'redirect_log_mongo_id',
    57 => 'redirect_mongo_id',
    58 => 'sb_referer_host',
    59 => 'ref',
    60 => 'ttclid',
  ),
  'cache_logged_in' => false,
  'cache_mobile' => true,
  'cache_bypass_urls' => 
  array (
  ),
  'cache_bypass_cookies' => 
  array (
  ),
  'license_key' => '44388001ad1c795be4254237ce2519c8',
  'license_status' => 'valid',
  'license_name' => 'Admin',
  'license_email' => 'Valid',
  'license_expiry' => '2024-11-28 03:55:33',
  'css_minify' => true,
  'css_rucss' => true,
  'css_rucss_method' => 'async',
  'css_rucss_exclude_stylesheets' => 
  array (
  ),
  'css_rucss_include_selectors' => 
  array (
  ),
  'css_lazy_render_selectors' => 
  array (
  ),
  'js_minify' => true,
  'js_preload_links' => true,
  'js_defer' => true,
  'js_defer_inline' => true,
  'js_defer_excludes' => 
  array (
  ),
  'js_delay' => true,
  'js_delay_method' => 'selected',
  'js_delay_all_excludes' => 
  array (
  ),
  'js_delay_selected' => 
  array (
    0 => 'googletagmanager.com',
    1 => 'google-analytics.com',
    2 => 'googleoptimize.com',
    3 => 'adsbygoogle.js',
    4 => 'xfbml.customerchat.js',
    5 => 'fbevents.js',
    6 => 'widget.manychat.com',
    7 => 'cookie-law-info',
    8 => 'grecaptcha.execute',
    9 => 'static.hotjar.com',
    10 => 'hs-scripts.com',
    11 => 'embed.tawk.to',
    12 => 'disqus.com/embed.js',
    13 => 'client.crisp.chat',
    14 => 'matomo.js',
    15 => 'usefathom.com',
    16 => 'code.tidio.co',
    17 => 'metomic.io',
    18 => 'js.driftt.com',
    19 => 'cdn.onesignal.com',
  ),
  'fonts_optimize_google_fonts' => true,
  'fonts_display_swap' => true,
  'fonts_preload_urls' => 
  array (
  ),
  'img_lazyload' => true,
  'img_lazyload_exclude_count' => 2,
  'img_lazyload_excludes' => 
  array (
  ),
  'img_width_height' => false,
  'img_localhost_gravatar' => false,
  'img_preload' => true,
  'img_responsive' => false,
  'iframe_lazyload' => true,
  'iframe_youtube_placeholder' => true,
  'bloat_remove_google_fonts' => false,
  'bloat_disable_woo_cart_fragments' => false,
  'bloat_disable_woo_assets' => false,
  'bloat_disable_xml_rpc' => true,
  'bloat_disable_rss_feed' => false,
  'bloat_disable_block_css' => false,
  'bloat_disable_oembeds' => false,
  'bloat_disable_emojis' => true,
  'bloat_disable_cron' => false,
  'bloat_disable_jquery_migrate' => true,
  'bloat_disable_dashicons' => true,
  'bloat_remove_restapi' => false,
  'bloat_post_revisions_control' => true,
  'bloat_post_revisions_limit' => 'disable',
  'bloat_heartbeat_control' => true,
  'bloat_heartbeat_behaviour' => 'default',
  'bloat_heartbeat_frequency' => 15,
  'cdn' => false,
  'cdn_url' => '',
  'cdn_file_types' => 'all',
  'db_post_revisions' => false,
  'db_post_auto_drafts' => false,
  'db_post_trashed' => false,
  'db_comments_spam' => false,
  'db_comments_trashed' => false,
  'db_transients_expired' => false,
  'db_transients_all' => false,
  'db_optimize_tables' => false,
  'db_schedule_clean' => 'never',
  'settings_tracking' => false,
);

if (!headers_sent()) {
  // Set response cache headers
  header('x-flying-press-cache: MISS');
  header('x-flying-press-source: PHP');
}

// Check if cache_bust is set
if (isset($_GET['cache_bust'])) {
  return false;
}

// Check if the request method is HEAD or GET
if (!isset($_SERVER['REQUEST_METHOD']) || !in_array($_SERVER['REQUEST_METHOD'], ['HEAD', 'GET'])) {
  return false;
}

// Check if current page has any cookies set that should not be cached
foreach ($config['cache_bypass_cookies'] as $cookie) {
  if (preg_grep("/$cookie/i", array_keys($_COOKIE))) {
    return false;
  }
}

// Default file name is "index.php"
$file_name = 'index';

// Check if the user is logged in
$is_user_logged_in = preg_grep('/^wordpress_logged_in_/i', array_keys($_COOKIE));
if ($is_user_logged_in && !$config['cache_logged_in']) {
  return false;
}

// Append "-logged-in" to the file name if the user is logged in
$file_name .= $is_user_logged_in ? '-logged-in' : '';

// Add user role to cache file name
$file_name .= isset($_COOKIE['fp_logged_in_roles']) ? '-' . $_COOKIE['fp_logged_in_roles'] : '';

// Add currency code to cache if Aelia Currency Switcher is enabled
$file_name .= isset($_COOKIE['aelia_cs_selected_currency'])
  ? '-' . $_COOKIE['aelia_cs_selected_currency']
  : '';

// Add currency code to cache if YITH Currency Switcher is enabled
$file_name .= isset($_COOKIE['yith_wcmcs_currency']) ? '-' . $_COOKIE['yith_wcmcs_currency'] : '';

// Add currency code to cache file name if WCML Currency Switcher is active
$file_name .= isset($_COOKIE['wcml_currency']) ? '-' . $_COOKIE['wcml_currency'] : '';

// Check if user agent is mobile and append "mobile" to the file name
$is_mobile =
  isset($_SERVER['HTTP_USER_AGENT']) &&
  preg_match(
    '/Mobile|Android|Silk\/|Kindle|BlackBerry|Opera (Mini|Mobi)/i',
    $_SERVER['HTTP_USER_AGENT']
  );
$file_name .= $config['cache_mobile'] && $is_mobile ? '-mobile' : '';

// Remove ignored query string parameters from the URL
$query_strings = array_diff_key($_GET, array_flip($config['cache_ignore_queries']));
$file_name .= !empty($query_strings) ? '-' . md5(serialize($query_strings)) : '';

// Append ".html" to the file name
$file_name .= '.html';

// File path of the cached file
$host = $_SERVER['HTTP_HOST'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$cache_file_path = WP_CONTENT_DIR . '/cache/flying-press/' . $host . $path . $file_name;

// If we don't have a cache copy, we do not need to proceed
if (!file_exists($cache_file_path)) {
  return false;
}

// Set cache HIT response header
header('x-flying-press-cache: HIT');
// Add Last modified response header
$cache_last_modified = filemtime($cache_file_path);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $cache_last_modified) . ' GMT');

// Get last modified since from request header
$http_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
  ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])
  : 0;

// If file is not modified during this time, send 304
if ($http_modified_since >= $cache_last_modified) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', true, 304);
  exit();
}

readfile($cache_file_path);
exit();