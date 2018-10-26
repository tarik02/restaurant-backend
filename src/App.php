<?php

namespace App;

use Slim\Collection;
use Slim\Container;

class App extends \Slim\App {
  /** @var Collection */
  private $defaultConfig;
  private $config;

  public function __construct(array $config) {
    parent::__construct();

    /** @var Container $container */
    $container = $this->getContainer();

    $this->defaultConfig = $container->raw('settings')();
    $this->config = array_merge($this->defaultConfig->all(), $config);
//    $this->config = $config;

    $container['settings'] = $container->factory(function () {
      return $this->config;
    });
//    $container->extend('settings', function (Collection $old, Container $container) {
//      return $container->factory(function () use ($old) {
//        return array_merge($old->all(), $this->config);
//      });
//    });
  }

  /**
   * @return array
   */
  public function getConfig(): array {
    return $this->config;
  }

  /**
   * @param array $config
   */
  public function setConfig(array $config): void {
    $this->config = array_merge($this->defaultConfig->all(), $config);
//    $this->config = $this->defaultConfig->all();
  }
}