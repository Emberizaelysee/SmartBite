<?php
session_start();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/secrets.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

// --- 1. verifier que le user est connecte ---
if (!isset($_SESSION['user_id'])) {
    header("Location: /SmartBite/Frontend/signin.html?redirect=reservation");
    exit();
}

// --- 2. VÉRIFIER QUE LA REQUÊTE EST POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /SmartBite/Frontend/reservation.html");
    exit();
}

// --- 3. RÉCUPÉRER ET VALIDER LES DONNÉES DU FORMULAIRE ---
$idUser   = (int) $_SESSION['user_id'];
$guests   = isset($_POST['guests'])   ? (int) trim($_POST['guests'])   : 0;
$date     = isset($_POST['date'])     ? trim($_POST['date'])           : '';
$time     = isset($_POST['time'])     ? trim($_POST['time'])           : '';
$requests = isset($_POST['requests']) ? trim($_POST['requests'])       : '';

// Préparer les params de restauration pour toutes les redirections d'erreur
$restoreParams = "&date=" . urlencode($date) . "&time=" . urlencode($time) . "&guests=" . $guests;

// Validation basique
if ($guests < 1 || $guests > 10 || empty($date) || empty($time)) {
    header("Location: /SmartBite/Frontend/reservation.html?error=missing_fields" . $restoreParams);
    exit();
}

// Valider le format date (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header("Location: /SmartBite/Frontend/reservation.html?error=invalid_date" .$restoreParams);
    exit();
}

// Vérifier que la date n'est pas dans le passé
$today = new DateTime('today');
$reservationDate = new DateTime($date);
if ($reservationDate < $today) {
    header("Location: /SmartBite/Frontend/reservation.html?error=past_date" . $restoreParams);
    exit();
}

// Convertir le time AM/PM → format 24h pour MySQL (TIME)
// Ex: "11:30 AM" → "11:30:00" | "7:00 PM" → "19:00:00"
$timeParsed = DateTime::createFromFormat('g:i A', $time);
if (!$timeParsed) {
    header("Location: /SmartBite/Frontend/reservation.html?error=invalid_time" . $restoreParams);
    exit();
}
$timeMySQL = $timeParsed->format('H:i:s');

// --- 4. TROUVER UNE TABLE DISPONIBLE ---
// Table avec capacité >= guests, active, et pas déjà réservée
// pour la même date ET le même créneau horaire
// On prend la plus petite capacité suffisante (best fit)
$stmtTable = $conn->prepare("
    SELECT t.IdTable, t.TableNumber, t.TableCapacity
    FROM restauranttable t
    WHERE t.IsActive = TRUE
      AND t.TableCapacity >= ?
      AND t.IdTable NOT IN (
          SELECT r.IdTable
          FROM reservations r
          WHERE r.ReservationDate = ?
            AND r.ReservationTime = ?
      )
    ORDER BY t.TableCapacity ASC
    LIMIT 1
");
$stmtTable->bind_param("iss", $guests, $date, $timeMySQL);
$stmtTable->execute();
$resultTable = $stmtTable->get_result();

if ($resultTable->num_rows === 0) {
    $stmtTable->close();
    $conn->close();
    header("Location: /SmartBite/Frontend/reservation.html?error=no_table_available" . $restoreParams);
    exit();
}

$table   = $resultTable->fetch_assoc();
$idTable = $table['IdTable'];
$stmtTable->close();

// --- 5. INSÉRER LA RÉSERVATION ---
$stmtInsert = $conn->prepare("
    INSERT INTO reservations (GuestNumber, SpecialNotes, ReservationDate, ReservationTime, IdTable, IdUser)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmtInsert->bind_param("isssii", $guests, $requests, $date, $timeMySQL, $idTable, $idUser);

if ($stmtInsert->execute()) {
    $stmtInsert->close();

    // --- 6. EMAIL DE CONFIRMATION ---
    $stmtUser = $conn->prepare("SELECT UserName, UserEmail FROM users WHERE IdUser = ?");
    $stmtUser->bind_param("i", $idUser);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $stmtUser->close();

    if ($resultUser->num_rows === 1) {
        $user          = $resultUser->fetch_assoc();
        $userName      = $user['UserName'];
        $userEmail     = $user['UserEmail'];
        $dateFormatted = (new DateTime($date))->format('l, F j, Y');
        $specialNotes  = !empty($requests) ? $requests : 'None';

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
            $mail->addAddress($userEmail, $userName);
            $mail->isHTML(true);
            $mail->Subject = 'SmartBite - Reservation Confirmed!';
            $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif; background:#f4f4f4; padding: 20px;'>
                <div style='max-width:500px; margin:auto; background:white; border-radius:12px; padding:30px; box-shadow:0 4px 12px rgba(0,0,0,0.08);'>
                    <h2 style='color:#16c451; margin-top:0;'>🍽️ SmartBite</h2>
                    <h3 style='color:#333;'>Your reservation is confirmed!</h3>
                    <p style='color:#555;'>Hi <strong>{$userName}</strong>, here's a summary of your booking:</p>
                    <div style='background:#e9faf0; border-radius:8px; padding:16px; margin:20px 0;'>
                        <table style='width:100%; border-collapse:collapse; font-size:15px;'>
                            <tr>
                                <td style='padding:8px 0; color:#888;'>📅 Date</td>
                                <td style='padding:8px 0; color:#333; font-weight:600;'>{$dateFormatted}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px 0; color:#888;'>🕐 Time</td>
                                <td style='padding:8px 0; color:#333; font-weight:600;'>{$time}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px 0; color:#888;'>👥 Guests</td>
                                <td style='padding:8px 0; color:#333; font-weight:600;'>{$guests}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px 0; color:#888;'>🪑 Table</td>
                                <td style='padding:8px 0; color:#333; font-weight:600;'>Table {$table['TableNumber']}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px 0; color:#888;'>📝 Special Requests</td>
                                <td style='padding:8px 0; color:#333; font-weight:600;'>{$specialNotes}</td>
                            </tr>
                        </table>
                    </div>
                    <p style='color:#555;'>We look forward to welcoming you!</p>
                    <p style='color:#16c451; font-weight:bold;'>— The SmartBite Team</p>
                    <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>
                    <p style='font-size:12px; color:#aaa; text-align:center;'>© 2026 SmartBite Restaurants. All rights reserved.</p>
                </div>
            </body>
            </html>";

            $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            // Email failed silently — reservation still confirmed
        }
    }

    $conn->close();
    header("Location: /SmartBite/Frontend/reservation.html?success=1&table=" . $table['TableNumber'] . "&date=" . urlencode($date) . "&time=" . urlencode($time) . "&guests=" . $guests);
    exit();
} else {
    $stmtInsert->close();
    $conn->close();
    header("Location: /SmartBite/Frontend/reservation.html?error=db_error");
    exit();
}
?>