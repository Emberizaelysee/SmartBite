<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level())
    ob_clean();
header('Content-Type: application/json');
require_once '../../config/connection.php';
require_once '../../models/DashboardModel.php';

$response = ['success' => false, 'message' => ''];
$dashboardModel = new DashboardModel($conn);

// verif si user connecte et admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit();  
}

$data = json_decode(file_get_contents('php://input'), true);
if ($data === null)
    $data = $_POST;

$action = $data['action'] ?? '';

// resoudre le total de la commande get DB prices + appliquer le discount retourne [items, subtotal, total]
$resolveOrderTotal = function (array $rawItems, string $discountType, float $discountValue) use ($conn): array {
    $subtotal = 0;
    $resolved = [];
    foreach ($rawItems as $item) {
        $menuId = (int) ($item['menu_id'] ?? 0);
        $qty = max(1, (int) ($item['quantity'] ?? 1));
        if ($menuId <= 0)
            continue;
        $res = $conn->query("SELECT ItemPrice FROM menu WHERE IdMenu = {$menuId} LIMIT 1");
        if (!$res || $res->num_rows === 0)
            continue;
        $price = (float) $res->fetch_assoc()['ItemPrice'];
        $subtotal += $price * $qty;
        $resolved[] = ['menu_id' => $menuId, 'quantity' => $qty, 'price_at_time' => $price];
    }
    $discountAmount = 0;
    if ($discountType === 'percent' && $discountValue > 0)
        $discountAmount = $subtotal * (min($discountValue, 100) / 100);
    elseif ($discountType === 'fixed' && $discountValue > 0)
        $discountAmount = min($discountValue, $subtotal);
    $total = round(max(0.01, $subtotal - $discountAmount), 2);
    return ['items' => $resolved, 'subtotal' => $subtotal, 'total' => $total];
};


