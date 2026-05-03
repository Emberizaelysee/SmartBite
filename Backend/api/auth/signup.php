<?php
session_start();
//require_once '../config/connection.php';
require_once __DIR__ . '/../../config/connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname  = trim($_POST["fullname"]);
    $email     = trim($_POST["email"]);
    $password1 = $_POST["password1"];
    $password2 = $_POST["confirm_password"];

    if ($password1 !== $password2) {
        header("Location: ../../../Frontend/signup.html?error=password_mismatch");
        exit();
    }

    // Vérifier que l'email n'existe pas déjà
    $stmt = $conn->prepare("SELECT IdUser FROM Users WHERE UserEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        header("Location: ../../../Frontend/signup.html?error=email_taken");
        exit();
    }

    $stmt->close();

    // Hasher le mot de passe
    $hashed_password = password_hash($password1, PASSWORD_DEFAULT);

    $insert = $conn->prepare("INSERT INTO Users (UserName, UserEmail, UserPassword, UserRole) VALUES (?, ?, ?, 'user')");
    $insert->bind_param("sss", $fullname, $email, $hashed_password);

    if ($insert->execute()) {
        $new_id = $conn->insert_id;

        // Connexion automatique après inscription
        $_SESSION["user_id"]   = $new_id;
        $_SESSION["user_name"] = $fullname;
        $_SESSION["user_role"] = "user";

        $insert->close();
        header("Location: ../../../Frontend/index.html");
        exit();
    } else {
        $insert->close();
        header("Location: ../../../Frontend/signup.html?error=server_error");
        exit();
    }
}
?>