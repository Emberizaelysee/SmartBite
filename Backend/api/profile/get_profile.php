<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
ob_clean();
header('Content-Type: application/json');
require_once '../../config/connection.php';
require_once '../../models/ProfileModel.php';

$response = ['success' => false];

// verif si user connecte
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Not authenticated.';
    echo json_encode($response);
    exit();
}

// recup info user via profile model
$user_id = (int) $_SESSION['user_id'];
$profileModel = new ProfileModel($conn);
$profile = $profileModel->getProfileById($user_id);

// envoie reponse json avec data
if ($profile) {
    $response['success'] = true;
    $response['id'] = $profile['id'];
    $response['username'] = $profile['username'];
    $response['email'] = $profile['email'];
    $response['role'] = $profile['role'];
    $response['created_at'] = $profile['created_at'];
    $response['avatar'] = $profile['avatar'];
}

// close connection bdd
$conn->close();
// reponse format JSON
echo json_encode($response);
?>