<?php
/** @var string $name */
/** @var string $table */
/** @var boolean $update */

/** @var string $class */
/** @var string $column */
?>
<?php echo '<?php', PHP_EOL; ?>

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class <?=$class?> extends Migration {
  /**
   * Do the migration
   */
  public function up() {
<?php if (!$update): ?>
    Capsule::schema()->create('<?=addslashes($table)?>', function(Blueprint $table) {
      $table->increments('id');
      $table->timestamps();
    });
<?php else: ?>
    Capsule::schema()->table('<?=addslashes($table)?>', function(Blueprint $table) {
      $table->string('<?=addslashes($column)?>');
    });
<?php endif ?>
  }
  
  /**
   * Undo the migration
   */
  public function down() {
<?php if (!$update): ?>
    Capsule::schema()->drop('<?=addslashes($table)?>');
<?php else: ?>
    Capsule::schema()->table('<?=addslashes($table)?>', function(Blueprint $table) {
      $table->dropColumn('<?=addslashes($column)?>');
    });
<?php endif ?>
  }
}
