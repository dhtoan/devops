<?php
// FlyingPress

$config = CONFIG_TO_REPLACE;

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