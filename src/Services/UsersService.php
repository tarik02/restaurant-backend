<?php

namespace App\Services;

use Chadicus\Slim\OAuth2\Middleware\Authorization;
use Illuminate\Database\Capsule\Manager as DB;
use OAuth2\Storage\UserCredentialsInterface;
use Slim\Container;
use Slim\Http\Request;

class UsersService implements UserCredentialsInterface {
  const ATTRIBUTE_KEY_USER = 'user-service.user';

  public function __construct(Container $container) {
  }

  public function register(array $user): int {
    $id = DB::table('users')->insertGetId([
      'username' => $user['username'],
      'email' => $user['email'] ?? null,
      'phone' => $user['phone'] ?? null,
      'password' => $this->hashPassword($user, $user['password']),
      'roles' => json_encode(['user']),
    ]);

    return $id;
  }

  public function exists(?string $username, ?string $email, ?string $phone) {
    $builder = DB::table('users');

    if ($username !== null) {
      $builder->orWhereRaw('LOWER(username) = LOWER(?)', [$username]);
    }

    if ($email !== null) {
      $builder->orWhereRaw('LOWER(email) = LOWER(?)', [$email]);
    }

    if ($phone !== null) {
      $builder->orWhere('phone', $phone);
    }

    return $builder->exists();
  }

  public function toUser(?array $user): ?array {
    if ($user !== null) {
      $user['id'] = intval($user['id']);
      $user['roles'] = json_decode($user['roles'] ?? '["user"]', true);
    }

    return $user;
  }

  public function get(int $id): ?array {
    return $this->toUser(
      DB::table('users')
        ->where('id', $id)
        ->first()
    );
  }

  public function getUser(string $username) {
    /** @var array|null $user */
    $user = DB::table('users')
      ->orWhereRaw('LOWER(username) = LOWER(?)', [$username])
      ->orWhereRaw('LOWER(email) = LOWER(?)', [$username])
      ->orWhere('phone', $username)
      ->first()
    ;

    return $this->toUser($user);
  }

  public function hashPassword(array $user, string $password) {
    $salt = $user['username'];

    return crypt($password, $salt);
  }

  public function checkUserCredentials($username, $password) {
    $user = $this->getUser($username);
    if ($user === null) {
      return false;
    }

    return $this->hashPassword($user, $password) === $user['password'];
  }

  public function getUserDetails($username) {
    $user = $this->getUser($username);
    if ($user === null) {
      return false;
    }

    return [
      'user_id' => $user['id'],
      'scope' => 'test',
    ];
  }

  public function getUserFromRequest(Request $request): ?array {
    if (null !== $user = $request->getAttribute(self::ATTRIBUTE_KEY_USER)) {
      return $user;
    }

    $data = $request->getAttribute(Authorization::TOKEN_ATTRIBUTE_KEY);
    if ($data === null) {
      return null;
    }

    $id = intval($data['user_id']);
    $user = $this->get($id);

    return $user;
  }
}
