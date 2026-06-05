<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level())
    ob_clean();

header('Content-Type: application/json');
require_once '../../config/connection.php';
require_once __DIR__ . '/profile_receipt_mails.php';

$response = ['success' => false, 'message' => ''];

// check user connection
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not authenticated';
    echo json_encode($response);
    exit();
}

// get id from session and data from post 
$userId = (int) $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data))
    $data = $_POST;

$sourceOrderId = (int) ($data['source_order_id'] ?? 0);
$items = $data['items'] ?? [];

// check if data is valid 
if ($sourceOrderId <= 0 || !is_array($items) || count($items) === 0) {
    $response['message'] = 'Invalid reorder payload.';
    echo json_encode($response);
    exit();
}

// Verify the source order belongs to this user
$ownerStmt = $conn->prepare('SELECT IdOrder FROM orders WHERE IdOrder = ? AND IdUser = ?');
$ownerStmt->bind_param('ii', $sourceOrderId, $userId);
$ownerStmt->execute();
$ownerRes = $ownerStmt->get_result();
if (!$ownerRes || $ownerRes->num_rows === 0) {
    $ownerStmt->close();
    $response['message'] = 'Order not found or access denied.';
    echo json_encode($response);
    exit();
}
$ownerStmt->close();

// Normalise items
$normalizedItems = [];
foreach ($items as $item) {
    $menuItemId = (int) ($item['menu_item_id'] ?? 0);
    $qty = (int) ($item['qty'] ?? 0);
    if ($menuItemId > 0 && $qty > 0) {
        $normalizedItems[] = ['menu_item_id' => $menuItemId, 'qty' => $qty];
    }
}

// prevent empty order
if (count($normalizedItems) === 0) {
    $response['message'] = 'At least one item with quantity > 0 is required.';
    echo json_encode($response);
    exit();
}

$conn->begin_transaction();

try {
    $totalAmount = 0.0;
    $pricedItems = [];

    // get current price from menu table using IdMenu / ItemPrice
    $priceStmt = $conn->prepare('SELECT IdMenu, ItemPrice FROM menu WHERE IdMenu = ?');
    foreach ($normalizedItems as $item) {
        $menuItemId = $item['menu_item_id'];
        $qty = $item['qty'];

        $priceStmt->bind_param('i', $menuItemId);
        $priceStmt->execute();
        $priceRes = $priceStmt->get_result();
        $row = $priceRes ? $priceRes->fetch_assoc() : null;

        if (!$row) {
            throw new Exception('One or more menu items no longer exist.');
        }

        $price = (float) $row['ItemPrice'];
        $totalAmount += $price * $qty;
        $pricedItems[] = [
            'menu_item_id' => $menuItemId,
            'qty' => $qty,
            'price_at_time' => $price,
        ];
    }
    $priceStmt->close();

    $status = 'Confirmed';
    $specialInstructions = 'Reordered from order #' . $sourceOrderId;

    // Insert into orders
    $orderStmt = $conn->prepare(
        'INSERT INTO orders (IdUser, OrderTotalAmount, Status, SpecialInstructions) VALUES (?, ?, ?, ?)'
    );
    $orderStmt->bind_param('idss', $userId, $totalAmount, $status, $specialInstructions);
    if (!$orderStmt->execute()) {
        throw new Exception('Failed to create reordered order.');
    }
    $newOrderId = (int) $orderStmt->insert_id;
    $orderStmt->close();

    // Insert into orderitems
    $itemStmt = $conn->prepare(
        'INSERT INTO orderitems (IdOrder, IdMenu, Quantity, PriceAtTime) VALUES (?, ?, ?, ?)'
    );
    foreach ($pricedItems as $item) {
        $menuItemId = $item['menu_item_id'];
        $qty = $item['qty'];
        $priceAtTime = $item['price_at_time'];
        $itemStmt->bind_param('iiid', $newOrderId, $menuItemId, $qty, $priceAtTime);
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to insert reordered items.');
        }
    }
    $itemStmt->close();

    $conn->commit();

    dispatchReceiptMailAsync('reorder', [
        'user_id' => $userId,
        'new_order_id' => $newOrderId,
        'source_order_id' => $sourceOrderId,
        'total_amount' => $totalAmount,
    ]);

    $response['success'] = true;
    $response['message'] = 'Order reordered successfully.';
    $response['new_order_id'] = $newOrderId;
    $response['total_amount'] = round($totalAmount, 2);

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>