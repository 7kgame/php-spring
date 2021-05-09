<?php
namespace PHPSpring\Utils;

class ProxyFactory {

  private static $annotations;
  private static $cacheDir = '.cache';
  private static $annosFileName = 'annos.php';

  private static array $proxyInsMap = array();

  public static function getProxy ($target) {
    $targetClassName = Proxy::getClassName($target);
    if (isset(self::$proxyInsMap[$targetClassName])) {
      return self::$proxyInsMap[$targetClassName];
    }
    $proxy = Proxy::getProxy($target);
    $ins = new $proxy($target);
    self::$proxyInsMap[$targetClassName] = $ins;
    return $ins;
  }

  public static function getAnnotations(string $className=null) {
    if(empty(self::$annotations)) {
      $cwd = getcwd();
      if (preg_match('/public/', $cwd)) {
        $cwd = dirname($cwd);
      }

      self::$annotations = require($cwd
          . DIRECTORY_SEPARATOR . self::$cacheDir
          . DIRECTORY_SEPARATOR . self::$annosFileName);
    }
    if ($className != null) {
      $className = explode('\\', $className);
      $className = strtolower(array_pop($className));
      return self::$annotations[$className] ?? null;
    } else {
      return self::$annotations;
    }
  }

}
