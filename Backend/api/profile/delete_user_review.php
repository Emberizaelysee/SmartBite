<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json');

require_once '../../config/connection.php';
require_once '../../models/ProfileModel.php';

$response = ['success' => false];

// verif si user connecte
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not authenticated';
    echo json_encode($response);
    exit();
}

// verif si la methode de la requette est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// get payload from request
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$reviewId = (int) ($payload['review_id'] ?? 0);

// verif si l'id de la review est valide
if ($reviewId <= 0) {
    $response['message'] = 'Valid review ID is required.';
    echo json_encode($response);
    exit();
}

// try to delete review
try {
    $userId = (int) $_SESSION['user_id'];
    $profileModel = new ProfileModel($conn);
    $deleted = $profileModel->deleteUserReview($reviewId, $userId);

    if ($deleted) {
        $response['success'] = true;
        $response['message'] = 'Review deleted successfully.';
    } else {
        $response['message'] = 'Review not found or could not be deleted.';
    }
} catch (Exception $e) {
    $response['message'] = 'Failed to delete review: ' . $e->getMessage();
}

echo json_encode($response);
exit();
