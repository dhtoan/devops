<?php

namespace FlyingPress\Optimizer;

use FlyingPress\Caching;
use FlyingPress\Config;
use FlyingPress\Utils;
use FlyingPress\Optimizer\HTML;
use Wa72\Url\Url;
use MatthiasMullie\Minify;

class CSS
{
  public static function minify($html)
  {
    if (!Config::$config['css_minify']) {
      return $html;
    }

    // run preg match all to grab all the tags
    preg_match_all("/<link[^>]*\srel=['\"]stylesheet['\"][^>]*>/", $html, $stylesheets);

    // excluded keywords from filter
    $exclude_keywords = apply_filters('flying_press_exclude_from_minify:css', []);
    try {
      foreach ($stylesheets[0] as $stylesheet_tag) {
        // check if any of the exclude keywords are in the tag
        if (Utils::any_keywords_match_string($exclude_keywords, $stylesheet_tag)) {
          continue;
        }

        $stylesheet = new HTML($stylesheet_tag);
        $href = $stylesheet->href;
        // Convert relative path to absolute path
        $file_path = Caching::get_file_path_from_url($href);
        if (!is_file($file_path)) {
          continue;
        }
        $css = file_get_contents($file_path);
        // Generate hash based on the css content and CDN url
        // If CDN URL changes, new hash will be generated
        $hash = Config::$config['cdn'] ? md5($css) : md5($css . Config::$config['cdn_url']);
        $file_name = substr($hash, 0, 12) . '.' . basename($file_path);
        $minified_path = FLYING_PRESS_CACHE_DIR . $file_name;
        $minified_url = FLYING_PRESS_CACHE_URL . $file_name;

        if (!is_file($minified_path)) {
          $minifier = new Minify\CSS($css);
          $minified_css = $minifier->minify();
          $minified_css = self::rewrite_absolute_urls($minified_css, $href);
          $minified_css = Font::inject_display_swap($minified_css);
          $minified_css = CDN::rewrite($minified_css);
          file_put_contents($minified_path, $minified_css);
        }

        $html = str_replace($href, $minified_url, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function remove_unused_css($html)
  {
    $config = Config::$config;

    if (!$config['css_rucss'] || \is_user_logged_in()) {
      return $html;
    }

    // Find used selectors in HTML
    $html_selectors = self::get_used_html_selectors($html);

    // Find stylesheets in HTML
    preg_match_all("/<link[^>]*\srel=['\"]stylesheet['\"][^>]*>/", $html, $stylesheets);

    // Filter out excluded stylesheets
    $excludes = $config['css_rucss_exclude_stylesheets'];
    $stylesheets = array_filter($stylesheets[0], function ($stylesheet) use ($excludes) {
      return !Utils::any_keywords_match_string($excludes, $stylesheet);
    });

    // Force include selectors
    $include_selectors = $config['css_rucss_include_selectors'];
    $include_selectors = array_filter($include_selectors);
    $include_selectors = array_map('preg_quote', $include_selectors);
    $include_selectors = implode('|', $include_selectors);

    foreach ($stylesheets as $stylesheet_tag) {
      $stylesheet = new HTML($stylesheet_tag);

      // Skip print stylesheets
      if ($stylesheet->media == 'print') {
        continue;
      }

      $href = $stylesheet->href;
      $css_file_path = Caching::get_file_path_from_url($href);

      // Skip if file doesn't exist
      if (!is_file($css_file_path)) {
        continue;
      }

      // Parse and remove unused CSS
      $css = file_get_contents($css_file_path);

      // Convert media queries to @media if present
      $media = $stylesheet->media;
      if (isset($media) && $media != 'all') {
        $css = "@media $media { $css }";
      }

      // Parse CSS and get used css
      $css = self::get_used_css_blocks($html_selectors, $css, $include_selectors);

      if (!$config['css_minify']) {
        $css = self::rewrite_absolute_urls($css, $href);
        $css = Font::inject_display_swap($css);
      }

      // Add used CSS to HTML, right after the link tag
      $used_styletag = "<style class='flying-press-used-css' original-href='$href'>$css</style>";
      $html = str_replace($stylesheet_tag, $used_styletag . PHP_EOL . $stylesheet_tag, $html);

      // Load unused CSS based on method selected (async, interaction, remove)
      switch ($config['css_rucss_method']) {
        case 'async':
          // Set media=print and onload, set media to original value
          $media = $stylesheet->media ?: 'all';
          $js = "this.onload=null;this.rel='stylesheet';this.media='$media';";
          $stylesheet->onload = $js;
          $stylesheet->media = 'print';
          $html = str_replace($stylesheet_tag, $stylesheet, $html);
          break;
        case 'interaction':
          // Use core.js to load stylesheet on interaction
          $href = $stylesheet->href;
          $stylesheet->{'data-href'} = $href;
          unset($stylesheet->href);
          $html = str_replace($stylesheet_tag, $stylesheet, $html);
          break;
        case 'remove':
          $html = str_replace($stylesheet_tag, '', $html);
          break;
      }
    }

    return $html;
  }

  public static function lazy_render($html)
  {
    // get all lazy render selectors
    $selectors = Config::$config['css_lazy_render_selectors'];

    // add lazy render class to all elements
    $selectors[] = '.lazy-render';

    // remove empty selectors
    $selectors = array_filter($selectors);

    try {
      foreach ($selectors as $selector) {
        // Find all elements matching the selector
        $elements = self::get_elements_by_selector($html, $selector);
        foreach ($elements as $element) {
          // Add the content-visibility to the element
          $html_element = new HTML($element);
          $css = 'content-visibility:auto; contain-intrinsic-size:1px 1000px;';
          $style = $css . $html_element->style;
          $html_element->style = $style;
          $html = str_replace($element, $html_element, $html);
        }
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  private static function get_used_html_selectors($html)
  {
    libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    $result = $dom->loadHTML($html);
    libxml_clear_errors();

    $dom->xpath = new \DOMXPath($dom);

    $html_selectors = [
      'tags' => [],
      'classes' => [],
      'ids' => [],
      'attributes' => [],
    ];

    foreach ($dom->getElementsByTagName('*') as $tag) {
      $html_selectors['tags'][$tag->tagName] = 1;

      if ($tag->hasAttribute('class')) {
        $classes = $tag->getAttribute('class');
        // Escape special characters
        $classes = str_replace([':', '/'], ['\:', '\/'], $classes);
        // Split classes to array
        $classes = preg_split('/\s+/', $classes);
        foreach ($classes as $class) {
          $html_selectors['classes'][$class] = 1;
        }
      }

      if ($tag->hasAttribute('id')) {
        $id = $tag->getAttribute('id');
        $html_selectors['ids'][$id] = 1;
      }

      foreach ($tag->attributes as $attribute) {
        $html_selectors['attributes'][$attribute->name] = 1;
      }
    }

    return $html_selectors;
  }

  private static function get_used_css_blocks($html_selectors, $css, $include_selectors)
  {
    $parsed_css_blocks = self::parse_css($css);

    foreach ($parsed_css_blocks as $css_block) {
      $selectors = $css_block['selectors'];

      if ($include_selectors && preg_match("/$include_selectors/", $selectors)) {
        continue;
      }

      $selectors = explode(',', $selectors);

      $selectors = array_filter($selectors, function ($selector) use ($html_selectors) {
        return self::is_selector_used($selector, $html_selectors);
      });

      if (empty($selectors)) {
        $css = Utils::str_replace_first($css_block['css'], '', $css);
      }
    }

    return $css;
  }

  private static function is_selector_used($selector, $html_selectors)
  {
    // eliminate false negatives (:not(), pseudo, etc...)
    $selector = preg_replace('/(?<!\\\\)::?[a-zA-Z0-9_-]+(\(.+?\))?/', '', $selector);

    //atts
    preg_match('/\[([A-Za-z0-9_:-]+)(\W?=[^\]]+)?\]/', $selector, $matches);
    if (isset($matches[1])) {
      if (!isset($html_selectors['attributes'][$matches[1]])) {
        return false;
      }
    }
    $selector = preg_replace('/\[([A-Za-z0-9_:-]+)(\W?=[^\]]+)?\]/', '', $selector);

    //classes
    preg_match_all('/\.((?:[a-zA-Z0-9_-]+|\\\\.)+)/', $selector, $matches);
    foreach ($matches[1] as $class) {
      if (!isset($html_selectors['classes'][$class])) {
        return false;
      }
    }
    $selector = preg_replace('/\.((?:[a-zA-Z0-9_-]+|\\\\.)+)/', '', $selector);

    //ids
    preg_match('/#([a-zA-Z0-9_-]+)/', $selector, $matches);
    if (isset($matches[1])) {
      if (!isset($html_selectors['ids'][$matches[1]])) {
        return false;
      }
    }
    $selector = preg_replace('/#([a-zA-Z0-9_-]+)/', '', $selector);

    //tags
    preg_match('/[a-zA-Z0-9_-]+/', $selector, $matches);
    if (isset($matches[0])) {
      if (!isset($html_selectors['tags'][$matches[0]])) {
        return false;
      }
    }

    return true;
  }

  private static function get_elements_by_selector($html, $selector)
  {
    $matches = [];
    if ($selector[0] === '.') {
      preg_match_all(
        '/(<[^>]*?class=[\'"](.*?)' . substr($selector, 1) . '(.*?)[\'"][^>]*>)/',
        $html,
        $matches
      );
    } elseif ($selector[0] === '#') {
      preg_match_all('/(<[^>]*?id=[\'"]' . substr($selector, 1) . '[\'"][^>]*>)/', $html, $matches);
    } else {
      preg_match_all('/(<' . $selector . '[^>]*>)/', $html, $matches);
    }
    return $matches[1];
  }

  private static function rewrite_absolute_urls($content, $base_url)
  {
    $regex = '/url\([\'"]*(.*?)[\'"]*\)/';

    $content = preg_replace_callback(
      $regex,
      function ($match) use ($base_url) {
        $url_string = $match[0];
        $relative_url = $match[1];
        $absolute_url = Url::parse($relative_url);
        $absolute_url->makeAbsolute(Url::parse($base_url));
        return str_replace($relative_url, $absolute_url, $url_string);
      },
      $content
    );

    return $content;
  }

  private static function parse_css($css)
  {
    // Remove comments
    $css = preg_replace('/\/\*.*?\*\//s', '', $css);

    // Remove all imports
    $css = preg_replace('/@import[^;]+;/', '', $css);

    // Remove charset
    $css = preg_replace('/@charset[^;]+;/', '', $css);

    // Remove all font-faces (including the code inside)
    $css = preg_replace('/@font-face[^}]+}/', '', $css);

    // Remove all @keyframes with or without vendor prefixes (including the code inside)
    $css = preg_replace('/@(-webkit-|-moz-|-o-|-ms-)?keyframes[\s\S]*?}\s*}/', '', $css);

    // Remove @rules without nested code
    $css = preg_replace('/@[^{]+{[^}]+}/', '', $css);

    // Remove all @rules
    $css = preg_replace('/@[^{]+{/', '', $css);

    // Combine double or more closing braces
    $css = preg_replace('/\}\s*(\}\s*)+/s', '}', $css);

    preg_match_all('/([^{]+)\s*\{([^}]+)\}\s*/', $css, $matches);

    $selectors = $matches[1];
    $styles = $matches[0];

    $parsed_css = [];
    foreach ($selectors as $index => $selector) {
      $parsed_css[] = [
        'selectors' => trim($selector),
        'css' => trim($styles[$index]),
      ];
    }

    return $parsed_css;
  }
}
