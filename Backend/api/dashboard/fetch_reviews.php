<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level())
    ob_clean();

header('Content-Type: application/json');
require_once '../../config/connection.php';
require_once '../../models/DashboardModel.php';

// verif si user connecte et admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// fait appel a la fonction getReviews et envoie les donnees sous format json
$dashboardModel = new DashboardModel($conn);
$reviewPayload = $dashboardModel->getReviews();
$response = [
    'success' => true,
    'data' => $reviewPayload['data'],
    'average_rating' => $reviewPayload['average_rating'],
    'total' => $reviewPayload['total'],
];

$conn->close();
echo json_encode($response);
?>