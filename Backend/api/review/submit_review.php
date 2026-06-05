<?php
session_start();
require_once __DIR__ . '/../../config/connection.php';

// --- 1. VÉRIFIER QUE LE USER EST CONNECTÉ ---
if (!isset($_SESSION['user_id'])) {
    header("Location: /SmartBite/Frontend/signin.php?redirect=review");
    exit();
}

// --- 2. VÉRIFIER QUE LA REQUÊTE EST POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /SmartBite/Frontend/review.php");
    exit();
}

// --- 3. RÉCUPÉRER ET VALIDER LES DONNÉES ---
$idUser  = (int) $_SESSION['user_id'];
$idMenu  = isset($_POST['dish_id']) ? (int) $_POST['dish_id'] : 0;
$rating  = isset($_POST['rating'])  ? (int) $_POST['rating']  : 0;
$comment = isset($_POST['review'])  ? trim($_POST['review'])  : '';

if ($idMenu === 0 || $rating === 0 || $comment === '') {
    header("Location: /SmartBite/Frontend/review.php?error=missing_fields");
    exit();
}

if ($rating < 1 || $rating > 5) {
    header("Location: /SmartBite/Frontend/review.php?error=invalid_rating");
    exit();
}

// --- 4. VÉRIFIER QUE LE PLAT EXISTE ---
$stmtCheck = $conn->prepare("SELECT IdMenu FROM menu WHERE IdMenu = ?");
$stmtCheck->bind_param("i", $idMenu);
$stmtCheck->execute();
$stmtCheck->store_result();

if ($stmtCheck->num_rows === 0) {
    $stmtCheck->close();
    header("Location: /SmartBite/Frontend/review.php?error=invalid_dish");
    exit();
}
$stmtCheck->close();

// --- 5. INSÉRER LA REVIEW ---
$stmtInsert = $conn->prepare("
    INSERT INTO reviews (UserRating, RatingDescription, IdUser, IdMenu)
    VALUES (?, ?, ?, ?)
");
$stmtInsert->bind_param("isii", $rating, $comment, $idUser, $idMenu);

if ($stmtInsert->execute()) {
    $stmtInsert->close();
    $conn->close();
    header("Location: /SmartBite/Frontend/review.php?success=1");
    exit();
} else {
    $stmtInsert->close();
    $conn->close();
    header("Location: /SmartBite/Frontend/review.php?error=db_error");
    exit();
}
?>