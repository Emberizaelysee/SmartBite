<?php
// effacement du tampon regle Cannot modify header information - headers already sent
ob_clean();
header('Content-Type: application/json');
require_once '../../config/connection.php';
require_once '../../models/DashboardModel.php';

// verif si user connecte
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not authenticated';
    echo json_encode($response);
    exit();
}

// fait appel a la fonction getMenuItems et envoie les donnees sous format json 
$dashboardModel = new DashboardModel($conn);
$items = $dashboardModel->getMenuItems();

$conn->close();
echo json_encode($items);
?>