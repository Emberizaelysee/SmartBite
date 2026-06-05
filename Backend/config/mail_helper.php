<?php
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//envoyer un email via SmartBite SMTP. Retourne true en cas de succes, false en cas d'echec.
function sendSmartBiteMail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $plainBody = ''
): bool {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('smartbite169@gmail.com', 'SmartBite');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $plainBody !== '' ? $plainBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('SmartBite mail error: ' . $e->getMessage());
        return false;
    }
}

// construire le layout de l'email de la facture
function buildReceiptEmailLayout(string $title, string $greeting, string $bodyHtml): string
{
    $year = date('Y');
    return "
    <html>
    <body style='font-family: Arial, sans-serif; background:#f6f8f7; margin:0; padding:24px;'>
        <div style='max-width:560px; margin:0 auto; background:#ffffff; border:1px solid #e8ece9; border-radius:12px; overflow:hidden;'>
            <div style='background:#16c451; color:#ffffff; padding:20px 24px;'>
                <h1 style='margin:0; font-size:22px;'>SmartBite</h1>
                <p style='margin:6px 0 0; font-size:14px; opacity:0.95;'>{$title}</p>
            </div>
            <div style='padding:24px; color:#333;'>
                <p style='margin:0 0 16px;'>{$greeting}</p>
                {$bodyHtml}
                <p style='margin:24px 0 0; font-size:13px; color:#888;'>
                    This is an automated confirmation from your SmartBite account.
                </p>
            </div>
            <div style='background:#f6f8f7; padding:14px 24px; font-size:12px; color:#888; text-align:center;'>
                &copy; {$year} SmartBite Restaurants. All rights reserved.
            </div>
        </div>
    </body>
    </html>";
}

// formater le montant de la facture
function formatReceiptMoney(float $amount): string
{
    return '$' . number_format($amount, 2);
}

// formater la date de la facture
function formatReceiptDate(string $date): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt ? $dt->format('F j, Y') : $date;
}
