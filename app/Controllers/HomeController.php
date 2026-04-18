<?php

class HomeController
{
  public static function index(): void
  {
    requireLogin();

    $rooms = $rooms ?? [];
    $products = $products ?? [];

    include __DIR__ . '/../../views/home.php';
  }
}
