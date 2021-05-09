<?php

namespace PHPSpring\Utils;

class Proxy {

  private static array $proxyMap = array();

  private static string $proxyTpl = '
namespace ${namespace};

class ${className} extends ${targetClass} {
  private $target;

  public function __construct($target) {
    if (is_string($target)) {
      $target = new $target();
    }
    $this->target = $target;
  }

${methods}
}';

  public static function getProxy ($target) {
    $targetClassName = self::getClassName($target);

    if (isset(self::$proxyMap[$targetClassName])) {
      return self::$proxyMap[$targetClassName];
    }

    $proxy = self::genProxy($targetClassName);
    eval($proxy['class']);
    self::$proxyMap[$targetClassName] = $proxy['name'];
    return self::$proxyMap[$targetClassName];
  }

  private static function genProxy ($className) {
    $re = new \ReflectionClass($className);
    if ($re->isFinal()) {
      throw new \Error('无法代理Final类' . $className);
    }
    $methods = array();
    foreach($re->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
      if ($method->isFinal()){
        throw new Error('无法代理Final方法' . $method->name);
      }
      $retType = $method->getReturnType();
      if ($retType == null) {
        $retType = '';
      } else {
        $retType = ': \\' . $retType;
      }
      $params = array();
      $invokeArgs = array();
      foreach ($method->getParameters() as $param) {
        $type = $param->getType();
        if ($type == null) {
          $type = '';
        } else {
          if (!self::isPrimaryType($type)) {
            $type = '\\' . $type;
          }
          $type .= ' ';
        }
        $params[] = $type . '$' . $param->name;
        $invokeArgs[] = '$' . $param->name;
      }
      $paramStr = implode(', ', $params);
      $invokeArgsStr = implode(', ', $invokeArgs);
      $methodName = $method->name;
      $ns = __NAMESPACE__;
      $methods[] = "  public function $methodName ($paramStr) $retType {" . PHP_EOL
                 . "    return \\$ns\\AOP::invoke(\$this->target, '$methodName',  array($invokeArgsStr));" . PHP_EOL
                 . "  }";
    }
    $replace = array(
      '${namespace}' => "$ns\\Proxys",
      '${className}' => $re->getShortName() . 'Proxy',
      '${targetClass}' => $className,
      '${methods}' => join(PHP_EOL . PHP_EOL, $methods),
    );
    $proxyClass = str_replace(array_keys($replace), array_values($replace), self::$proxyTpl);
    // var_dump($proxyClass);
    return array(
      'name' => '\\' . $replace['${namespace}'] . '\\' . $replace['${className}'],
      'class' => $proxyClass);
  }

  public static function getClassName ($target) {
    if (is_object($target)) {
      $targetClassName = get_class($target);
    } else {
      $targetClassName = $target;
    }
    if (substr($targetClassName, 0, 1) != '\\') {
      $targetClassName = '\\' . $targetClassName;
    }
    return $targetClassName;
  }

  public static function isPrimaryType($type) {
    return ($type == null || in_array($type, array('int', 'bool', 'float', 'string', 'array')));
  }

}
