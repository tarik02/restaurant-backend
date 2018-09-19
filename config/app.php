<?php

return [
  'displayErrorDetails' => true, // set to false in production
  'addContentLengthHeader' => false, // Allow the web server to send the content-length header
  'determineRouteBeforeAppMiddleware' => false,
  'displayErrorDetails' => true,

  // Renderer settings
  'renderer' => [
    'template_path' => __DIR__.'/../templates/',
  ],

  // Monolog settings
  'logger' => [
    'name' => 'slim-app',
    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__.'/../logs/app.log',
    'level' => \Monolog\Logger::DEBUG,
  ],

  'db' => require_once 'db.php',

  'cors' => [
    'headers.allow' => ['content-type'],
  ],

  'uploads' => [
    'path' => __DIR__ . '/../public/uploads/',
    'public' => '/',
  ],
];