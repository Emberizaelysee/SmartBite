<?php
session_start();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/secrets.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../PHPMailer-master/src/Exception.php';
require __DIR__ . '/../../PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../../PHPMailer-master/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        usleep(300000);
        header("Location: /smartbite/SmartBite/Frontend/forgot-password.html?sent=1");
        exit();
    }

    $stmt = $conn->prepare("SELECT IdUser, UserPassword FROM users WHERE UserEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Bloquer les comptes Google
        if (is_null($user["UserPassword"])) {
            usleep(300000);
            header("Location: /smartbite/SmartBite/Frontend/forgot-password.html?error=use_google");
            exit();
        }

        $token   = hash('sha256', random_bytes(64));
        $expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE UserEmail = ?");
        $update->bind_param("sss", $token, $expires, $email);
        $update->execute();
        $update->close();

        $resetLink = "http://localhost/smartbite/SmartBite/Frontend/reset-password.php?token=" . $token;

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('smartbite169@gmail.com', 'SmartBite');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'SmartBite - Reset your password';
            $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2 style='color:#16c451;'>SmartBite</h2>
                <p>You requested a password reset.</p>
                <a href='$resetLink' 
                   style='background:#16c451;color:white;padding:12px 24px;
                   border-radius:8px;text-decoration:none;'>
                   Reset Password
                </a>
                <p style='font-size:13px;color:#888;'>Expires in 5 minutes.</p>
            </body>
            </html>";

            $mail->send();

        } catch (Exception $e) {
            // on ne révèle pas l'erreur à l'utilisateur
        }
    }

    usleep(300000);
    header("Location: /smartbite/SmartBite/Frontend/forgot-password.html?sent=1");
    exit();
}
?>