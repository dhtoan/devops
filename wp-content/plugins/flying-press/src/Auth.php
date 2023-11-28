<?php

namespace FlyingPress;

class Auth
{
  public static function is_allowed()
  {
    $current_user = wp_get_current_user();
    $allowed_roles = apply_filters('flying_press_allowed_roles', ['administrator', 'editor']);

    // Only allow access to the REST API for users with the specified roles
    if (!array_intersect($current_user->roles, $allowed_roles)) {
      return false;
    }

    return true;
  }
}
