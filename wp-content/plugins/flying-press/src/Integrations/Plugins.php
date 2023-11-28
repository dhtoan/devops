<?php

namespace FlyingPress\Integrations;

class Plugins
{
  public static function init()
  {
    Plugins\Optimization\SiteGround::init();
    Plugins\MultiCurrency\WCML::init();
  }
}
