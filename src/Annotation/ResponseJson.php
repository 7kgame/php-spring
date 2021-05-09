<?php

namespace PHPSpring\Annotation;

class ResponseJson {

  public static function after($res) {
    return json_encode($res);
  }
}
