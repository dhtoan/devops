<?php

namespace FlyingPress\Optimizer;

use FlyingPress\Config;

class Font
{
  private static $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.122 Safari/537.36';

  public static function add_display_swap_to_google_fonts($html)
  {
    if (!Config::$config['fonts_display_swap']) {
      return $html;
    }

    // get all link tags with google fonts
    preg_match_all(
      '/<link[^>]+href=[\'"]https\:\/\/fonts\.googleapis\.com\/css[^\'"]+[\'"][^>]*>/i',
      $html,
      $googlefonts
    );
    try {
      foreach ($googlefonts[0] as $google_font_tag) {
        $google_font = new HTML($google_font_tag);
        $href = $google_font->href;
        // Remove display=swap if it exists
        $href = preg_replace('/(\?|&)display=[^\'"]*/', '', $href);
        // Add display=swap
        $href .= '&display=swap';
        $google_font->href = $href;
        $html = str_replace($google_font_tag, $google_font, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function add_display_swap_to_internal_styles($html)
  {
    if (!Config::$config['fonts_display_swap']) {
      return $html;
    }

    // get all style tags
    preg_match_all('/<style[^>]*>([^<]*)<\/style>/i', $html, $styles);
    try {
      foreach ($styles[0] as $style_tag) {
        $style = new HTML($style_tag);
        $css = $style->getContent();
        $css = self::inject_display_swap($css);
        $style->setContent($css);
        $html = str_replace($style_tag, $style, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function optimize_google_fonts($html)
  {
    if (!Config::$config['fonts_optimize_google_fonts']) {
      return $html;
    }

    if (Config::$config['bloat_remove_google_fonts']) {
      return $html;
    }

    // Remove all preconnect, preload, prefech, and dns-prefetch tags
    $html = preg_replace(
      '/<link[^>]*(?:preload|preconnect|prefetch)[^>]*(?:fonts\.gstatic\.com|fonts\.googleapis\.com)[^>]*>/i',
      '',
      $html
    );

    // Find all Google Fonts
    preg_match_all(
      '/<link[^>]+href=[\'"](https\:\/\/|\/\/)fonts\.googleapis\.com\/css[^\'"]+[\'"][^>]*>/i',
      $html,
      $googlefonts
    );
    try {
      foreach ($googlefonts[0] as $google_font_tag) {
        $google_font = new HTML($google_font_tag);
        $href = $google_font->href;
        $hash = substr(md5($href), 0, 12);
        $file_name = "$hash.google-font.css";
        $file_path = FLYING_PRESS_CACHE_DIR . $file_name;
        $file_url = FLYING_PRESS_CACHE_URL . $file_name;
        if (!is_file($file_path)) {
          self::self_host_style_sheet($href, $file_path);
        }
        $google_font->href = $file_url;
        $html = str_replace($google_font_tag, $google_font, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function preload_fonts($html)
  {
    $font_urls = Config::$config['fonts_preload_urls'];

    $preload_tags = '';
    try {
      foreach ($font_urls as $font_url) {
        // get file extension from url and create type attribute
        $type = 'font/' . pathinfo($font_url, PATHINFO_EXTENSION);
        $preload_tags .= "<link rel='preload' href='$font_url' as='font' type='$type' fetchpriority='high' crossorigin='anonymous'>";
      }

      $html = str_replace('</head>', $preload_tags . '</head>', $html);
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function inject_display_swap($content)
  {
    if (!Config::$config['fonts_display_swap']) {
      return $content;
    }

    // Remove existing font-display: xxx
    $content = preg_replace('/font-display:\s*(swap|block|fallback|optional);?/', '', $content);

    // Add font-display: swap
    return preg_replace('/@font-face\s*{/', '@font-face{font-display:swap;', $content);
  }

  // Source: https://gist.github.com/nicklasos/365a251d63d94876179c
  public static function download_urls_in_parallel($urls, $save_path)
  {
    $multi_handle = curl_multi_init();
    $file_pointers = [];
    $curl_handles = [];

    // Add curl multi handles, one per file we don't already have
    foreach ($urls as $key => $url) {
      $file = $save_path . '/' . basename($url);
      $curl_handles[$key] = curl_init($url);
      $file_pointers[$key] = fopen($file, 'w');
      curl_setopt($curl_handles[$key], CURLOPT_USERAGENT, self::$user_agent);
      curl_setopt($curl_handles[$key], CURLOPT_FILE, $file_pointers[$key]);
      curl_setopt($curl_handles[$key], CURLOPT_HEADER, 0);
      curl_setopt($curl_handles[$key], CURLOPT_CONNECTTIMEOUT, 60);
      curl_multi_add_handle($multi_handle, $curl_handles[$key]);
    }

    // Download the files
    do {
      curl_multi_exec($multi_handle, $running);
    } while ($running > 0);

    // Free up objects
    foreach ($urls as $key => $url) {
      curl_multi_remove_handle($multi_handle, $curl_handles[$key]);
      curl_close($curl_handles[$key]);
      fclose($file_pointers[$key]);
    }
    curl_multi_close($multi_handle);
  }

  private static function self_host_style_sheet($url, $file_path)
  {
    // If URL starts with "//", add https
    if (substr($url, 0, 2) === '//') {
      $url = 'https:' . $url;
    }

    // Download style sheet
    $css_file_response = wp_remote_get($url, [
      'user-agent' => self::$user_agent,
      'httpversion' => '2.0',
    ]);

    // Check Google Fonts returned response
    if (
      is_wp_error($css_file_response) ||
      wp_remote_retrieve_response_code($css_file_response) !== 200
    ) {
      return false;
    }

    // Extract body (CSS)
    $css = $css_file_response['body'];

    // Get list of fonts (woff2 files) inside the CSS
    $font_urls = self::get_font_urls($css);

    self::download_urls_in_parallel($font_urls, FLYING_PRESS_CACHE_DIR);

    foreach ($font_urls as $font_url) {
      $cached_font_url = FLYING_PRESS_CACHE_URL . basename($font_url);
      $css = str_replace($font_url, $cached_font_url, $css);
    }

    file_put_contents($file_path, $css);
  }

  private static function get_font_urls($css)
  {
    // Extract font urls like: https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu4mxKKTU1Kg.woff2
    $regex = '/url\((https:\/\/fonts\.gstatic\.com\/.*?)\)/';
    preg_match_all($regex, $css, $matches);
    return array_unique($matches[1]);
  }
}
