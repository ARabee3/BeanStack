<?php

/**
 * ProductController.php
 * Handles all product CRUD operations.
 * Place in: app/Controllers/ProductController.php
 */

require_once __DIR__ . '/../../config/Database.php';

class ProductController
{
    // ------------------------------------------------------------------ //
    //  Helpers
    // ------------------------------------------------------------------ //

    private static function db(): PDO
    {
        return Database::connect();
    }

    private static function redirect(string $page, array $params = []): void
    {
        $query   = http_build_query(array_merge(['page' => $page], $params));
        $base    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        header("Location: {$base}/index.php?{$query}");
        exit;
    }

    private static function flashError(string $msg): void
    {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => $msg];
    }

    private static function flashSuccess(string $msg): void
    {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
    }

    // ------------------------------------------------------------------ //
    //  Upload helper
    // ------------------------------------------------------------------ //

    /**
     * Saves an uploaded image and returns the relative path, or null.
     * Keeps the old image if no new file is uploaded.
     */
    private static function handleImageUpload(string $inputName, ?string $currentImage = null): ?string
    {
        if (empty($_FILES[$inputName]['name'])) {
            return $currentImage; // nothing uploaded – keep existing
        }

        $file     = $_FILES[$inputName];
        $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxBytes = 2 * 1024 * 1024; // 2 MB

        if (!in_array($file['type'], $allowed, true)) {
            self::flashError('Invalid image type. Allowed: JPG, PNG, WEBP, GIF.');
            return false;
        }

        if ($file['size'] > $maxBytes) {
            self::flashError('Image must be smaller than 2 MB.');
            return false;
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('product_', true) . '.' . $ext;
        $dest     = __DIR__ . '/../../public/uploads/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            self::flashError('Failed to save image. Check uploads/ directory permissions.');
            return false;
        }

        // Remove old image if it exists
        if ($currentImage) {
            $oldPath = __DIR__ . '/../../public/uploads/' . basename($currentImage);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        return 'uploads/' . $filename;
    }

    // ------------------------------------------------------------------ //
    //  index()  –  GET ?page=products
    // ------------------------------------------------------------------ //

    public static function index(): void
    {
        $db = self::db();

        // ── Pagination ──────────────────────────────────────────────────
        $perPage     = 10;
        $currentPage = max(1, (int) ($_GET['p'] ?? 1));
        $offset      = ($currentPage - 1) * $perPage;

        // ── Filters ─────────────────────────────────────────────────────
        $search    = trim($_GET['search']    ?? '');
        $catFilter = trim($_GET['category']  ?? '');
        $staFilter = $_GET['status'] ?? '';

        $where  = ['p.is_deleted = 0'];
        $params = [];

        if ($search !== '') {
            $where[]          = 'p.name LIKE :search';
            $params[':search'] = "%$search%";
        }

        if ($catFilter !== '') {
            $where[]          = 'c.name = :cat';
            $params[':cat']    = $catFilter;
        }

        if ($staFilter !== '') {
            $where[]          = 'p.is_available = :status';
            $params[':status'] = (int) $staFilter;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ── Count total rows ─────────────────────────────────────────────
        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             $whereSQL"
        );
        $countStmt->execute($params);
        $totalRows  = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $currentPage = min($currentPage, $totalPages);

        // ── Fetch page ───────────────────────────────────────────────────
        $stmt = $db->prepare(
            "SELECT p.id, p.name, p.price, p.image, p.is_available,
                    c.name AS category
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             $whereSQL
             ORDER BY p.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Categories for the filter dropdown ───────────────────────────
        $categories = $db->query("SELECT id, name FROM categories ORDER BY name")
                         ->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'All Products';
        $activeNav = 'products';

        include __DIR__ . '/../../views/products/all_products.php';
    }

    // ------------------------------------------------------------------ //
    //  show()  –  returns one product row (used when opening edit form)
    // ------------------------------------------------------------------ //

    public static function show(int $id): ?array
    {
        $stmt = self::db()->prepare(
            "SELECT p.*, c.name AS category
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id AND p.is_deleted = 0"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ------------------------------------------------------------------ //
    //  store()  –  POST ?page=store-product
    // ------------------------------------------------------------------ //

    public static function store(): void
    {
        requireAdmin();

        $name        = trim($_POST['name']        ?? '');
        $price       = trim($_POST['price']       ?? '');
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        // ── Validation ───────────────────────────────────────────────────
        $errors = [];
        if ($name === '')          $errors[] = 'Product name is required.';
        if (!is_numeric($price) || (float)$price < 0)
                                   $errors[] = 'A valid price is required.';
        if ($category_id === 0)    $errors[] = 'Please select a category.';

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old']    = $_POST;
            self::redirect('add-product');
        }

        // ── Image ────────────────────────────────────────────────────────
        $image = self::handleImageUpload('image');
        if ($image === false) {            // upload error already flashed
            self::redirect('add-product');
        }

        // ── Insert ───────────────────────────────────────────────────────
        $stmt = self::db()->prepare(
            "INSERT INTO products (name, price, category_id, image, is_available)
             VALUES (:name, :price, :cat, :img, :avail)"
        );
        $stmt->execute([
            ':name'  => $name,
            ':price' => (float) $price,
            ':cat'   => $category_id,
            ':img'   => $image,
            ':avail' => $is_available,
        ]);

        self::flashSuccess("Product \"$name\" added successfully.");
        self::redirect('products');
    }

    // ------------------------------------------------------------------ //
    //  update()  –  POST ?page=update-product&id=X
    // ------------------------------------------------------------------ //

    public static function update(int $id): void
    {
        requireAdmin();

        $product = self::show($id);
        if (!$product) {
            self::flashError('Product not found.');
            self::redirect('products');
        }

        $name        = trim($_POST['name']        ?? '');
        $price       = trim($_POST['price']       ?? '');
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        // ── Validation ───────────────────────────────────────────────────
        $errors = [];
        if ($name === '')          $errors[] = 'Product name is required.';
        if (!is_numeric($price) || (float)$price < 0)
                                   $errors[] = 'A valid price is required.';
        if ($category_id === 0)    $errors[] = 'Please select a category.';

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old']    = $_POST;
            self::redirect('edit-product', ['id' => $id]);
        }

        // ── Image ────────────────────────────────────────────────────────
        $image = self::handleImageUpload('image', $product['image']);
        if ($image === false) {
            self::redirect('edit-product', ['id' => $id]);
        }

        // ── Update ───────────────────────────────────────────────────────
        $stmt = self::db()->prepare(
            "UPDATE products
             SET name = :name, price = :price, category_id = :cat,
                 image = :img, is_available = :avail
             WHERE id = :id AND is_deleted = 0"
        );
        $stmt->execute([
            ':name'  => $name,
            ':price' => (float) $price,
            ':cat'   => $category_id,
            ':img'   => $image,
            ':avail' => $is_available,
            ':id'    => $id,
        ]);

        self::flashSuccess("Product \"$name\" updated successfully.");
        self::redirect('products');
    }

    // ------------------------------------------------------------------ //
    //  delete()  –  GET/POST ?page=delete-product&id=X  (soft delete)
    // ------------------------------------------------------------------ //

    public static function delete(int $id): void
    {
        requireAdmin();

        $stmt = self::db()->prepare(
            "UPDATE products SET is_deleted = 1 WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);

        self::flashSuccess('Product deleted.');
        self::redirect('products');
    }

    // ------------------------------------------------------------------ //
    //  toggle()  –  GET ?page=toggle-product&id=X
    //  Also handles AJAX: if X-Requested-With header present, return JSON
    // ------------------------------------------------------------------ //

    public static function toggle(int $id): void
    {
        requireAdmin();

        $db   = self::db();
        $stmt = $db->prepare("SELECT is_available FROM products WHERE id = :id AND is_deleted = 0");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            if (self::isAjax()) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                exit;
            }
            self::flashError('Product not found.');
            self::redirect('products');
        }

        $newStatus = $product['is_available'] ? 0 : 1;

        $db->prepare("UPDATE products SET is_available = :s WHERE id = :id")
           ->execute([':s' => $newStatus, ':id' => $id]);

        if (self::isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['is_available' => $newStatus]);
            exit;
        }

        self::flashSuccess('Product availability updated.');
        self::redirect('products');
    }

    // ------------------------------------------------------------------ //
    //  Utility
    // ------------------------------------------------------------------ //

    private static function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}