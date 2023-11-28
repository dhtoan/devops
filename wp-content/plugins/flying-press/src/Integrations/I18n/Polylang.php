<?php

namespace FlyingPress\Integrations\I18n;

class Polylang
{
  public static function init()
  {
    // Check if Polylang is active
    if (!defined('POLYLANG_VERSION')) {
      return;
    }

    // Filter URLs on preloading all URLs
    add_filter('flying_press_preload_urls', [__CLASS__, 'add_translated_urls'], 10, 1);

    // Filter URLs on auto purging URLs
    add_filter('flying_press_auto_purge_urls', [__CLASS__, 'add_translated_urls'], 10, 1);
  }

  public static function add_translated_urls($urls)
  {
    // Get Polylang languages
    $languages = \pll_languages_list();

    // Get home URLs for each language
    foreach ($languages as $language) {
      $urls[] = \pll_home_url($language);
    }

    // Get translated URLs for each post
    foreach ($urls as $url) {
      $translations = \pll_get_post_translations(\url_to_postid($url));
      foreach ($translations as $translation) {
        $urls[] = \get_permalink($translation);
      }
    }

    // Get translation of each taxonomy
    foreach ($urls as $url) {
      $postid = \url_to_postid($url);
      $taxonomies = get_object_taxonomies(get_post_type($postid));

      // Get translation for each term of each taxonomy
      foreach ($taxonomies as $taxonomy) {
        $terms = get_the_terms($postid, $taxonomy);
        if (!is_array($terms)) {
          continue;
        }
        foreach ($terms as $term) {
          $translations = \pll_get_term_translations($term->term_id);
          foreach ($translations as $translation) {
            $urls[] = \get_term_link($translation);
          }
        }
      }
    }

    $urls = array_unique($urls);

    return $urls;
  }
}
