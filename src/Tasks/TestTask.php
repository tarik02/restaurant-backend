<?php

namespace App\Tasks;

class TestTask extends Task {
  function run(): void {
    echo 'Hi!', PHP_EOL;
  }
}
