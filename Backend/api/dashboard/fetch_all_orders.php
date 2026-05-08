<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
ob_clean();
header('Content-Type: application/json');
require_once '../../config/connection.php';
require_once '../../models/DashboardModel.php';

// verif si user connecte et admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// fait appel a la fonction getAllOrders et envoie les donnees sous format json
$dashboardModel = new DashboardModel($conn);
$response = [
    'success' => true,
    'data' => $dashboardModel->getAllOrders(),
];

$conn->close();
echo json_encode($response);
?>