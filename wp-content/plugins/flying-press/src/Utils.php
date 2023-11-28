<?php

namespace FlyingPress;

class Utils
{
  public static function any_keywords_match_string($keywords, $string)
  {
    // Filter out empty elements
    $keywords = array_filter($keywords);

    foreach ($keywords as $keyword) {
      if (stripos($string, $keyword) !== false) {
        return true;
      }
    }

    return false;
  }

  public static function str_replace_first($search, $replace, $subject)
  {
    $pos = strpos($subject, $search);
    if ($pos !== false) {
      return substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
  }
}
