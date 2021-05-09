<?php

namespace PHPSpring\Utils;

use PHPSpring\Annotation\Autowired;

class AOP {

  public static function invoke($target, $method, $args) {
    $targetAnnos = ProxyFactory::getAnnotations(get_class($target));

    // TODO: get interceptors

    // TODO: call $interceptor->before()

    // TODO: call class annotations before()
    // TODO: call method annotations before()
    // TODO: init properties
    $propertiesAnnos = (empty($targetAnnos) || empty($targetAnnos['properties'])) ? [] : $targetAnnos['properties'];
    foreach ($propertiesAnnos as $property => $annos) {
      if ($annos['annos'] && isset($annos['annos']['Autowired'])) {
        $rf = new \ReflectionProperty($target, $property);
        $rf->setAccessible(true);
        $rf->setValue($target, ProxyFactory::getProxy($annos['type']));
      }
    }
    $res = $target->$method(...$args);
    // TODO: call class annotations after()
    // TODO: call method annotations after()

    // TODO: call $interceptor->after()
    return $res;
  }
}
