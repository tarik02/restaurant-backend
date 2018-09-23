<?php

use Phpmig\Migration\Migration;
use OAuth2\Storage\Pdo as PdoStorage;

class Oauth2 extends Migration {
  public function up() {
    /** @var \Illuminate\Database\Capsule\Manager $db */
    $db = $this->container['db'];
    $storage = $this->container['oauth2-storage'];
    $connection = $db->getConnection();
    $pdo = $connection->getPdo();

    if ($storage instanceof PdoStorage) {
      foreach (explode(';', $storage->getBuildSql()) as $statement) {
        $pdo->exec($statement);
      }
    }
  }

  public function down() {
    //
  }
}
