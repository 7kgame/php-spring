<?php

namespace PHPSpring\Utils;

class File {

  public static function readDir ($dir, $callback=null, $pattern='', $withFullPath=true, $fullPath='') {
    $result = array();
    $cdir = scandir($dir);
    foreach ($cdir as $key => $value) {
      if (!in_array($value, array(".", ".."))) {
        if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
          $path = $value;
          if ($withFullPath && !empty($fullPath)) {
            $path = $fullPath . DIRECTORY_SEPARATOR . $value;
          }
          $result[$path] = self::readDir($dir . DIRECTORY_SEPARATOR . $value, $callback, $pattern, $withFullPath, $path);
        } else {
          if (empty($pattern) || preg_match($pattern, $value)) {
            $result[] = $value;
            if (is_callable($callback)) {
              $path = rtrim($fullPath, DIRECTORY_SEPARATOR);
              $callback($path, $value);
            }
          }
        }
      }
    }
    return $result;
  }

}
