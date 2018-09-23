<?php

namespace App\Exceptions;

use Slim\Http\Response;

class ResponseException extends \RuntimeException {
  /** @var Response */
  protected $response;

  public function __construct(Response $response) {
    parent::__construct();

    $this->response = $response;
  }

  public function getResponse(): Response {
    return $this->response;
  }
}
