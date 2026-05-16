<?php
session_start();
require_once __DIR__ . '/../../config/connection.php';

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