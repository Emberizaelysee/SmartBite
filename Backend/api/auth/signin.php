<?php
session_start();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/check_remember.php';
checkRememberMe($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $remember = isset($_POST["remember_me"]);

    $stmt = $conn->prepare("SELECT IdUser, UserName, UserPassword, UserRole FROM users WHERE UserEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (is_null($user["UserPassword"])) {
            header("Location: ../../../Frontend/signin.html?error=use_google");
            exit();
        }
        if (password_verify($password, $user["UserPassword"])) {
            // CONNEXION RÉUSSIE
            $_SESSION["user_id"]   = $user["IdUser"];
            $_SESSION["user_name"] = $user["UserName"];
            $_SESSION["user_role"] = $user["UserRole"];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $update = $conn->prepare("UPDATE users SET UserToken = ? WHERE IdUser = ?");
                $update->bind_param("si", $token, $user["IdUser"]);
                $update->execute();
                $update->close();
                setcookie("remember_token", $token, time() + (30 * 24 * 3600), "/", "", false, true);
            }

            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'index.php';
            $allowed  = ['index.php', 'reservation.php', 'review.php', 'cart.php'];
            if (!in_array($redirect, $allowed)) $redirect = 'index.php';
            header("Location: ../../../Frontend/" . $redirect);
            exit();

        } else {
            // ERREUR : MOT DE PASSE INCORRECT
            header("Location: ../../../Frontend/signin.html?error=invalid");
            exit();
        }
    } else {
        // ERREUR : EMAIL NON TROUVÉ
        header("Location: ../../../Frontend/signin.html?error=invalid");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>