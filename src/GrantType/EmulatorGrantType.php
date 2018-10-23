<?php

namespace App\GrantType;

use App\Services\UsersService;
use OAuth2\GrantType\GrantTypeInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
use OAuth2\ResponseType\AccessTokenInterface;
use Slim\Container;

class EmulatorGrantType implements GrantTypeInterface {
  /**
   * @var array
   */
  private $userInfo;

  /** @var UsersService */
  private $users;

  public function __construct(Container $container) {
    $this->users = $container['users'];
  }

  public function getQueryStringIdentifier() {
    return 'emulator';
  }

  public function validateRequest(RequestInterface $request, ResponseInterface $response) {
    if (
      !$request->request('password') ||
      !$request->request('username') ||
      !$request->request('roles')
    ) {
      $response->setError(400, 'invalid_request', 'Missing parameters: "username", "password" and "roles" required');

      return null;
    }

    if ($id = $this->users->register([
      'username' => $request->request('username'),
      'password' => $request->request('password'),
      'email' => $request->request('email'),
      'phone' => $request->request('phone'),
      'roles' => json_decode($request->request('roles'), false),
    ])) {
      $userInfo = [
        'user_id' => $id,
        'scope' => 'test',
      ];
    } else if ($this->users->checkUserCredentials($request->request('username'), $request->request('password'))) {
      $userInfo = $this->users->getUserDetails($request->request('username'));
    } else {
      $response->setError(401, 'invalid_grant', 'Invalid username and password combination');

      return null;
    }

    if (empty($userInfo)) {
      $response->setError(400, 'invalid_grant', 'Unable to retrieve user information');

      return null;
    }

    if (!isset($userInfo['user_id'])) {
      throw new \LogicException("you must set the user_id on the array returned by getUserDetails");
    }

    $this->userInfo = $userInfo;

    return true;
  }

  public function getClientId() {
    return null;
  }

  public function getUserId() {
    return $this->userInfo['user_id'];
  }

  public function getScope() {
    return isset($this->userInfo['scope']) ? $this->userInfo['scope'] : null;
  }

  public function createAccessToken(AccessTokenInterface $accessToken, $client_id, $user_id, $scope) {
    return $accessToken->createAccessToken($client_id, $user_id, $scope);
  }
}