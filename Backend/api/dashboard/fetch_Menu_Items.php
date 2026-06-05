<?php
// Used by chatbot and dashboard to get the menu items
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level())
    ob_clean();

header('Content-Type: application/json');
require_once '../../config/connection.php';
require_once '../../models/DashboardModel.php';

// fait appel a la fonction getMenuItems et envoie les donnees sous format json 
$dashboardModel = new DashboardModel($conn);
$items = $dashboardModel->getMenuItems();

$conn->close();
echo json_encode($items);
?>