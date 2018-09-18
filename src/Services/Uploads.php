<?php

namespace App\Services;

use Psr\Container\ContainerInterface;
use Slim\Http\UploadedFile;

class Uploads {
  /** @var ContainerInterface */
  private $container;

  /** @var string */
  private $path;

  /** @var string */
  private $public;

  public function __construct(ContainerInterface $container) {
    $this->container = $container;

    $settings = $this->container->get('settings')['uploads'];
    $this->path = rtrim($settings['path'], '/');
    $this->public = rtrim($settings['public'], '/');
  }

  public function getPath(): string {
    return $this->path;
  }

  public function getPublic(): string {
    return $this->public;
  }

  public function getFilename(UploadedFile $file) {
    return time() . '-' . $file->getClientFilename();
  }

  public function getDirectory(UploadedFile $file) {
    return date('Ymd');
  }

  public function getLocalPath(UploadedFile $file): string {
    return implode('/', [
      $this->getPath(),
      $this->getDirectory($file),
      $this->getFilename($file),
    ]);
  }

  public function getPublicPath(UploadedFile $file): string {
    return implode('/', [
      $this->getPublic(),
      $this->getDirectory($file),
      $this->getFilename($file),
    ]);
  }

  /**
   * @param UploadedFile $file
   * @return string Public path for the uploaded file
   */
  public function upload(UploadedFile $file): string {
    $localPath = $this->getLocalPath($file);
    mkdir(dirname($localPath), 0777, true);
    $file->moveTo($localPath);

    return $this->getPublicPath($file);
  }
}
