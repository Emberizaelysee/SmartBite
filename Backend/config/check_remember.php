<?php
// check_remember.php

function checkRememberMe($conn) {

    // Déjà connecté via session → rien à faire
    if (isset($_SESSION["user_id"])) return;

    // Pas de cookie → rien à faire
    if (!isset($_COOKIE["remember_token"])) return;

    $token = $_COOKIE["remember_token"];

    // Chercher le token dans la DB
    $stmt = $conn->prepare("SELECT IdUser, UserName, UserRole FROM Users WHERE UserToken = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Recréer la session
        $_SESSION["user_id"]   = $user["IdUser"];
        $_SESSION["user_name"] = $user["UserName"];
        $_SESSION["user_role"] = $user["UserRole"];

        // Renouveler le cookie pour 30 jours supplémentaires
        $stmt2 = $conn->prepare("UPDATE Users SET UserToken = ? WHERE IdUser = ?");
        $new_token = bin2hex(random_bytes(32));
        $stmt2->bind_param("si", $new_token, $user["IdUser"]);
        $stmt2->execute();
        $stmt2->close();
        setcookie("remember_token", $new_token, time() + (30 * 24 * 3600), "/", "", false, true);

    } else {
        // Token invalide → supprimer le cookie
        setcookie("remember_token", "", time() - 3600, "/");
    }

    $stmt->close();
}
?>