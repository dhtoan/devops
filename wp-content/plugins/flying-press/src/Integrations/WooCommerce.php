<?php

namespace FlyingPress\Integrations;

use FlyingPress\Purge;
use FlyingPress\Preload;
use FlyingPress\AutoPurge;

class WooCommerce
{
  public static function init()
  {
    // Exclude cart, checkout, and account pages from cache
    add_filter('flying_press_is_cacheable', [__CLASS__, 'is_cacheable']);

    // Add URLs to purge when a product is updated
    add_filter('flying_press_auto_purge_urls', [__CLASS__, 'auto_purge_urls'], 10, 2);

    // Stock updated
    add_action('woocommerce_product_set_stock', [__CLASS__, 'purge_product']);
    add_action('woocommerce_variation_set_stock', [__CLASS__, 'purge_product']);

    // Product updated via batch rest API
    add_action('woocommerce_rest_insert_product_object', [__CLASS__, 'purge_product']);
  }

  public static function is_cacheable($is_cacheable)
  {
    if (!class_exists('woocommerce')) {
      return $is_cacheable;
    }

    // If the current page is a WooCommerce cart, checkout, or account page, return false
    if (is_cart() || is_checkout() || is_account_page()) {
      return false;
    }

    return $is_cacheable;
  }

  public static function auto_purge_urls($urls_to_purge, $post_id)
  {
    if (!class_exists('woocommerce')) {
      return $urls_to_purge;
    }

    // Check if post is a product
    $post_type = get_post_type($post_id);
    if ($post_type !== 'product') {
      return $urls_to_purge;
    }

    // Add shop page URL
    $urls_to_purge[] = get_permalink(wc_get_page_id('shop'));

    // Add product category URLs
    $product_categories = get_the_terms($post_id, 'product_cat');
    foreach ($product_categories as $product_category) {
      $urls_to_purge[] = get_term_link($product_category);
      $parent_categories = get_ancestors($product_category->term_id, 'product_cat');
      foreach ($parent_categories as $parent_category) {
        $urls_to_purge[] = get_term_link($parent_category);
      }
    }

    return $urls_to_purge;
  }

  public static function purge_product($product)
  {
    // Get product URL
    $product_id = $product->get_id();
    $product_url = get_permalink($product_id);

    Purge::purge_url($product_url);
    Preload::preload_url($product_url);
  }
}
