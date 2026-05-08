<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
ob_clean();
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

// check si on a pu acceder au profile model et fait appel a la fonction getUserOrders et envoie les donnees sous format json 
try {
    $user_id = (int) $_SESSION['user_id'];
    $profileModel = new ProfileModel($conn);
    $response['success'] = true;
    $response['data'] = $profileModel->getUserOrders($user_id);

} catch (Exception $e) {
    $response['message'] = 'Failed to fetch orders: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>