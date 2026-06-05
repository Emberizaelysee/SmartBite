<?php
session_start();
// effacement du tampon regle Cannot modify header information - headers already sent
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json');

require_once '../../config/connection.php';
require_once __DIR__ . '/reservation_helpers.php';
require_once __DIR__ . '/profile_receipt_mails.php';
require_once '../../models/ProfileModel.php';

$response = ['success' => false];

// verif si user connecte
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not authenticated';
    echo json_encode($response);
    exit();
}

// verif si la methode de la requette est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// get payload from request
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim($payload['action'] ?? '');
$userId = (int) $_SESSION['user_id'];
$reservationId = (int) ($payload['reservation_id'] ?? 0);

// verif si l'id de la reservation est valide
if ($reservationId <= 0) {
    $response['message'] = 'Valid reservation ID is required.';
    echo json_encode($response);
    exit();
}

// try to cancel or edit reservation
try {
    $profileModel = new ProfileModel($conn);

    if ($action === 'cancel') {
        $result = $profileModel->cancelUserReservation($reservationId, $userId);
        $response['success'] = $result['success'];
        $response['message'] = $result['message'];

        if ($result['success'] && !empty($result['cancelled_reservation'])) {
            dispatchReceiptMailAsync('reservation_cancel', [
                'user_id' => $userId,
                'cancelled_reservation' => $result['cancelled_reservation'],
            ]);
        }

        echo json_encode($response);
        exit();
    }

    if ($action === 'edit') {
        $date = trim($payload['date'] ?? '');
        $time = trim($payload['time'] ?? '');
        $guests = (int) ($payload['guests'] ?? 0);
        $notes = isset($payload['special_notes']) ? trim((string) $payload['special_notes']) : null;
        if ($notes === '') {
            $notes = null;
        }

        // verif si le temps est valide
        $allowedTimes = array_column(getReservationTimeSlotOptions(), 'value');
        $timeNormalized = normalizeReservationTime($time);
        if (!in_array($timeNormalized, $allowedTimes, true)) {
            $response['message'] = 'Please select a valid time slot.';
            echo json_encode($response);
            exit();
        }

        // try to update reservation
        $result = $profileModel->updateUserReservation(
            $reservationId,
            $userId,
            $date,
            $timeNormalized,
            $guests,
            $notes
        );
        $response['success'] = $result['success'];
        $response['message'] = $result['message'];
        if (!empty($result['table_number'])) {
            $response['table_number'] = $result['table_number'];
        }

        if (
            $result['success']
            && !empty($result['previous'])
            && !empty($result['reservation'])
        ) {
            dispatchReceiptMailAsync('reservation_edit', [
                'user_id' => $userId,
                'previous' => $result['previous'],
                'reservation' => $result['reservation'],
            ]);
        }

        echo json_encode($response);
        exit();
    }

    $response['message'] = 'Unknown action.';
} catch (Exception $e) {
    $response['message'] = 'Request failed: ' . $e->getMessage();
}

echo json_encode($response);
exit();
