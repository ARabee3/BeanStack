<?php
/**
 * app/Controllers/OrderController.php
 *
 * تعديلات على حسب الجداول الفعلية:
 *
 *  [1] location_snapshot:
 *      الجدول فيه column اسمه location_snapshot (TEXT).
 *      الكود القديم مكنش بيحفظه.
 *      الحل: جلب details من جدول locations وحفظه في location_snapshot.
 *
 *  [2] notes و location_id:
 *      ممكن ييجوا undefined من الـ payload.
 *      الحل: ?? null بدل ما يطلع error.
 *
 *  [3] status ENUM:
 *      القيم الصح: 'processing', 'out_for_delivery', 'canceled', 'done'
 *      الكود بيحفظ 'processing' افتراضياً — صح.
 *
 *  باقي الكود لم يتغير.
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/Database.php';

$method = $_SERVER['REQUEST_METHOD'];

// ═══════════════════════════════════════════════════════
// GET — إرجاع أوردرات الـ user الحالي
// ═══════════════════════════════════════════════════════
if ($method === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    try {
        $db     = Database::connect();
        $userId = $_SESSION['user_id'];

        $sql = "SELECT o.id,
                       o.order_date  AS date,
                       o.status,
                       o.total_price AS amount
                FROM   orders o
                WHERE  o.user_id = ?
                ORDER  BY o.order_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'orders' => $orders]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════
// POST — حفظ أوردر جديد
// ═══════════════════════════════════════════════════════

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['products'])) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty. Please add products.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You are not a registered user. Please log in to place an order.'
    ]);
    exit;
}

// ── تحديد userId: session أو target (admin mode) ─────────
$userId = $_SESSION['user_id'];

if (isset($data['target_user_id'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Only admins can place orders for other users.']);
        exit;
    }

    $targetId = (int) $data['target_user_id'];

    if ($targetId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user selected.']);
        exit;
    }

    try {
        $db    = Database::connect();
        $check = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'user' LIMIT 1");
        $check->execute([$targetId]);

        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Selected employee not found.']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
        exit;
    }

    $userId = $targetId;
}

// ── التعديل [2]: null-safe للحقول الاختيارية ─────────────
// الجدول: notes TEXT DEFAULT NULL  → nullable ✓
// الجدول: location_id INT          → nullable ✓
// لو الـ payload ما فيهومش → null بدل undefined error
$notes      = $data['notes']       ?? null;
$locationId = $data['location_id'] ?? null;

// ═══════════════════════════════════════════════════════
// حفظ الأوردر في الـ DB
// ═══════════════════════════════════════════════════════
try {
    $db = Database::connect();
    $db->beginTransaction();

    // ── التعديل [1]: جلب location_snapshot ───────────────
    // الجدول فيه column اسمه location_snapshot (TEXT).
    // بنجيب details من جدول locations ونحفظه snapshot
    // لأن الـ location ممكن يتغير أو يتمسح بعدين.
    $locationSnapshot = null;

    if ($locationId) {
        $locStmt = $db->prepare("SELECT details FROM locations WHERE id = ? LIMIT 1");
        $locStmt->execute([$locationId]);
        $loc = $locStmt->fetch(PDO::FETCH_ASSOC);
        if ($loc) {
            $locationSnapshot = $loc['details'];   // TEXT — اسم الأوضة وقت الأوردر
        }
    }

    // 1. حساب الـ Total Price
    $total = 0;
    foreach ($data['products'] as $item) {
        $total += ($item['qty'] * $item['price']);
    }

    // 2. INSERT الأوردر الرئيسي
    // ── التعديل [1]: أضفنا location_snapshot في الـ INSERT ─
    $sqlOrder = "INSERT INTO orders
                     (user_id, total_price, notes, location_id, location_snapshot, status)
                 VALUES
                     (?, ?, ?, ?, ?, 'processing')";

    $stmt = $db->prepare($sqlOrder);
    $stmt->execute([
        $userId,
        $total,
        $notes,               // [2] null-safe
        $locationId,          // [2] null-safe
        $locationSnapshot,    // [1] snapshot نص الأوضة
    ]);

    $orderId = $db->lastInsertId();

    // 3. INSERT order_items — لم يتغير
    $sqlItems = "INSERT INTO order_items
                     (order_id, product_id, quantity, price_at_purchase)
                 VALUES (?, ?, ?, ?)";

    $stmtItem = $db->prepare($sqlItems);

    foreach ($data['products'] as $productId => $details) {
        $stmtItem->execute([
            $orderId,
            $productId,
            $details['qty'],
            $details['price']
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}