<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level())
    ob_clean();

header('Content-Type: application/json');

require_once '../../config/connection.php';
require_once '../../models/ProfileModel.php';

$response = ['success' => false, 'message' => ''];

// verif si user connecte
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Not authenticated.';
    echo json_encode($response);
    exit();
}
// verif si le fichier avatar est present
if (!isset($_FILES['avatar'])) {
    $response['message'] = 'No file uploaded.';
    echo json_encode($response);
    exit();
}

// verif si le upload est OK
$file = $_FILES['avatar'];
if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Upload failed.';
    echo json_encode($response);
    exit();
}
// set taille du fichier et type
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$maxBytes = 2 * 1024 * 1024;

// verif si la taille du fichier est OK
if (($file['size'] ?? 0) > $maxBytes) {
    $response['message'] = 'Image size must be 2MB or less.';
    echo json_encode($response);
    exit();
}
// verif si le type du fichier est OK
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMimeType = $finfo->file($file['tmp_name']) ?: '';
if (!isset($allowedMimeTypes[$detectedMimeType])) {
    $response['message'] = 'Only JPG, PNG or WEBP images are allowed.';
    echo json_encode($response);
    exit();
}
// dossier pour upload 
$userId = (int) $_SESSION['user_id'];
$fileExtension = $allowedMimeTypes[$detectedMimeType];
$backendRoot = dirname(__DIR__, 2);
$resolvedBackend = realpath($backendRoot);
if ($resolvedBackend !== false) {
    $backendRoot = $resolvedBackend;
}
$uploadsDirectory = $backendRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';

if (!is_dir($uploadsDirectory)) {
    @mkdir($uploadsDirectory, 0777, true);
}
@chmod($uploadsDirectory, 0777);

if (!is_dir($uploadsDirectory)) {
    $response['message'] = 'Could not create upload directory: ' . $uploadsDirectory;
    echo json_encode($response);
    exit();
}

if (!is_writable($uploadsDirectory)) {
    $response['message'] = 'Upload folder is not writable. ' . dirname($uploadsDirectory);
    echo json_encode($response);
    exit();
}

// set nom de fichier et chemin de destination
$fileName = sprintf('avatar_%d_%d.%s', $userId, time(), $fileExtension);
$destinationPath = $uploadsDirectory . '/' . $fileName;

// deplacer le fichier upload
if (!@move_uploaded_file($file['tmp_name'], $destinationPath)) {
    $lastError = error_get_last();
    $details = $lastError['message'] ?? 'unknown runtime error';
    $response['message'] = 'Failed to save image. Runtime says: ' . $details;
    echo json_encode($response);
    exit();
}

// set chemin relatif de l'avatar et modifier l'avatar dans la base de donnees
$relativeAvatarPath = 'uploads/avatars/' . $fileName;
$profileModel = new ProfileModel($conn);
if (!$profileModel->updateAvatar($userId, $relativeAvatarPath)) {
    // supprimer le fichier upload si la modification echoue 
    @unlink($destinationPath);
    $response['message'] = 'Failed to update avatar.';
    echo json_encode($response);
    exit();
}

$response['success'] = true;
$response['message'] = 'Profile picture updated.';
$response['avatar'] = $relativeAvatarPath;

$conn->close();
echo json_encode($response);
?>