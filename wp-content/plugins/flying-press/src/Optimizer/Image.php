<?php

namespace FlyingPress\Optimizer;

use FlyingPress\Caching as Caching;
use FlyingPress\Utils;
use FlyingPress\Config;

class Image
{
  private static $images = [];

  public static function init()
  {
    add_filter('wp_lazy_loading_enabled', '__return_false');
  }

  public static function parse_images($html)
  {
    // Remove all script tags to skip parsing images inside them
    $html_without_scripts = preg_replace('/<script.*?<\/script>/is', '', $html);

    // Find all images with src attribute
    preg_match_all('/<img[^>]+src=[\"\'][^>]+>/i', $html_without_scripts, $images);
    $images = $images[0];

    // Filter out base64 images
    $images = array_filter($images, function ($image) {
      return strpos($image, 'data:image') === false;
    });

    // Parse image using HTML class
    $images = array_map(function ($image) {
      return new HTML($image);
    }, $images);

    // Store images in the static variable
    self::$images = $images;
  }

  public static function add_width_height($html)
  {
    if (!Config::$config['img_width_height']) {
      return $html;
    }

    try {
      foreach (self::$images as $key => $image) {
        // get src attribute
        $src = $image->src;

        // Skip if both width and height are already set
        if (is_numeric($image->width) && is_numeric($image->height)) {
          continue;
        }

        // Get width and height
        $dimensions = self::get_dimensions($src);

        // Skip if no dimensions found
        if (!$dimensions) {
          continue;
        }

        // Add missing width and height attributes
        $ratio = $dimensions['width'] / $dimensions['height'];

        if (!is_numeric($image->width) && !is_numeric($image->height)) {
          $image->width = $dimensions['width'];
          $image->height = $dimensions['height'];
        } elseif (!is_numeric($image->width)) {
          $image->width = $image->height * $ratio;
        } elseif (!is_numeric($image->height)) {
          $image->height = $image->width / $ratio;
        }
        // replace the image
        self::$images[$key] = $image;
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    }
  }

  public static function exclude_above_fold($html)
  {
    if (!Config::$config['img_lazyload']) {
      return $html;
    }

    if (!Config::$config['img_lazyload_exclude_count']) {
      return $html;
    }

    $count = Config::$config['img_lazyload_exclude_count'];

    foreach (self::$images as $key => $image) {
      if ($key < $count) {
        $image->loading = 'eager';
        self::$images[$key] = $image;
      }
    }
  }

  public static function lazy_load($html)
  {
    if (!Config::$config['img_lazyload']) {
      return $html;
    }

    $default_exclude_keywords = ['eager', 'skip-lazy'];
    $user_exclude_keywords = Config::$config['img_lazyload_excludes'];

    // Merge default and user excluded keywords
    $exclude_keywords = array_merge($default_exclude_keywords, $user_exclude_keywords);

    try {
      foreach (self::$images as $key => $image) {
        // Image is excluded from lazy loading
        if (Utils::any_keywords_match_string($exclude_keywords, $image)) {
          $image->loading = 'eager';
          $image->fetchpriority = 'high';
          $image->decoding = 'async';
        } else {
          $image->loading = 'lazy';
          $image->fetchpriority = 'low';
        }

        self::$images[$key] = $image;
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    }
  }

  public static function responsive_images($html)
  {
    if (
      !Config::$config['img_responsive'] ||
      !Config::$config['cdn'] ||
      !Config::$config['cdn_url']
    ) {
      return $html;
    }

    // Get all images from the page
    $images = array_filter(self::$images, function ($image) {
      return strpos($image->src, site_url()) !== false;
    });

    try {
      foreach ($images as $key => $image) {
        // Skip images with loading="eager" attribute
        if ($image->loading === 'eager') {
          continue;
        }

        // Get src attribute
        $src = $image->src;

        // Skip SVG images
        if (strpos($src, '.svg') !== false) {
          continue;
        }

        // Set data-origin-src for responsive images (used by core.js)
        $image->{'data-origin-src'} = $src;

        // Set 1px transparent image as src
        $image->src = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

        // Remove srcset and sizes attributes
        unset($image->srcset);
        unset($image->sizes);

        self::$images[$key] = $image;
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    }
  }

  public static function localhost_gravatars($html)
  {
    if (!Config::$config['img_localhost_gravatar']) {
      return $html;
    }
    try {
      foreach (self::$images as $key => $image) {
        if (strpos($image->src, 'gravatar.com/avatar/') === false) {
          continue;
        }

        $gravatar_file_name = 'gravatar-' . substr(md5($image->src), 0, 12) . '.png';
        $gravatar_file_path = FLYING_PRESS_CACHE_DIR . $gravatar_file_name;

        if (!file_exists($gravatar_file_path)) {
          file_put_contents($gravatar_file_path, file_get_contents($image->src));
        }
        $image->src = FLYING_PRESS_CACHE_URL . $gravatar_file_name;
        self::$images[$key] = $image;
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    }
  }

  public static function write_images($html)
  {
    foreach (self::$images as $image) {
      $html = str_replace($image->original_tag, $image, $html);
    }
    return $html;
  }

  public static function lazy_load_bg_style($html)
  {
    if (!Config::$config['img_lazyload']) {
      return $html;
    }

    // Get excluded keywords
    $exclude_keywords = Config::$config['img_lazyload_excludes'];

    // Get all the elements with url in style attribute
    preg_match_all('/<[^>]+style=[\'"][^\'"]*url\([^)]+\)[^\'"]*[\'"][^>]*>/i', $html, $elements);

    try {
      // Loop through elements
      foreach ($elements[0] as $element_tag) {
        $element = new HTML($element_tag);

        // Continue if element is excluded
        if (Utils::any_keywords_match_string($exclude_keywords, $element_tag)) {
          continue;
        }

        // Lazy load bacgkround images by lazy loading style attribute
        $style = $element->style;
        $element->{'data-lazy-style'} = $style;
        $element->{'data-lazy-method'} = 'viewport';
        $element->{'data-lazy-attributes'} = 'style';
        unset($element->style);

        $html = str_replace($element_tag, $element, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function lazy_load_bg_class($html)
  {
    if (!Config::$config['img_lazyload']) {
      return $html;
    }

    // get all elements with lazy-bg class
    preg_match_all('/<[^>]+class=[\'"][^\'"]*lazy-bg[^\'"]*[\'"][^>]*>/i', $html, $elements);

    try {
      foreach ($elements[0] as $element_tag) {
        $element = new HTML($element_tag);

        // Lazy load class attribute
        $class = $element->class;
        $element->{'data-lazy-class'} = $class;
        $element->{'data-lazy-method'} = 'viewport';
        $element->{'data-lazy-attributes'} = 'class';
        unset($element->class);

        // Lazy load id attribute
        if ($element->id) {
          $id = $element->id;
          $element->{'data-lazy-id'} = $id;
          $element->{'data-lazy-attributes'} = 'class,id';
          unset($element->id);
        }

        $html = str_replace($element_tag, $element, $html);
      }
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  public static function preload($html)
  {
    if (!Config::$config['img_preload']) {
      return $html;
    }

    // Filter self::$images to get only images with loading="eager"
    $images = array_filter(self::$images, function ($image) {
      return $image->loading === 'eager';
    });

    $preload_images = [];

    try {
      foreach ($images as $image) {
        $src = $image->src;
        $srcset = $image->srcset;
        $sizes = $image->sizes;
        $preload_images[] = "<link rel='preload' href='$src' as='image' imagesrcset='$srcset' imagesizes='$sizes' />";
      }

      // Get unique preload tags
      $preload_images = array_unique($preload_images);

      // Convert array to string
      $preload_image_tags = implode(PHP_EOL, $preload_images);

      // Add preload tags after head tag opening
      $html = Utils::str_replace_first(
        '</title>',
        '</title>' . PHP_EOL . $preload_image_tags,
        $html
      );
    } catch (\Exception $e) {
      error_log($e->getMessage());
    } finally {
      return $html;
    }
  }

  private static function get_dimensions($url)
  {
    try {
      // Extract width if found the the url. For example something-100x100.jpg
      if (preg_match('/(?:.+)-([0-9]+)x([0-9]+)\.(jpg|jpeg|png|gif|svg)$/', $url, $matches)) {
        list($_, $width, $height) = $matches;
        return ['width' => $width, 'height' => $height];
      }

      // Get width and height for Gravatar images
      if (strpos($url, 'gravatar.com/avatar/') !== false) {
        $query_string = parse_url($url, PHP_URL_QUERY);
        parse_str($query_string ?? '', $query_vars);
        $size = $query_vars['s'] ?? 80;
        return ['width' => $size, 'height' => $size];
      }

      $file_path = Caching::get_file_path_from_url($url);

      if (!is_file($file_path)) {
        return false;
      }

      // Get width and height from svg
      if (pathinfo($file_path, PATHINFO_EXTENSION) === 'svg') {
        $xml = @simplexml_load_file($file_path);
        $attr = $xml->attributes();
        $viewbox = explode(' ', $attr->viewBox);
        $width =
          isset($attr->width) && preg_match('/\d+/', $attr->width, $value)
            ? (int) $value[0]
            : (count($viewbox) == 4
              ? (int) $viewbox[2]
              : null);
        $height =
          isset($attr->height) && preg_match('/\d+/', $attr->height, $value)
            ? (int) $value[0]
            : (count($viewbox) == 4
              ? (int) $viewbox[3]
              : null);
        if ($width && $height) {
          return ['width' => $width, 'height' => $height];
        }
      }

      // Get image size by checking the file
      list($width, $height) = getimagesize($file_path);
      if ($width && $height) {
        return ['width' => $width, 'height' => $height];
      }
    } catch (\Exception $e) {
      return false;
    }
  }
}
