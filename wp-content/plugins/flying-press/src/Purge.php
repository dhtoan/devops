<?php

namespace FlyingPress;

class Purge
{
  // Purge a list of HTML pages
  public static function purge_urls($urls)
  {
    foreach ($urls as $url) {
      self::purge_url($url);
    }
  }

  // Purge a single HTML page
  public static function purge_url($url)
  {
    do_action('flying_press_purge_url:before', $url);

    // Get directory path for the URL
    $host = parse_url($url, PHP_URL_HOST);
    $path = parse_url($url, PHP_URL_PATH);
    $page_cache_dir = FLYING_PRESS_CACHE_DIR . $host . $path;

    // Find all HTML pages in the directory (NOT recursive)
    $pages = glob($page_cache_dir . '/*.html');

    // Delete all HTML pages
    array_map(function ($file) {
      is_file($file) && @unlink($file);
    }, $pages);

    do_action('flying_press_purge_url:after', $url);
  }

  // Purge all HTML pages
  public static function purge_pages()
  {
    do_action('flying_press_purge_pages:before');

    @unlink(FLYING_PRESS_CACHE_DIR . '/preload.txt');

    // Delete all HTML pages including subdirectories
    self::delete_files_by_type(FLYING_PRESS_CACHE_DIR, 'html');

    do_action('flying_press_purge_pages:after');
  }

  // Purge entire cache
  public static function purge_everything()
  {
    do_action('flying_press_purge_everything:before');

    @unlink(FLYING_PRESS_CACHE_DIR . '/preload.txt');

    // Delete all files and subdirectories
    self::delete_directory(FLYING_PRESS_CACHE_DIR);

    @mkdir(FLYING_PRESS_CACHE_DIR, 0755, true);

    do_action('flying_press_purge_everything:after');
  }

  private static function delete_files_by_type($path, $type)
  {
    if (is_file($path) && preg_match("/\.{$type}$/", $path)) {
      return @unlink($path);
    } else {
      $files = glob($path . '/*');
      if (!is_iterable($files)) {
        return false;
      }
      foreach ($files as $file) {
        self::delete_files_by_type($file, $type);
      }
      unset($files);
    }
    return @rmdir($path);
  }

  private static function delete_directory($path)
  {
    return is_file($path)
      ? @unlink($path)
      : array_map(__METHOD__, glob($path . '/*')) == @rmdir($path);
  }
}
