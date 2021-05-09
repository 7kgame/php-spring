<?php
namespace PHPSpring\Annotation;

use PHPSpring\Utils\Proxy;
use PHPSpring\Utils\ProxyFactory;

class Controller {

  public static function init ($callback) {
    if (!is_callable($callback)) {
      return;
    }
    $allAnnos = ProxyFactory::getAnnotations();
    if (empty($allAnnos)) {
      return;
    }
    foreach ($allAnnos as $clz => $clzAnnos) {
      $classAnnos = $clzAnnos['class'];
      if (empty($classAnnos['annos'])
          || !isset($classAnnos['annos']['Controller'])) {
        continue;
      }
      $prePath = '';
      if (isset($classAnnos['annos']['RequestMapping'])) {
        $prePath = implode('/', $classAnnos['annos']['RequestMapping']);
      }
      $methodsAnnos = $clzAnnos['methods'];
      foreach ($methodsAnnos as $method => $methodAnnos) {
        if (empty($methodAnnos['annos'])) {
          continue;
        }
        $path = $prePath . implode('/', $methodAnnos['annos']['RequestMapping']);
        $controller = $classAnnos['type'];
        $methodArgs = array();
        if (!empty($methodAnnos['params'])) {
          foreach ($methodAnnos['params'] as $p) {
            $methodArgs[] = $p['name'];
          }
        }

        $callback('get', $path, $methodArgs, function ($requestParams) use ($methodAnnos, $controller, $clzAnnos) {
          $ins = ProxyFactory::getProxy($controller);
          $method = $methodAnnos['name'];
          $funcParams = $methodAnnos['params'];
          $params = array();
          if (!empty($methodAnnos['params'])) {
            foreach ($methodAnnos['params'] as $p) {
              $v = self::setValue($requestParams, $p['name'], $p['type'], $p['default']);
              $params[] = $v;
            }
          }
          $res = $ins->$method(...$params);
          if (is_object($res)) {
            $res = json_encode($res);
          }
          return $res;
        });
      }
    }
  }

  private static function setValue ($params, $name, $type, $default=null) {
    if (Proxy::isPrimaryType($type)) {
      $v = $params[$name];
      if ($v === null) {
        $v = $p['default'];
      } else {
        if (in_array($type, array('int', 'float'))) {
          if (!is_numeric($v)) {
            // TODO throw type error
          }
          $v = $v - 0;
        } else if ($type == 'bool') {
          $v = $v ? true : false;
        }
      }
      return $v;
    } else if (class_exists($type)) {
      $ins = new $type;
      $re = new \ReflectionClass($ins);
      $properties = $re->getProperties();
      foreach ($properties as $property) {
        $pt = $property->getType();
        if ($pt != null) {
          $pt = $pt->__toString();
        }
        $property->setAccessible(true);
        $property->setValue($ins, self::setValue($params, $property->name, $pt));
      }
      return $ins;
    }
  }

}
