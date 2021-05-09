<?php

namespace PHPSpring\Utils;

class Scanner {

  private static $ANNOS = array();

  public static function scan ($path, $cacheDir, $classPrefix='') {
    $files = File::readDir(
      $path
      , function ($path, $file) use($classPrefix) {
        $clzName = substr($file, 0, -4);
        $clz = $clzName;
        if (!empty($path)) {
          $clz = $path . DIRECTORY_SEPARATOR . $clz;
        }
        $clz = str_replace(DIRECTORY_SEPARATOR, '\\', $clz);
        if (!empty($classPrefix)) {
          $clz = $classPrefix . '\\' . $clz;
        }
        $annos = AnnotationParser::parse($clz);
        if (!empty($annos)) {
          self::$ANNOS[strtolower($clzName)] = $annos;
        }
      }
      , '/\.php$/i');
    if (!file_exists($cacheDir)) {
      mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'annos.php';
    $content = "<?php\nreturn " . var_export(self::$ANNOS, true) . ";\n";
    file_put_contents($cacheFile, $content);
  }
}