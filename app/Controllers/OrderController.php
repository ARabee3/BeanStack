<?php

/**
 * OrderController.php
 * Handles all order operations for both users and admins.
 * Place in: app/Controllers/OrderController.php
 *
 * Routes handled by index.php:
 *   GET  ?page=orders              → index()        admin order dashboard
 *   POST ?page=store-order         → store()        place order (user or admin-manual)
 *   GET  ?page=update-order-status → updateStatus() change order status (AJAX-aware)
 *   GET  ?page=cancel-order        → cancel()       cancel an order
 *   GET  ?page=order-items         → items()        return order items as JSON (AJAX)
 */

require_once __DIR__ . '/../../config/Database.php';

class OrderController
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

    private static function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    private static function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // ------------------------------------------------------------------ //
    //  index()  —  GET ?page=orders   (admin dashboard)
    // ------------------------------------------------------------------ //

    public static function index(): void
    {
        requireAdmin();

        $db = self::db();

        // ── Filters ──────────────────────────────────────────────────────
        $statusFilter = $_GET['status'] ?? '';
        $search       = trim($_GET['search'] ?? '');

        // ── Pagination ────────────────────────────────────────────────────
        $perPage     = 15;
        $currentPage = max(1, (int) ($_GET['p'] ?? 1));
        $offset      = ($currentPage - 1) * $perPage;

        $where  = ['1=1'];
        $params = [];

        if ($statusFilter !== '') {
            $where[]           = 'o.status = :status';
            $params[':status'] = $statusFilter;
        }

        if ($search !== '') {
            $where[]           = 'u.name LIKE :search';
            $params[':search'] = "%$search%";
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        // ── Count ─────────────────────────────────────────────────────────
        $countStmt = $db->prepare(
            "SELECT COUNT(DISTINCT o.id)
             FROM orders o
             JOIN users u ON u.id = o.user_id
             $whereSQL"
        );
        $countStmt->execute($params);
        $totalRows   = (int) $countStmt->fetchColumn();
        $totalPages  = max(1, (int) ceil($totalRows / $perPage));
        $currentPage = min($currentPage, $totalPages);

        // ── Fetch orders (header info only — items fetched per-card via AJAX) ─
        $stmt = $db->prepare(
            "SELECT o.id,
                    o.order_date,
                    o.status,
                    o.total_price,
                    o.notes,
                    o.location_snapshot,
                    u.name  AS user_name,
                    u.email AS user_email,
                    l.details AS location
             FROM orders o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN locations l ON l.id = o.location_id
             $whereSQL
             ORDER BY
               FIELD(o.status,'processing','out_for_delivery','done','canceled'),
               o.order_date DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Status counts for the summary badges ─────────────────────────
        $countByStatus = $db->query(
            "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $pageTitle = 'Orders';
        $activeNav = 'orders';

        include __DIR__ . '/../../views/admin/orders.php';
    }

    // ------------------------------------------------------------------ //
    //  items()  —  GET ?page=order-items&id=X  (AJAX — returns JSON)
    // ------------------------------------------------------------------ //

    public static function items(): void
    {
        requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);

        $stmt = self::db()->prepare(
            "SELECT oi.quantity,
                    oi.price_at_purchase AS price,
                    p.name,
                    p.image
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :id"
        );
        $stmt->execute([':id' => $id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        self::jsonResponse(['success' => true, 'items' => $items]);
    }

    // ------------------------------------------------------------------ //
    //  store()  —  POST ?page=store-order
    //  Works for both regular users and admin manual orders.
    // ------------------------------------------------------------------ //

    public static function store(): void
    {
        // Accept both JSON body (AJAX) and regular form POST
        $isJsonRequest = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

        if ($isJsonRequest) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            $data = $_POST;
            // Decode products from JSON string when sent as a form field
            if (isset($data['products']) && is_string($data['products'])) {
                $data['products'] = json_decode($data['products'], true);
            }
        }

        // ── Auth ──────────────────────────────────────────────────────────
        if (!isset($_SESSION['user_id'])) {
            self::jsonResponse(['success' => false, 'message' => 'Not logged in.'], 401);
        }

        // ── Validate cart ─────────────────────────────────────────────────
        if (empty($data['products'])) {
            self::jsonResponse(['success' => false, 'message' => 'Cart is empty.'], 422);
        }

        // ── Determine target user ─────────────────────────────────────────
        $userId = (int) $_SESSION['user_id'];

        if (!empty($data['target_user_id'])) {
            if (($_SESSION['role'] ?? '') !== 'admin') {
                self::jsonResponse(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            $targetId = (int) $data['target_user_id'];
            $chk = self::db()->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $chk->execute([$targetId]);
            if (!$chk->fetch()) {
                self::jsonResponse(['success' => false, 'message' => 'Employee not found.'], 422);
            }
            $userId = $targetId;
        }

        // ── Location snapshot ─────────────────────────────────────────────
        $locationId       = $data['location_id'] ? (int) $data['location_id'] : null;
        $locationSnapshot = null;

        if ($locationId) {
            $locStmt = self::db()->prepare("SELECT details FROM locations WHERE id = ? LIMIT 1");
            $locStmt->execute([$locationId]);
            $loc = $locStmt->fetch(PDO::FETCH_ASSOC);
            if ($loc) $locationSnapshot = $loc['details'];
        }

        // ── Calculate total ───────────────────────────────────────────────
        $total = 0;
        foreach ($data['products'] as $item) {
            $total += ((float)$item['price']) * ((int)$item['qty']);
        }

        // ── Insert order + items ──────────────────────────────────────────
        try {
            $db = self::db();
            $db->beginTransaction();

            $db->prepare(
                "INSERT INTO orders
                    (user_id, total_price, notes, location_id, location_snapshot, status, order_date)
                 VALUES (?, ?, ?, ?, ?, 'processing', NOW())"
            )->execute([
                $userId,
                $total,
                $data['notes'] ?? null,
                $locationId,
                $locationSnapshot,
            ]);

            $orderId  = (int) $db->lastInsertId();
            $itemStmt = $db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($data['products'] as $productId => $details) {
                $itemStmt->execute([
                    $orderId,
                    (int) $productId,
                    (int) $details['qty'],
                    (float) $details['price'],
                ]);
            }

            $db->commit();
            self::jsonResponse(['success' => true, 'order_id' => $orderId]);

        } catch (Exception $e) {
            $db->rollBack();
            self::jsonResponse(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------ //
    //  updateStatus()  —  GET/POST ?page=update-order-status&id=X&status=Y
    //  AJAX-aware: returns JSON if X-Requested-With header is set.
    // ------------------------------------------------------------------ //

    public static function updateStatus(): void
    {
        requireAdmin();

        $id        = (int) ($_GET['id'] ?? 0);
        $newStatus = $_GET['status'] ?? '';

        $allowed = ['processing', 'out_for_delivery', 'done', 'canceled'];

        if (!$id || !in_array($newStatus, $allowed, true)) {
            if (self::isAjax()) {
                self::jsonResponse(['success' => false, 'message' => 'Invalid request.'], 422);
            }
            self::flashError('Invalid status update request.');
            self::redirect('orders');
        }

        $stmt = self::db()->prepare(
            "SELECT id, status FROM orders WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            if (self::isAjax()) {
                self::jsonResponse(['success' => false, 'message' => 'Order not found.'], 404);
            }
            self::flashError('Order not found.');
            self::redirect('orders');
        }

        self::db()->prepare("UPDATE orders SET status = :s WHERE id = :id")
                  ->execute([':s' => $newStatus, ':id' => $id]);

        if (self::isAjax()) {
            self::jsonResponse(['success' => true, 'status' => $newStatus]);
        }

        self::flashSuccess("Order #$id status updated to $newStatus.");
        self::redirect('orders');
    }

    // ------------------------------------------------------------------ //
    //  cancel()  —  GET ?page=cancel-order&id=X
    //  Convenience wrapper — only allowed on 'processing' orders.
    // ------------------------------------------------------------------ //

    public static function cancel(): void
    {
        requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);

        $stmt = self::db()->prepare("SELECT status FROM orders WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            self::flashError('Order not found.');
            self::redirect('orders');
        }

        if ($order['status'] === 'done') {
            self::flashError('Cannot cancel a completed order.');
            self::redirect('orders');
        }

        self::db()->prepare("UPDATE orders SET status = 'canceled' WHERE id = :id")
                  ->execute([':id' => $id]);

        if (self::isAjax()) {
            self::jsonResponse(['success' => true]);
        }

        self::flashSuccess("Order #$id has been canceled.");
        self::redirect('orders');
    }

    // ------------------------------------------------------------------ //
    //  myOrders()  —  GET ?page=my-orders  (logged-in user)
    // ------------------------------------------------------------------ //

    public static function myOrders(): void
    {
        requireLogin();

        $db     = self::db();
        $userId = (int) $_SESSION['user_id'];

        $stmt = $db->prepare(
            "SELECT o.id, o.order_date, o.status, o.total_price, o.notes,
                    o.location_snapshot
             FROM orders o
             WHERE o.user_id = :uid
             ORDER BY o.order_date DESC"
        );
        $stmt->execute([':uid' => $userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch items for each order
        $itemStmt = $db->prepare(
            "SELECT oi.quantity, oi.price_at_purchase AS price, p.name, p.image
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :oid"
        );

        foreach ($orders as &$o) {
            $itemStmt->execute([':oid' => $o['id']]);
            $o['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($o);

        $pageTitle = 'My Orders';
        $activeNav = 'my-orders';

        include __DIR__ . '/../../views/orders/my_orders.php';
    }
}