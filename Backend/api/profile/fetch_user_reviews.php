<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json');

require_once '../../config/connection.php';
require_once '../../models/ProfileModel.php';

$response = ['success' => false, 'data' => []];

// verif si user connecte
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not authenticated';
    echo json_encode($response);
    exit();
}

// try to fetch user reviews
try {
    $userId = (int) $_SESSION['user_id'];
    $profileModel = new ProfileModel($conn);
    $response['success'] = true;
    $response['data'] = $profileModel->getUserReviews($userId);
} catch (Exception $e) {
    $response['message'] = 'Failed to fetch reviews: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>