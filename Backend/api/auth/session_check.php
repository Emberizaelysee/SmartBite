<?php
// returns login state et user info

// demarrage session si aucune session active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/connection.php';

// effacement du tampon regle Cannot modify header information - headers already sent
ob_clean();
header('Content-Type: application/json');

// init array contien reponse API
$response = [];

// etat connexion
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit();
}

// check d'un id user dans session -> connecte
if (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];

    // preparation de la requette SQL
    $stmt = $mysqli->prepare("SELECT UserName, UserEmail, UserRole FROM users WHERE IdUser = ?");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // extraction donnees user
            $user = $result->fetch_assoc();
            $response['logged_in'] = true;
            $response['username'] = $user['UserName'];
            $response['email'] = $user['UserEmail'];
            $response['role'] = $user['UserRole'];
            $response['success'] = true;
        } else {
            $response['logged_in'] = false;
            $response['message'] = 'User not found';
            $response['success'] = false;
        }
        $stmt->close();
    } else {
        // echec preparation de la requette SQL
        $response['logged_in'] = false;
        $response['message'] = 'Failed to prepare statement';
        $response['success'] = false;
    }
} else {
    // pas Id dans SESSION
    $response['logged_in'] = false;
    $response['success'] = true;
}

// close connection bdd
$mysqli->close();
// reponse format JSON
echo json_encode($response);
?>