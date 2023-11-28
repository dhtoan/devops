<?php

namespace FlyingPress\Optimizer;

use FlyingPress\Caching;
use FlyingPress\Config;
use FlyingPress\Utils;
use MatthiasMullie\Minify;

class Javascript
{
  public static function init()
  {
    add_action('wp_enqueue_scripts', [__CLASS__, 'inject_preload_lib']);
  }

  public static function minify($html)
  {
    if (!Config::$config['js_minify']) {
      return $html;
    }

    // get all the scripts with src attribute
    preg_match_all('/<script[^>]*src=[\'"][^\'"]+[\'"][^>]*><\/script>/i', $html, $scripts);

    // Get excluded keywords from filter
    $exclude_keywords = apply_filters('flying_press_exclude_from_minify:js', []);

    try {
      // loop through all the scripts
      foreach ($scripts[0] as $script) {
        // skip if script is excluded
        if (Utils::any_keywords_match_string($exclude_keywords, $script)) {
          continue;
        }
        $script = new HTML($script);
        // get src
        $src = $script->src;
        $file_path = Caching::get_file_path_from_url($src);
        if (!is_file($file_path)) {
          continue;
        }
        $hash = substr(hash_file('md5', $file_path), 0, 12);

        // Generate new hashed file name
        $file_name = $hash . '.' . basename($file_path);
        $minified_path = FLYING_PRESS_CACHE_DIR . $file_name;
        $minified_url = FLYING_PRESS_CACHE_URL . $file_name;

        // Minify if ninified version of the file doesn't exist
        if (!is_file($minified_path)) {
          $minifier = new Minify\JS($file_path);
          $minifier->minify($minified_path);
        }
        // Check if minified version is smaller than original
        $original_file_size = filesize($file_path);
        $minified_file_size = filesize($minified_path);
        $wasted_bytes = $original_file_size - $minified_file_size;
        $wasted_percent = ($wasted_bytes / $original_file_size) * 100;
        $is_already_minified = preg_match('/\.min\.js/', $file_path);

        if ($wasted_bytes < 2048 || $wasted_percent < 10 || $is_already_minified) {
          $minified_url = strtok($src, '?') . "?ver=$hash";
        }

        $html = str_replace($src, $minified_url, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function defer_external($html)
  {
    if (!Config::$config['js_defer']) {
      return $html;
    }

    // get all the scripts with src attribute
    preg_match_all('/<script\s+[^>]*src=(["\']).*?\1[^>]*>/i', $html, $scripts);

    // get excluded keywords
    $exclude_keywords = Config::$config['js_defer_excludes'];

    try {
      // loop through all the scripts
      foreach ($scripts[0] as $script_tag) {
        // skip if script is excluded
        if (Utils::any_keywords_match_string($exclude_keywords, $script_tag)) {
          continue;
        }
        $script = new HTML($script_tag);
        // remove existing async
        unset($script->async);
        // add defer
        $script->defer = true;

        $html = str_replace($script_tag, $script, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function defer_inline($html)
  {
    if (!Config::$config['js_defer']) {
      return $html;
    }

    if (!Config::$config['js_defer_inline']) {
      return $html;
    }

    // get all the scripts without src attribute
    preg_match_all('/<script(?![^>]*src)[^>]*>(.*?)<\/script>/ism', $html, $scripts);

    $exclude_keywords = Config::$config['js_defer_excludes'];

    try {
      foreach ($scripts[0] as $script_tag) {
        // skip if script is excluded
        if (Utils::any_keywords_match_string($exclude_keywords, $script_tag)) {
          continue;
        }

        $script = new HTML($script_tag);

        // Skip non-standard scripts
        if ($script->type && $script->type != 'text/javascript') {
          continue;
        }

        // Convert script to data URI
        $src = 'data:text/javascript,' . rawurlencode($script->getContent());

        // Remove script content
        $script->setContent('');

        // Set src attribute as data URI
        $script->src = $src;
        $script->defer = true;

        $html = str_replace($script_tag, $script, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function delay_scripts($html)
  {
    $config = Config::$config;

    if (!$config['js_delay']) {
      return $html;
    }

    // get all the scripts
    preg_match_all('/<script[^>]*>([\s\S]*?)<\/script>/i', $html, $scripts);

    // get delay method
    $delay_method = $config['js_delay_method']; // exclude, include

    // get keywords
    $keywords =
      $delay_method === 'selected'
        ? $config['js_delay_selected']
        : $config['js_delay_all_excludes'];

    try {
      // loop through all the scripts
      foreach ($scripts[0] as $script_tag) {
        // check delay method
        $is_keyword_matched = Utils::any_keywords_match_string($keywords, $script_tag);
        if (
          ($delay_method === 'selected' && !$is_keyword_matched) ||
          ($delay_method === 'all' && $is_keyword_matched)
        ) {
          continue;
        }

        $script = new HTML($script_tag);

        // Skip Rest API script injected by FlyingPress
        if ($script->id === 'flying-press-rest') {
          continue;
        }

        // Skip non-standard scripts
        if ($script->type && $script->type != 'text/javascript') {
          continue;
        }

        // Convert script to data URI if it's inline
        $src = $script->src ?? 'data:text/javascript,' . rawurlencode($script->getContent());

        $script->{'data-src'} = $src;
        unset($script->src);
        $script->setContent('');

        $html = str_replace($script_tag, $script, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function inject_core_lib($html)
  {
    $js_code = file_get_contents(FLYING_PRESS_PLUGIN_DIR . 'assets/core.min.js');

    // Get timeout from filter and convert it to milliseconds (default to 10s)
    $timeout = apply_filters('flying_press_js_delay_timeout', 10);
    $timeout = $timeout * 1000;

    // Replace timeout placeholder with actual timeout
    $js_code = str_replace('INTERACTION_TIMEOUT', $timeout, $js_code);

    // create script tag and  add append it to the  body tag
    $script_tag = PHP_EOL . "<script>$js_code</script>" . PHP_EOL;
    $html = str_replace('</body>', "$script_tag</body>", $html);
    return $html;
  }

  public static function inject_preload_lib()
  {
    if (!Config::$config['js_preload_links']) {
      return;
    }

    // Enqueue preload script
    wp_enqueue_script(
      'flying_press_preload',
      FLYING_PRESS_PLUGIN_URL . 'assets/preload.min.js',
      [],
      FLYING_PRESS_VERSION,
      true
    );

    // Add defer to preload script
    wp_script_add_data('flying_press_preload', 'defer', true);
  }
}
