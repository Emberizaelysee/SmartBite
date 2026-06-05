<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level())
    ob_clean();

header('Content-Type: application/json');
require_once '../../config/connection.php';
require_once '../../models/ProfileModel.php';

$response = ['success' => false, 'message' => ''];
$profileModel = new ProfileModel($conn);

// verif si user connecte
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Not authenticated.';
    echo json_encode($response);
    exit();
}

// get payload from request
$data = json_decode(file_get_contents('php://input'), true);
if ($data === null)
    $data = $_POST;

// get user id from session
$user_id = (int) $_SESSION['user_id'];
$action = $data['action'] ?? 'update_profile';

// modifier le profil
if ($action === 'update_profile') {
    $username = isset($data['username']) ? trim($data['username']) : '';

    if (empty($username)) {
        $response['message'] = 'Username cannot be empty.';
        echo json_encode($response);
        exit();
    }

    if ($profileModel->updateProfile($user_id, $username)) {
        $_SESSION['user_name'] = $username;
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully.';
        $response['username'] = $username;
    } else {
        $response['message'] = 'Update failed.';
    }

} elseif ($action === 'change_password') {
    $current_pw = $data['current_password'] ?? '';
    $new_pw = $data['new_password'] ?? '';
    $confirm_pw = $data['confirm_password'] ?? '';

    if ($new_pw !== $confirm_pw) {
        $response['message'] = 'New passwords do not match.';
        echo json_encode($response);
        exit();
    }
    if (strlen($new_pw) < 6) {
        $response['message'] = 'Password must be at least 6 characters.';
        echo json_encode($response);
        exit();
    }

    $hashed = $profileModel->getPasswordHash($user_id);
    if ($hashed === null) {
        $response['message'] = 'User not found.';
        echo json_encode($response);
        exit();
    }

    if (!password_verify($current_pw, $hashed)) {
        $response['message'] = 'Current password is incorrect.';
        echo json_encode($response);
        exit();
    }

    $new_hashed = password_hash($new_pw, PASSWORD_DEFAULT);
    if ($profileModel->updatePassword($user_id, $new_hashed)) {
        $response['success'] = true;
        $response['message'] = 'Password changed successfully.';
    } else {
        $response['message'] = 'Failed to change password.';
    }

} elseif ($action === 'delete_account') {
    if ($profileModel->deleteAccount($user_id)) {
        session_destroy();
        $response['success'] = true;
        $response['message'] = 'Account deleted.';
    } else {
        $response['message'] = 'Failed to delete account.';
    }

} else {
    $response['message'] = 'Unknown action.';
}

$conn->close();
echo json_encode($response);
?>