<?php

namespace PHPSpring\Utils;

class AnnotationParser {

  private static $ANNO_IMPLS = array();

  public static function parse($clz) {
    $re  = new \ReflectionClass($clz);
    if ($re->isAbstract() || $re->isInterface()) {
      return;
    }
    $clzAnnos = self::getAnnotations($re->getDocComment());

    $methodsAnnos = array();
    foreach($re->getMethods() as $method) {
      $methodAnnos = self::getAnnotations($method->getDocComment());
      if (empty($methodAnnos)) {
        continue;
      }
      $retType = $method->getReturnType();
      $params = $method->getParameters();
      $paramsWithType = array();
      if (!empty($params)) {
        foreach($params as $param) {
          $type = $param->getType();
          $paramsWithType[] = array(
            'name' => $param->getName(),
            'type' => empty($type) ? null : $type->__toString(),
            'default' => ($param->isDefaultValueAvailable()) ? $param->getDefaultValue() : null,
            'optional' => $param->isOptional()
          );
        }
      }
      $methodsAnnos[$method->getName()] = array(
        'name' => $method->getName(),
        'type' => empty($retType) ? null : $retType->__toString(),
        'annos' => $methodAnnos,
        'params' => $paramsWithType
      );
    }

    $propertiesAnnos = array();
    foreach($re->getProperties() as $property) {
      $propertyAnnos = self::getAnnotations($property->getDocComment());
      if (empty($propertyAnnos)) {
        continue;
      }
      $type = $property->getType();
      $propertiesAnnos[$property->getName()] = array(
        'name' => $property->getName(),
        'type' => empty($type) ? null : $type->__toString(),
        'annos' => $propertyAnnos
      );
    }

    if (!empty($clzAnnos) || !empty($methodsAnnos) || !empty($propertiesAnnos)) {
      $annos = array(
        'class' => array(
                    'name' => $re->getShortName(),
                    'type' => $clz,
                    'annos' => $clzAnnos),
        'methods' => $methodsAnnos,
        'properties' => $propertiesAnnos);
      return $annos;
    }
    return;
  }

  private static function getAnnotations ($comment) {
    if (empty($comment)) {
      return;
    }
    $comment = trim(preg_replace('/^\/\*\*|\*\//', '', $comment));
    preg_match_all('/\s*\**\s*(.*)/', $comment, $matches);
    if (count($matches) < 2) {
      return;
    }
    $comment = implode('', $matches[1]);
    $comment = explode('@', preg_replace('/^@*|@*$/', '', $comment));
    $annoImpls = self::getAnnotationImplements();
    $annos = array();
    foreach ($comment as $anno) {
      $funcSign = self::annoToFuncSign($anno);
      if (empty($funcSign) || !in_array($funcSign[0], self::$ANNO_IMPLS)) {
        continue;
      }
      $annos[$funcSign[0]] = $funcSign[1];
    }
    return $annos;
  }

  private static function annoToFuncSign ($anno) {
    preg_match('/^(\w+)(\(.*\))?/', $anno, $funcMatches);
    if (count($funcMatches) < 1) {
      return null;
    }
    $funcName = $funcMatches[1];
    $paramStr = count($funcMatches) > 2 ? $funcMatches[2] : '';
    if ($paramStr) {
      $paramStr = substr($paramStr, 1, -1);
    }
    $len = mb_strlen($paramStr);
    $params = array();
    $quote = '';
    $start = true;
    $param = '';
    for ($i = 0; $i < $len; $i++) {
      $ch = mb_substr($paramStr, $i, 1);
      if ($start) {
        if ($ch == ' ') {
          continue;
        }
        $start = false;
        if ($ch == '"' || $ch == "'") {
          $quote = $ch;
          continue;
        }
      } else {
        if (!empty($quote)) {
          if ($ch == $quote) {
            if (mb_substr($paramStr, $i-1, 1) != '\\'
              && ($i == $len-1 || mb_substr($paramStr, $i+1, 1) == ',')) {
              $params[] = trim($param);
              $start = true;
              $quote = '';
              $param = '';
              $i++;
              continue;
            }
          }
        } else {
          if ($ch == ',') {
            $params[] = trim($param);
            $start = true;
            $quote = '';
            $param = '';
            continue;
          }
        }
      }
      $param .= $ch;
    }
    if (!empty($param)) {
      $params[] = trim($param);
    }
    return array($funcName, $params);
  }

  private static function getAnnotationImplements () {
    if (empty(self::$ANNO_IMPLS)) {
      $annos = array();
      $files = scandir(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Annotation');
      foreach ($files as $file) {
        if (preg_match('/\.php/i', $file)) {
          $annos[] = substr($file, 0, -4);
        }
      }
      self::$ANNO_IMPLS = $annos;
    }
    return self::$ANNO_IMPLS;
  }

}
