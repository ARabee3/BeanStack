<?php

class SessionHelper
{
  public static function flash(string $key, $value): void
  {
    $_SESSION[$key] = $value;
  }

  public static function pull(string $key, $default = null)
  {
    if (!array_key_exists($key, $_SESSION)) {
      return $default;
    }

    $value = $_SESSION[$key];
    unset($_SESSION[$key]);

    return $value;
  }
}
