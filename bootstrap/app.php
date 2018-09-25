<?php

require_once 'kernel.php';

// Instantiate the app
$app = new \Slim\App([
  'settings' => require config_path() . '/app.php',
]);

// Set up dependencies
require app_path() . '/dependencies.php';

// Apply helpers
require app_path() . '/helpers.php';

// Register middleware
require app_path() . '/middleware.php';

// Register routes
require app_path() . '/routes.php';

return $app;
