<?php

namespace FlyingPress\Optimizer;

use FlyingPress\Config;
use FlyingPress\Utils;

class CDN
{
  public static function add_preconnect($html)
  {
    if (!Config::$config['cdn'] || !Config::$config['cdn_url']) {
      return $html;
    }

    $cdn_url = Config::$config['cdn_url'];

    // Add preconnect link
    $html = Utils::str_replace_first(
      '</title>',
      '</title>' . PHP_EOL . '<link rel="preconnect" href="' . $cdn_url . '"/>',
      $html
    );

    return $html;
  }

  public static function rewrite($html)
  {
    // No need to add CDN
    if (!Config::$config['cdn'] || !Config::$config['cdn_url']) {
      return $html;
    }

    // Get site domain and CDN domain
    $site_domain = '//' . preg_replace('(^https?://)', '', site_url());
    $cdn_domain = '//' . preg_replace('(^https?://)', '', Config::$config['cdn_url']);

    // Files types as regex
    $file_types = [
      'all' => 'css|js|eot|otf|ttf|woff|woff2|gif|jpeg|jpg|png|svg|webp|avif|jxl|ico|webm|mp4|ogg',
      'css_js_font' => 'css|js|eot|otf|ttf|woff|woff2',
      'image' => 'gif|jpeg|jpg|png|svg|webp|avif|jxl|ico',
    ];

    // Pick the regex based on `cdn_file_types` config
    $file_types_regex = $file_types[Config::$config['cdn_file_types']];

    // Escape for regex
    $site_domain_escaped = preg_quote($site_domain, '/');
    // Generate final regex
    // $regex = "/{$home_url_escaped}(\S+?\.({$file_types_regex}))/";
    $regex = "/{$site_domain_escaped}([^\"']*?\.({$file_types_regex})[?\"\'\s)>,])/";

    // Find and replace URLs
    $html = preg_replace($regex, $cdn_domain . '$1', $html);
    return $html;
  }
}
