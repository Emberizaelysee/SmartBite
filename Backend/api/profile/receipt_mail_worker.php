<?php
// verif si le script est execute en ligne de commande
if (php_sapi_name() !== 'cli') {
    exit(1);
}

$rawJob = $argv[1] ?? '';
$job = json_decode(base64_decode($rawJob, true) ?: '', true);

if (!is_array($job) || empty($job['type']) || !is_array($job['payload'] ?? null)) {
    exit(1);
}

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/profile_receipt_mails.php';

$type = $job['type'];
$payload = $job['payload'];

try {
    switch ($type) {
        case 'reorder':
            $userId = (int) ($payload['user_id'] ?? 0);
            $newOrderId = (int) ($payload['new_order_id'] ?? 0);
            $sourceOrderId = (int) ($payload['source_order_id'] ?? 0);
            $totalAmount = (float) ($payload['total_amount'] ?? 0);

            if ($userId <= 0 || $newOrderId <= 0) {
                break;
            }

            $items = fetchReorderItemsWithNames($conn, $newOrderId);
            sendReorderReceiptMail($conn, $userId, $newOrderId, $sourceOrderId, $totalAmount, $items);
            break;

        case 'reservation_edit':
            $userId = (int) ($payload['user_id'] ?? 0);
            $previous = $payload['previous'] ?? null;
            $updated = $payload['reservation'] ?? null;

            if ($userId <= 0 || !is_array($previous) || !is_array($updated)) {
                break;
            }

            sendReservationEditReceiptMail($conn, $userId, $previous, $updated);
            break;

        case 'reservation_cancel':
            $userId = (int) ($payload['user_id'] ?? 0);
            $cancelled = $payload['cancelled_reservation'] ?? null;

            if ($userId <= 0 || !is_array($cancelled)) {
                break;
            }

            sendReservationCancelReceiptMail($conn, $userId, $cancelled);
            break;

        default:
            break;
    }
} catch (Throwable $e) {
    error_log('Receipt mail worker error: ' . $e->getMessage());
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

exit(0);
