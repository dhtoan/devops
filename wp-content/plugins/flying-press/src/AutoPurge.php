<?php

namespace FlyingPress;

class AutoPurge
{
  public static function init()
  {
    // Purge when scheduled post is published
    add_action('future_to_publish', function ($post) {
      self::post_updated($post->ID, $post, $post);
    });

    // Purge cache on updating post
    add_action('post_updated', [__CLASS__, 'post_updated'], 10, 3);

    // Preload post URL when comment count is updated
    add_action('wp_update_comment_count', [__CLASS__, 'preload_on_comment']);
  }

  public static function post_updated($post_id, $post_after, $post_before)
  {
    // Get the status of the post after and before the update
    $post_after_status = get_post_status($post_after);
    $post_before_status = get_post_status($post_before);

    // If both post statuses are not 'publish', return early
    if (!in_array('publish', [$post_after_status, $post_before_status])) {
      return;
    }

    // Delete preload URLs transient
    delete_transient('flying_press_preload_urls');

    $urls = [];

    // Add URLs of post before and after the update
    if ($post_before_status == 'publish') {
      $urls[] = get_permalink($post_before);
    }
    if ($post_after_status == 'publish') {
      $urls[] = get_permalink($post_after);
    }

    // Add home URL
    $urls[] = home_url();

    // URls of categories
    $categories = get_the_category($post_id);
    foreach ($categories as $category) {
      $urls[] = get_category_link($category->term_id);
    }

    // URLs of tags
    $tags = get_the_tags($post_id);
    if ($tags) {
      foreach ($tags as $tag) {
        $urls[] = get_tag_link($tag->term_id);
      }
    }

    // Posts page (blog archive)
    $posts_page = get_option('page_for_posts');
    if ($posts_page) {
      $urls[] = get_permalink($posts_page);
    }

    // Add author profile URL
    $author_id = get_post_field('post_author', $post_id);
    $urls[] = get_author_posts_url($author_id);

    // Add URLs from filter
    $urls = apply_filters('flying_press_auto_purge_urls', $urls, $post_id);

    // Get unique URLs
    $urls = array_unique($urls);

    Purge::purge_urls($urls);
    Preload::preload_urls($urls);
  }

  public static function preload_on_comment($post_id)
  {
    $url = get_permalink($post_id);
    Purge::purge_url($url);
    Preload::preload_url($url);
  }
}
