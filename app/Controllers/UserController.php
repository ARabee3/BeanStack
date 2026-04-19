<?php

/**
 * UserController.php
 * Handles all user management operations (admin only).
 * Place in: app/Controllers/UserController.php
 */

require_once __DIR__ . '/../../config/Database.php';

class UserController
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
        $query = http_build_query(array_merge(['page' => $page], $params));
        $base  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        header("Location: {$base}/index.php?{$query}");
        exit;
    }

    private static function flashSuccess(string $msg): void
    {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
    }

    private static function flashError(string $msg): void
    {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => $msg];
    }

    // ------------------------------------------------------------------ //
    //  Image upload helper
    // ------------------------------------------------------------------ //

    /**
     * Handles profile picture upload.
     * Returns: relative path string | null (no file) | false (upload error)
     */
    private static function handleImageUpload(string $inputName, ?string $current = null): string|false|null
    {
        if (empty($_FILES[$inputName]['name'])) {
            return $current; // nothing uploaded — keep existing
        }

        $file     = $_FILES[$inputName];
        $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxBytes = 2 * 1024 * 1024;

        if (!in_array($file['type'], $allowed, true)) {
            self::flashError('Invalid image type. Allowed: JPG, PNG, WEBP, GIF.');
            return false;
        }

        if ($file['size'] > $maxBytes) {
            self::flashError('Profile picture must be smaller than 2 MB.');
            return false;
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('user_', true) . '.' . $ext;
        $dest     = __DIR__ . '/../../public/uploads/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            self::flashError('Failed to save image. Check uploads/ directory permissions.');
            return false;
        }

        // Remove old picture if it exists
        if ($current) {
            $oldPath = __DIR__ . '/../../public/uploads/' . basename($current);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        return 'uploads/' . $filename;
    }

    // ------------------------------------------------------------------ //
    //  index() — GET ?page=users
    // ------------------------------------------------------------------ //

    public static function index(): void
    {
        requireAdmin();

        $db = self::db();

        // ── Pagination ───────────────────────────────────────────────────
        $perPage     = 10;
        $currentPage = max(1, (int) ($_GET['p'] ?? 1));
        $offset      = ($currentPage - 1) * $perPage;

        // ── Filters ──────────────────────────────────────────────────────
        $search     = trim($_GET['search']   ?? '');
        $roleFilter = trim($_GET['role']     ?? '');

        $where  = ['u.id != :self'];          // never show the logged-in admin to themselves
        $params = [':self' => $_SESSION['user_id'] ?? 0];

        if ($search !== '') {
            $where[]           = '(u.name LIKE :search OR u.email LIKE :search)';
            $params[':search'] = "%$search%";
        }

        if ($roleFilter !== '') {
            $where[]          = 'u.role = :role';
            $params[':role']  = $roleFilter;
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        // ── Count ────────────────────────────────────────────────────────
        $countStmt = $db->prepare("SELECT COUNT(*) FROM users u $whereSQL");
        $countStmt->execute($params);
        $totalRows   = (int) $countStmt->fetchColumn();
        $totalPages  = max(1, (int) ceil($totalRows / $perPage));
        $currentPage = min($currentPage, $totalPages);

        // ── Fetch page ────────────────────────────────────────────────────
        $stmt = $db->prepare(
            "SELECT u.id, u.name, u.email, u.role, u.profile_pic,
                    u.isActive, u.created_at,
                    l.details AS location
             FROM users u
             LEFT JOIN locations l ON l.id = u.location_id
             $whereSQL
             ORDER BY u.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'All Users';
        $activeNav = 'users';

        include __DIR__ . '/../../views/users/all_users.php';
    }

    // ------------------------------------------------------------------ //
    //  show() — fetch one user row for edit form
    // ------------------------------------------------------------------ //

    public static function show(int $id): ?array
    {
        $stmt = self::db()->prepare(
            "SELECT u.*, l.details AS location
             FROM users u
             LEFT JOIN locations l ON l.id = u.location_id
             WHERE u.id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ------------------------------------------------------------------ //
    //  store() — POST ?page=store-user
    // ------------------------------------------------------------------ //

    public static function store(): void
    {
        requireAdmin();

        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm']  ?? '');
        $location = trim($_POST['location'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['user', 'admin']) ? $_POST['role'] : 'user';

        // ── Validation ────────────────────────────────────────────────────
        $errors = [];
        if ($name === '')                          $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if (strlen($password) < 8)                $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)               $errors[] = 'Passwords do not match.';
        if ($location === '')                     $errors[] = 'Location is required.';

        // Duplicate email check
        if (!$errors) {
            $chk = self::db()->prepare("SELECT id FROM users WHERE email = :email");
            $chk->execute([':email' => $email]);
            if ($chk->fetch()) {
                $errors[] = 'That email address is already in use.';
            }
        }

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old']    = $_POST;
            self::redirect('add-user');
        }

        // ── Profile picture ───────────────────────────────────────────────
        $pic = self::handleImageUpload('profile_pic');
        if ($pic === false) {
            self::redirect('add-user');
        }

        $db = self::db();

        // ── Upsert location ───────────────────────────────────────────────
        $locId = self::upsertLocation($db, $location);

        // ── Insert user ───────────────────────────────────────────────────
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, password, location_id, profile_pic, role, isActive, created_at)
             VALUES (:name, :email, :pass, :loc, :pic, :role, 1, NOW())"
        );
        $stmt->execute([
            ':name'  => $name,
            ':email' => $email,
            ':pass'  => password_hash($password, PASSWORD_BCRYPT),
            ':loc'   => $locId,
            ':pic'   => $pic,
            ':role'  => $role,
        ]);

        self::flashSuccess("User \"$name\" created successfully.");
        self::redirect('users');
    }

    // ------------------------------------------------------------------ //
    //  update() — POST ?page=update-user&id=X
    // ------------------------------------------------------------------ //

    public static function update(int $id): void
    {
        requireAdmin();

        $user = self::show($id);
        if (!$user) {
            self::flashError('User not found.');
            self::redirect('users');
        }

        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm']  ?? '');
        $location = trim($_POST['location'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['user', 'admin']) ? $_POST['role'] : 'user';
        $isActive = isset($_POST['isActive']) ? 1 : 0;

        // ── Validation ────────────────────────────────────────────────────
        $errors = [];
        if ($name === '')                               $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if ($location === '')                           $errors[] = 'Location is required.';

        // Password only validated if provided
        if ($password !== '') {
            if (strlen($password) < 8)   $errors[] = 'Password must be at least 8 characters.';
            if ($password !== $confirm)  $errors[] = 'Passwords do not match.';
        }

        // Duplicate email — exclude current user
        if (!$errors) {
            $chk = self::db()->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $chk->execute([':email' => $email, ':id' => $id]);
            if ($chk->fetch()) {
                $errors[] = 'That email address is already in use.';
            }
        }

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old']    = $_POST;
            self::redirect('edit-user', ['id' => $id]);
        }

        // ── Profile picture ───────────────────────────────────────────────
        $pic = self::handleImageUpload('profile_pic', $user['profile_pic']);
        if ($pic === false) {
            self::redirect('edit-user', ['id' => $id]);
        }

        $db = self::db();

        // ── Upsert location ───────────────────────────────────────────────
        $locId = self::upsertLocation($db, $location);

        // ── Build update query ────────────────────────────────────────────
        $fields = [
            'name        = :name',
            'email       = :email',
            'location_id = :loc',
            'profile_pic = :pic',
            'role        = :role',
            'isActive    = :active',
        ];
        $binds  = [
            ':name'   => $name,
            ':email'  => $email,
            ':loc'    => $locId,
            ':pic'    => $pic,
            ':role'   => $role,
            ':active' => $isActive,
            ':id'     => $id,
        ];

        if ($password !== '') {
            $fields[]        = 'password = :pass';
            $binds[':pass']  = password_hash($password, PASSWORD_BCRYPT);
        }

        $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id")
           ->execute($binds);

        self::flashSuccess("User \"$name\" updated successfully.");
        self::redirect('users');
    }

    // ------------------------------------------------------------------ //
    //  delete() — GET ?page=delete-user&id=X
    // ------------------------------------------------------------------ //

    public static function delete(int $id): void
    {
        requireAdmin();

        // Prevent admin from deleting themselves
        if ((int) ($_SESSION['user_id'] ?? 0) === $id) {
            self::flashError('You cannot delete your own account.');
            self::redirect('users');
        }

        $user = self::show($id);
        if (!$user) {
            self::flashError('User not found.');
            self::redirect('users');
        }

        // Remove profile picture file
        if (!empty($user['profile_pic'])) {
            $path = __DIR__ . '/../../public/uploads/' . basename($user['profile_pic']);
            if (file_exists($path)) @unlink($path);
        }

        self::db()->prepare("DELETE FROM users WHERE id = :id")
                  ->execute([':id' => $id]);

        self::flashSuccess("User \"{$user['name']}\" deleted.");
        self::redirect('users');
    }

    // ------------------------------------------------------------------ //
    //  toggleActive() — GET ?page=toggle-user&id=X  (AJAX-aware)
    // ------------------------------------------------------------------ //

    public static function toggleActive(int $id): void
    {
        requireAdmin();

        if ((int) ($_SESSION['user_id'] ?? 0) === $id) {
            if (self::isAjax()) {
                http_response_code(403);
                echo json_encode(['error' => 'Cannot deactivate your own account.']);
                exit;
            }
            self::flashError('Cannot deactivate your own account.');
            self::redirect('users');
        }

        $db   = self::db();
        $stmt = $db->prepare("SELECT isActive FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            if (self::isAjax()) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found.']);
                exit;
            }
            self::flashError('User not found.');
            self::redirect('users');
        }

        $newStatus = $user['isActive'] ? 0 : 1;
        $db->prepare("UPDATE users SET isActive = :s WHERE id = :id")
           ->execute([':s' => $newStatus, ':id' => $id]);

        if (self::isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['isActive' => $newStatus]);
            exit;
        }

        self::flashSuccess('User status updated.');
        self::redirect('users');
    }

    // ------------------------------------------------------------------ //
    //  Private utilities
    // ------------------------------------------------------------------ //

    /**
     * Returns location id — reuses existing row if details match, else inserts.
     */
    private static function upsertLocation(PDO $db, string $details): int
    {
        $stmt = $db->prepare("SELECT id FROM locations WHERE details = :d");
        $stmt->execute([':d' => $details]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) return (int) $row['id'];

        $db->prepare("INSERT INTO locations (details) VALUES (:d)")
           ->execute([':d' => $details]);

        return (int) $db->lastInsertId();
    }

    private static function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}