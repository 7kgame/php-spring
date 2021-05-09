<?php

namespace PHPSpring\Web;

abstract class BaseDao {

  private string $entityClass;

  protected function setEntity(string $entityClass) {
    $this->entityClass = $entityClass;
  }

  protected function entityToObject ($entity) {
    // TODO
  }

  protected function objectToEntity ($object) {
    // $entity = new $this->entityClass;
    $user = new \App\Entity\Account\User();
    $user->name = $object['name'];
    $user->age = $object['age'];
    return $user;
  }

  public function get($id) {
    $user = array(
      'name' => '张三',
      'age'  => 19
    );
    return $this->objectToEntity($user);
  }

  public function query ($sql, $params) {
    $obj = array(
      'name' => '张三',
      'age'  => 19
    );
    return $this->objectToEntity($obj);
  }

}
