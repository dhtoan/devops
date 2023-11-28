<?php

namespace FlyingPress\Optimizer;

class HTML
{
  public $original_tag;
  private $tag;
  private $self_closing;

  public function __construct($tag)
  {
    $this->original_tag = $tag;
    $this->tag = $tag;
    $this->self_closing = preg_match('/\/>$/', $tag) > 0;
  }

  public function getContent()
  {
    if ($this->self_closing) {
      return '';
    }

    $tag_name = $this->getTagName();
    $start = strpos($this->tag, '>') + 1;
    $end = strrpos($this->tag, "</$tag_name>");
    return trim(substr($this->tag, $start, $end - $start));
  }

  public function setContent($content)
  {
    if ($this->self_closing) {
      return;
    }

    $tag_name = $this->getTagName();
    $start = strpos($this->tag, '>') + 1;
    $end = strrpos($this->tag, "</$tag_name>");
    $this->tag = substr_replace($this->tag, $content, $start, $end - $start);
    return true;
  }

  public function __get($attribute)
  {
    if (preg_match("/$attribute=([\"'])(.*?)\\1/", $this->tag, $matches)) {
      return $matches[2];
    } elseif (preg_match("/\s$attribute(\s|>)/", $this->tag)) {
      return true;
    }
    return null;
  }

  public function __set($attribute, $value = null)
  {
    if (strpos($value, '"') !== false) {
      $attribute_string = $value === true ? $attribute : "$attribute='$value'";
    } else {
      $attribute_string = $value === true ? $attribute : "$attribute=\"$value\"";
    }

    $this->tag = $this->$attribute
      ? preg_replace(
        "/\s$attribute(=(\"|').*?(\\2)(?=\s|>|\/))?(?:(?=[^\w]))/",
        " $attribute_string",
        $this->tag
      )
      : preg_replace('/(>|\/>)/', " $attribute_string$1", $this->tag, 1);
  }

  public function __unset($attribute)
  {
    $this->tag = preg_replace("/\s$attribute(=(\"|').*?\\2)(?=\s|>|\/)/", '', $this->tag);
  }

  public function getTagName()
  {
    preg_match('/<(.*?)[\s|>]/', $this->tag, $matches);
    return $matches[1];
  }

  public function __toString()
  {
    return $this->tag;
  }
}
