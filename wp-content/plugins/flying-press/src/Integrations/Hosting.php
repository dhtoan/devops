<?php

namespace FlyingPress\Integrations;

class Hosting
{
  public static function init()
  {
    Hosting\Kinsta::init();
    Hosting\RocketNet::init();
    Hosting\GridPane::init();
    Hosting\WPEngine::init();
    Hosting\RunCloud::init();
    Hosting\SpinupWP::init();
    Hosting\SiteGround::init();
  }
}
