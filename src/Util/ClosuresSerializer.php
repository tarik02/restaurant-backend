<?php

namespace App\Util;

use SuperClosure\SerializableClosure;
use SuperClosure\SerializerInterface;

class ClosuresSerializer {
  /**
   * Recursively traverses and wraps all Closure objects within the value.
   *
   * NOTE: THIS MAY NOT WORK IN ALL USE CASES, SO USE AT YOUR OWN RISK.
   *
   * @param mixed $data Any variable that contains closures.
   * @param SerializerInterface $serializer The serializer to use.
   */
  public static function wrapClosures(&$data, SerializerInterface $serializer)
  {
    if ($data instanceof \Closure) {
      // Handle and wrap closure objects.
      /** @noinspection PhpUnhandledExceptionInspection */
      $reflection = new \ReflectionFunction($data);
      if ($binding = $reflection->getClosureThis()) {
        self::wrapClosures($binding, $serializer);
        $scope = $reflection->getClosureScopeClass();
        $scope = $scope ? $scope->getName() : 'static';
        $data = $data->bindTo($binding, $scope);
      }
      $data = new SerializableClosure($data, $serializer);
    } elseif (is_array($data) || $data instanceof \stdClass || $data instanceof \Traversable) {
      // Handle members of traversable values.
      foreach ($data as &$value) {
        self::wrapClosures($value, $serializer);
      }
    } elseif (is_object($data) && !$data instanceof \Serializable) {
      // Handle objects that are not already explicitly serializable.
      $reflection = new \ReflectionObject($data);
      if (!$reflection->hasMethod('__sleep')) {
        foreach ($reflection->getProperties() as $property) {
          if ($property->isPrivate() || $property->isProtected()) {
            $property->setAccessible(true);
          }
          $value = $property->getValue($data);
          if (self::wrapClosures($value, $serializer)) {
            $property->setValue($data, $value);
          }
        }
      }
    } else {
      return false;
    }

    return true;
  }
}