switch ($action) {

    // Menu 
    case 'delete_menu':
        $id = (int) ($data['id'] ?? 0);
        $response['success'] = $dashboardModel->deleteMenuItemFully($id);
        $response['message'] = $response['success'] ? 'Menu item deleted.' : 'Failed to delete menu item.';
        break;

    case 'edit_menu':
        $id = (int) ($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $ingredients = trim($data['ingredients'] ?? '');
        $price = (float) ($data['price'] ?? 0);
        $categoryId = (int) ($data['category_id'] ?? 0);
        $imageUrl = trim($data['image_url'] ?? '');

        if (empty($name) || $price <= 0 || $categoryId <= 0) {
            $response['message'] = 'Name, price and a valid category are required.';
            break;
        }

        $result = $dashboardModel->updateMenuItem($id, $name, $description, $ingredients, $price, $categoryId, $imageUrl);
        $response['success'] = $result['success'];
        $response['message'] = $result['success'] ? 'Menu item updated.' : 'Update failed: ' . $result['error'];
        break;

    case 'add_menu':
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $ingredients = trim($data['ingredients'] ?? '');
        $price = (float) ($data['price'] ?? 0);
        $categoryId = (int) ($data['category_id'] ?? 0);
        $imageUrl = trim($data['image_url'] ?? '');

        if (empty($name) || $price <= 0 || $categoryId <= 0) {
            $response['message'] = 'Name, price and a valid category are required.';
            break;
        }

        $result = $dashboardModel->addMenuItem($name, $description, $ingredients, $price, $categoryId, $imageUrl);
        $response['success'] = $result['success'];
        $response['message'] = $result['success'] ? 'Menu item added.' : 'Failed to add: ' . $result['error'];
        $response['new_id'] = $result['new_id'];
        break;

    // Reservations
    case 'add_reservation':
        $userId = (int) ($data['user_id'] ?? 0);
        $tableId = (int) ($data['table_id'] ?? 0);
        $date = trim($data['date'] ?? '');
        $time = trim($data['time'] ?? '');
        $guests = (int) ($data['guests'] ?? 0);
        $notes = trim($data['special_notes'] ?? '') ?: null;

        if ($userId <= 0 || $tableId <= 0 || $date === '' || $time === '' || $guests <= 0) {
            $response['message'] = 'User ID, table ID, date, time and guest count are required.';
            break;
        }

        $time = preg_match('/^\d{2}:\d{2}$/', $time) ? $time . ':00' : $time;
        $result = $dashboardModel->addReservation($userId, $tableId, $date, $time, $guests, $notes);
        $response['success'] = $result['success'];
        $response['message'] = $result['success'] ? 'Reservation added.' : ($result['error'] ?: 'Failed to add reservation.');
        $response['new_id'] = $result['new_id'];
        break;

    case 'edit_reservation':
        $id = (int) ($data['id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        $tableId = (int) ($data['table_id'] ?? 0);
        $date = trim($data['date'] ?? '');
        $time = trim($data['time'] ?? '');
        $guests = (int) ($data['guests'] ?? 0);
        $notes = trim($data['special_notes'] ?? '') ?: null;

        if ($id <= 0 || $userId <= 0 || $tableId <= 0 || $date === '' || $time === '' || $guests <= 0) {
            $response['message'] = 'All fields are required.';
            break;
        }

        $time = preg_match('/^\d{2}:\d{2}$/', $time) ? $time . ':00' : $time;
        $result = $dashboardModel->updateReservation($id, $userId, $tableId, $date, $time, $guests, $notes);
        $response['success'] = $result['success'];
        $response['message'] = $result['success'] ? 'Reservation updated.' : ($result['error'] ?: 'Failed to update reservation.');
        break;

    case 'delete_reservation':
        $id = (int) ($data['id'] ?? 0);
        $response['success'] = $dashboardModel->deleteById('reservations', $id);
        $response['message'] = $response['success'] ? 'Reservation deleted.' : 'Failed.';
        break;

    // Orders
    case 'add_order':
        $userId = (int) ($data['user_id'] ?? 0);
        $status = trim($data['status'] ?? 'Pending');
        $specialInstructions = trim($data['special_instructions'] ?? '') ?: null;
        $rawItems = is_array($data['items'] ?? null) ? $data['items'] : [];
        $discountType = trim($data['discount_type'] ?? 'percent');
        $discountValue = (float) ($data['discount_value'] ?? 0);

        if ($userId <= 0) {
            $response['message'] = 'A valid customer is required.';
            break;
        }
        if (empty($rawItems)) {
            $response['message'] = 'At least one order item is required.';
            break;
        }

        ['items' => $resolvedItems, 'total' => $totalAmount] = $resolveOrderTotal($rawItems, $discountType, $discountValue);
        if (empty($resolvedItems)) {
            $response['message'] = 'No valid menu items found.';
            break;
        }

        $allowedStatuses = ['Pending', 'Confirmed', 'Preparing', 'Completed', 'Delivered', 'Cancelled'];
        if (!in_array($status, $allowedStatuses, true))
            $status = 'Pending';

        $result = $dashboardModel->addOrder($userId, $totalAmount, $status, $specialInstructions);
        if (!$result['success']) {
            $response['message'] = 'Failed to create order: ' . $result['error'];
            break;
        }

        $newOrderId = (int) $result['new_id'];
        $dashboardModel->addOrderItems($newOrderId, $resolvedItems);

        $response['success'] = true;
        $response['message'] = 'Order added.';
        $response['new_id'] = $newOrderId;
        break;

    case 'edit_order':
        $id = (int) ($data['id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        $status = trim($data['status'] ?? 'Pending');
        $specialInstructions = trim($data['special_instructions'] ?? '') ?: null;
        $rawItems = is_array($data['items'] ?? null) ? $data['items'] : [];
        $discountType = trim($data['discount_type'] ?? 'percent');
        $discountValue = (float) ($data['discount_value'] ?? 0);

        if ($id <= 0 || $userId <= 0) {
            $response['message'] = 'Valid order ID and customer are required.';
            break;
        }
        if (empty($rawItems)) {
            $response['message'] = 'At least one order item is required.';
            break;
        }

        ['items' => $resolvedItems, 'total' => $totalAmount] = $resolveOrderTotal($rawItems, $discountType, $discountValue);
        if (empty($resolvedItems)) {
            $response['message'] = 'No valid menu items found.';
            break;
        }

        $allowedStatuses = ['Pending', 'Confirmed', 'Preparing', 'Completed', 'Delivered', 'Cancelled'];
        if (!in_array($status, $allowedStatuses, true))
            $status = 'Pending';

        $result = $dashboardModel->updateOrder($id, $userId, $totalAmount, $status, $specialInstructions);
        if (!$result['success']) {
            $response['message'] = 'Failed to update order: ' . $result['error'];
            break;
        }

        $dashboardModel->deleteOrderItems($id);
        $dashboardModel->addOrderItems($id, $resolvedItems);

        $response['success'] = true;
        $response['message'] = 'Order updated.';
        break;

    case 'delete_order':
        $id = (int) ($data['id'] ?? 0);
        $response['success'] = $dashboardModel->deleteOrderFully($id);
        $response['message'] = $response['success'] ? 'Order deleted.' : 'Failed to delete order.';
        break;

    case 'update_order_status':
        $id = (int) ($data['id'] ?? 0);
        $status = trim($data['status'] ?? '');
        $allowed = ['Pending', 'Confirmed', 'Preparing', 'Completed', 'Delivered', 'Cancelled'];
        if (!in_array($status, $allowed, true)) {
            $response['message'] = 'Invalid status.';
            break;
        }
        $response['success'] = $dashboardModel->setOrderStatus($id, $status);
        $response['message'] = $response['success'] ? 'Order status updated.' : 'Failed.';
        break;

    // Reviews
    case 'delete_review':
        $id = (int) ($data['id'] ?? 0);
        $response['success'] = $dashboardModel->deleteById('reviews', $id);
        $response['message'] = $response['success'] ? 'Review deleted.' : 'Failed.';
        break;

    case 'add_review':
        // Utiliser l'IdUser admin, un item de menu, une note et une description
        $userId = (int) $_SESSION['user_id'];
        $menuId = (int) ($data['menu_id'] ?? 0);
        $rating = (int) ($data['rating'] ?? 0);
        $description = trim($data['content'] ?? '') ?: null;

        if ($menuId <= 0 || $rating < 1 || $rating > 5) {
            $response['message'] = 'Valid menu item and rating (1-5) are required.';
            break;
        }

        $result = $dashboardModel->addReview($userId, $menuId, $rating, $description);
        $response['success'] = $result['success'];
        $response['message'] = $result['success'] ? 'Review added.' : 'Failed: ' . $result['error'];
        $response['new_id'] = $result['new_id'];
        break;

    // Users
    case 'make_admin':
        $id = (int) ($data['id'] ?? 0);
        $response['success'] = $dashboardModel->setUserRole($id, 'admin');
        $response['message'] = $response['success'] ? 'User promoted to Admin.' : 'Failed.';
        break;

    case 'make_user':
        $id = (int) ($data['id'] ?? 0);
        $response['success'] = $dashboardModel->setUserRole($id, 'user');
        $response['message'] = $response['success'] ? 'Admin demoted to Customer.' : 'Failed.';
        break;

    case 'delete_user':
        $id = (int) ($data['id'] ?? 0);
        if ($id === (int) $_SESSION['user_id']) {
            $response['message'] = 'Cannot delete your own account from here.';
            break;
        }
        $response['success'] = $dashboardModel->deleteUserFully($id);
        $response['message'] = $response['success'] ? 'User deleted.' : 'Failed to delete user.';
        break;

    case 'add_table':
        $number = (int) ($data['table_number'] ?? 0);
        $capacity = (int) ($data['table_capacity'] ?? 0);
        $isActive = (int) ($data['is_active'] ?? 1) ? 1 : 0;

        if ($number < 1 || $capacity < 1) {
            $response['message'] = 'Table number and capacity must be at least 1.';
            break;
        }

        $result = $dashboardModel->addRestaurantTable($number, $capacity, $isActive);
        $response['success'] = $result['success'];
        $response['message'] = $result['success'] ? 'Table added.' : 'Failed: ' . $result['error'];
        $response['new_id'] = $result['new_id'];
        break;

    case 'edit_table':
        $id = (int) ($data['id'] ?? 0);
        $number = (int) ($data['table_number'] ?? 0);
        $capacity = (int) ($data['table_capacity'] ?? 0);
        $isActive = (int) ($data['is_active'] ?? 1) ? 1 : 0;

        if ($id <= 0 || $number < 1 || $capacity < 1) {
            $response['message'] = 'Valid table ID, number and capacity are required.';
            break;
        }

        $result = $dashboardModel->updateRestaurantTable($id, $number, $capacity, $isActive);
        $response['success'] = $result['success'];
        $response['message'] = $result['success'] ? 'Table updated.' : 'Failed: ' . $result['error'];
        break;

    case 'delete_table':
        $id = (int) ($data['id'] ?? 0);
        $response['success'] = $dashboardModel->deleteRestaurantTableFully($id);
        $response['message'] = $response['success'] ? 'Table deleted.' : 'Failed to delete table.';
        break;

    case 'add_user':
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = strtolower(trim($data['role'] ?? 'user'));

        if ($username === '' || $email === '' || $password === '') {
            $response['message'] = 'Username, email and password are required.';
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format.';
            break;
        }
        if (strlen($password) < 8) {
            $response['message'] = 'Password must be at least 8 characters.';
            break;
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=<>?{}[\]~]).+$/', $password)) {
            $response['message'] = 'Password must include uppercase, lowercase, number, and special character.';
            break;
        }

        if ($role === 'customer' || $role === 'user') {
            $role = 'user';
        } elseif ($role === 'admin') {
            $role = 'admin';
        } else {
            $role = 'user';
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $result = $dashboardModel->addUser($username, $email, $hash, $role);
        $response['success'] = $result['success'];
        $response['message'] = $result['success'] ? 'User added.' : 'Failed: ' . $result['error'];
        $response['new_id'] = $result['new_id'];
        break;

    // Metrics
    case 'get_weekly_revenue':
        $response['success'] = true;
        $response['data'] = $dashboardModel->getWeeklyRevenue();
        break;

    // Categories (pour le dropdown du formulaire de menu)
    case 'get_categories':
        $response['success'] = true;
        $response['data'] = $dashboardModel->getCategories();
        break;

    // Recherche d'utilisateur (pour les modaux de commande / reservation)
    case 'search_users':
        $term = trim($data['term'] ?? '');
        if (strlen($term) < 2) {
            $response['message'] = 'Search term too short.';
            break;
        }
        $response['success'] = true;
        $response['data'] = $dashboardModel->searchUsers($term);
        break;

    // Tables actives (pour le dropdown du modal de reservation)
    case 'get_tables':
        $response['success'] = true;
        $response['data'] = $dashboardModel->getTables();
        break;

    // Items de commande pour une commande specifique
    case 'get_order_items':
        $orderId = (int) ($data['order_id'] ?? 0);
        if ($orderId <= 0) {
            $response['message'] = 'Valid order_id required.';
            break;
        }
        $response['success'] = true;
        $response['data'] = $dashboardModel->getOrderItems($orderId);
        break;

    default:
        $response['message'] = 'Unknown action.';
        break;
}

$conn->close();
echo json_encode($response);

?>