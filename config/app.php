<?php

return [
  'app' => [
    'name' => env('APP_NAME', 'Restaurant'),
  ],

  'googleMapsToken' => 'AIzaSyC7pSqaRYy0oTPNo1s7ujY9dCn-vunRHD8',

  'displayErrorDetails' => env('APP_ENV') === 'dev', // set to false in production
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

  'uploads' => [
    'path' => __DIR__ . '/../public/uploads/',
    'public' => '/',
  ],

  'db' => require 'db.php',
  'cors' => require 'cors.php',
  'oauth2' => require 'oauth2.php',

  'fake-date' => require 'fake-date.php',

  'installed' => env('INSTALLED', false),
];
