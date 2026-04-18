<?php

class ProductController
{
  public static function index(): void
  {
    requireAdmin();

    $products = $products ?? [];

    include __DIR__ . '/../../views/products/all_products.php';
  }
}
