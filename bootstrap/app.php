<?php

require_once 'kernel.php';

session_start();

// Instantiate the app
$app = new \Slim\App([
  'settings' => require config_path() . '/app.php',
]);

// Set up dependencies
require app_path() . '/dependencies.php';
