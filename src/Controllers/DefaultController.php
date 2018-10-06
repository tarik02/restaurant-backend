<?php

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;
use Slim\Http\Request;
use Slim\Http\Response;

class DefaultController extends Controller {
  public function courses(Request $request, Response $response, array $args) {
    $result = DB::table('courses')
      ->where('visible', 1)
      ->orderBy('id', 'desc')
      ->get();
    $coursesIds = $result->pluck('id');

    $images = DB::table('courses_images')
      ->whereIn('course_id', $coursesIds)
      ->get()
      ->groupBy('course_id');

    return $response->withJson([
      'data' => $result->map(function(array $course) use($images, $request) {
        $id = intval($course['id']);

        /** @var Collection|null $courseImages */
        $courseImages = $images[$id] ?? null;

        return [
          'id' => $id,
          'title' => $course['title'],
          'description' => $course['description'],
          'images' => $courseImages !== null
            ? collect($courseImages)->pluck('src')->toArray()
            : [],
          'price' => intval($course['price']),
        ];
      }),
    ]);
  }
}
