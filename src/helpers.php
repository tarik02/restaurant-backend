<?php

if (!function_exists('clamp')) {
  function clamp(int $number, int $lower, int $upper) {
    return max($lower, min($upper, $number));
  }
}
