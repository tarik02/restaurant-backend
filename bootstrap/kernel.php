<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(base_path() . '/.env')) {
  $dotenv = new Dotenv(base_path());
  $dotenv->load();
  unset($dotenv);
}
