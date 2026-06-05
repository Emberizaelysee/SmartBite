<?php
require_once __DIR__ . '/../../config/mail_helper.php';
require_once __DIR__ . '/reservation_helpers.php';

// obtenir le nom et l'email de l'utilisateur par ID
function getUserMailRecipient(mysqli $db, int $userId): ?array
{
    $stmt = $db->prepare('SELECT UserName, UserEmail FROM users WHERE IdUser = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || empty($row['UserEmail'])) {
        return null;
    }

    return [
        'name' => $row['UserName'] ?? 'Guest',
        'email' => $row['UserEmail'],
    ];
}

// construire une ligne de detail de la facture
function buildReceiptDetailRow(string $label, string $value): string
{
    $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    return "<tr>
        <td style='padding:8px 0; color:#666; width:40%; vertical-align:top;'>{$safeLabel}</td>
        <td style='padding:8px 0; font-weight:600;'>{$safeValue}</td>
    </tr>";
}

// envelopper les details de la facture dans un tableau
function wrapReceiptDetailsTable(string $rowsHtml): string
{
    return "<table style='width:100%; border-collapse:collapse; margin:16px 0; font-size:14px;'>{$rowsHtml}</table>";
}

// envoyer un email de confirmation de commande reorder
function sendReorderReceiptMail(
    mysqli $db,
    int $userId,
    int $newOrderId,
    int $sourceOrderId,
    float $totalAmount,
    array $items
): void {
    $recipient = getUserMailRecipient($db, $userId);
    if (!$recipient) {
        return;
    }

    $itemsHtml = '';
    foreach ($items as $item) {
        $name = htmlspecialchars($item['name'] ?? 'Item', ENT_QUOTES, 'UTF-8');
        $qty = (int) ($item['qty'] ?? 0);
        $price = formatReceiptMoney((float) ($item['price_at_time'] ?? 0));
        $lineTotal = formatReceiptMoney((float) ($item['price_at_time'] ?? 0) * $qty);
        $itemsHtml .= "<tr>
            <td style='padding:10px 8px; border-bottom:1px solid #eee;'>{$name}</td>
            <td style='padding:10px 8px; border-bottom:1px solid #eee; text-align:center;'>{$qty}</td>
            <td style='padding:10px 8px; border-bottom:1px solid #eee; text-align:right;'>{$price}</td>
            <td style='padding:10px 8px; border-bottom:1px solid #eee; text-align:right; font-weight:600;'>{$lineTotal}</td>
        </tr>";
    }

    $details = wrapReceiptDetailsTable(
        buildReceiptDetailRow('New order #', (string) $newOrderId)
        . buildReceiptDetailRow('Based on order #', (string) $sourceOrderId)
        . buildReceiptDetailRow('Status', 'Pending')
        . buildReceiptDetailRow('Total', formatReceiptMoney($totalAmount))
        . buildReceiptDetailRow('Date', date('F j, Y \a\t g:i A'))
    );

    $itemsTable = "
        <h3 style='margin:20px 0 8px; font-size:15px; color:#16c451;'>Order items</h3>
        <table style='width:100%; border-collapse:collapse; font-size:14px;'>
            <thead>
                <tr style='background:#f6f8f7;'>
                    <th style='padding:10px 8px; text-align:left;'>Item</th>
                    <th style='padding:10px 8px; text-align:center;'>Qty</th>
                    <th style='padding:10px 8px; text-align:right;'>Unit price</th>
                    <th style='padding:10px 8px; text-align:right;'>Subtotal</th>
                </tr>
            </thead>
            <tbody>{$itemsHtml}</tbody>
        </table>";

    $body = $details . $itemsTable;
    $greeting = 'Hi ' . htmlspecialchars($recipient['name'], ENT_QUOTES, 'UTF-8') . ',<br>Your reorder has been placed successfully. Here is your receipt:';
    $html = buildReceiptEmailLayout('Order Reorder Confirmation', $greeting, $body);

    sendSmartBiteMail(
        $recipient['email'],
        $recipient['name'],
        'SmartBite - Reorder Confirmation #' . $newOrderId,
        $html
    );
}

// envoyer un email de confirmation de modification de reservation
function sendReservationEditReceiptMail(
    mysqli $db,
    int $userId,
    array $previous,
    array $updated
): void {
    $recipient = getUserMailRecipient($db, $userId);
    if (!$recipient) {
        return;
    }

    $previousRows = wrapReceiptDetailsTable(
        buildReceiptDetailRow('Date', formatReceiptDate($previous['date']))
        . buildReceiptDetailRow('Time', formatReservationTimeLabel($previous['time']))
        . buildReceiptDetailRow('Guests', (string) $previous['guests'])
        . buildReceiptDetailRow('Table', '#' . $previous['table_number'])
        . ($previous['special_notes'] !== ''
            ? buildReceiptDetailRow('Notes', $previous['special_notes'])
            : '')
    );

    $updatedRows = wrapReceiptDetailsTable(
        buildReceiptDetailRow('Reservation #', (string) $updated['id'])
        . buildReceiptDetailRow('Date', formatReceiptDate($updated['date']))
        . buildReceiptDetailRow('Time', formatReservationTimeLabel($updated['time']))
        . buildReceiptDetailRow('Guests', (string) $updated['guests'])
        . buildReceiptDetailRow('Table', '#' . $updated['table_number'])
        . ($updated['special_notes'] !== ''
            ? buildReceiptDetailRow('Notes', $updated['special_notes'])
            : '')
    );

    $body = "
        <p style='margin:0 0 8px; font-size:14px; color:#666;'>Previous booking</p>
        {$previousRows}
        <p style='margin:16px 0 8px; font-size:14px; color:#666;'>Updated booking</p>
        {$updatedRows}";

    $greeting = 'Hi ' . htmlspecialchars($recipient['name'], ENT_QUOTES, 'UTF-8') . ',<br>Your reservation has been updated. Here are the details:';
    $html = buildReceiptEmailLayout('Reservation Update Confirmation', $greeting, $body);

    sendSmartBiteMail(
        $recipient['email'],
        $recipient['name'],
        'SmartBite - Reservation Updated #' . $updated['id'],
        $html
    );
}

// envoyer un email de confirmation de cancellation de reservation
function sendReservationCancelReceiptMail(
    mysqli $db,
    int $userId,
    array $cancelled
): void {
    $recipient = getUserMailRecipient($db, $userId);
    if (!$recipient) {
        return;
    }

    $details = wrapReceiptDetailsTable(
        buildReceiptDetailRow('Reservation #', (string) $cancelled['id'])
        . buildReceiptDetailRow('Date', formatReceiptDate($cancelled['date']))
        . buildReceiptDetailRow('Time', formatReservationTimeLabel($cancelled['time']))
        . buildReceiptDetailRow('Guests', (string) $cancelled['guests'])
        . buildReceiptDetailRow('Table', '#' . $cancelled['table_number'])
        . ($cancelled['special_notes'] !== ''
            ? buildReceiptDetailRow('Notes', $cancelled['special_notes'])
            : '')
        . buildReceiptDetailRow('Cancelled on', date('F j, Y \a\t g:i A'))
    );

    $greeting = 'Hi ' . htmlspecialchars($recipient['name'], ENT_QUOTES, 'UTF-8') . ',<br>Your reservation has been cancelled. Here is your cancellation receipt:';
    $html = buildReceiptEmailLayout('Reservation Cancellation', $greeting, $details);

    sendSmartBiteMail(
        $recipient['email'],
        $recipient['name'],
        'SmartBite - Reservation Cancelled #' . $cancelled['id'],
        $html
    );
}

// formater le temps de la reservation
function formatReservationTimeLabel(string $time): string
{
    $normalized = normalizeReservationTime($time);
    foreach (getReservationTimeSlotOptions() as $option) {
        if ($option['value'] === $normalized) {
            return $option['label'];
        }
    }

    $parts = explode(':', $normalized);
    $hour = (int) ($parts[0] ?? 0);
    $minute = (int) ($parts[1] ?? 0);
    $period = $hour >= 12 ? 'PM' : 'AM';
    $hour12 = $hour % 12 ?: 12;
    return sprintf('%d:%02d %s', $hour12, $minute, $period);
}

// recuperer les items de la commande reorder
function fetchReorderItemsWithNames(mysqli $db, int $orderId): array
{
    $stmt = $db->prepare(
        "SELECT oi.Quantity AS qty, oi.PriceAtTime AS price_at_time, m.ItemName AS name
         FROM orderitems oi
         JOIN menu m ON oi.IdMenu = m.IdMenu
         WHERE oi.IdOrder = ?"
    );
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'name' => $row['name'],
            'qty' => (int) $row['qty'],
            'price_at_time' => (float) $row['price_at_time'],
        ];
    }
    $stmt->close();
    return $items;
}

// envoyer l'email de la facture dans un processus PHP separe (arriere-plan) pour ne pas bloquer la reponse de l'API pendant la connexion SMTP.
function dispatchReceiptMailAsync(string $type, array $payload): void
{
    $job = [
        'type'    => $type,
        'payload' => $payload,
    ];

    $encoded = base64_encode(json_encode($job));
    $worker  = __DIR__ . '/receipt_mail_worker.php';
    $phpBinary = '';

    if (PHP_OS_FAMILY === 'Windows') {
        // Sous WAMP, PHP_BINARY = chemin vers php.exe — toujours valide
        if (defined('PHP_BINARY') && PHP_BINARY !== '' && is_file(PHP_BINARY)) {
            $phpBinary = PHP_BINARY;
        } else {
            // Fallback : chercher php.exe dans les emplacements WAMP habituels
            foreach (['C:\\wamp64\\bin\\php\\php.exe', 'C:\\wamp\\bin\\php\\php.exe'] as $candidate) {
                if (is_file($candidate)) {
                    $phpBinary = $candidate;
                    break;
                }
            }
            if ($phpBinary === '') {
                $phpBinary = 'php'; // dernier recours : PATH
            }
        }
    } else {
        // PHP_BINARY executable en CLI (XAMPP)
        if (defined('PHP_BINARY') && PHP_BINARY !== '' && is_executable(PHP_BINARY)) {
            $phpBinary = PHP_BINARY;
        }
        // Binaire CLI standard de XAMPP
        elseif (is_executable('/opt/lampp/bin/php')) {
            $phpBinary = '/opt/lampp/bin/php';
        }
        // Dernier recours : php dans le PATH systeme
        else {
            $phpBinary = 'php';
        }
    }

    // Lancement en arriere-plan du processus PHP
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen(
            'start /B '
            . escapeshellarg($phpBinary)
            . ' ' . escapeshellarg($worker)
            . ' ' . escapeshellarg($encoded),
            'r'
        ));
        return;
    }

    $command = 'nohup '
        . escapeshellarg($phpBinary)
        . ' ' . escapeshellarg($worker)
        . ' ' . escapeshellarg($encoded)
        . ' > /dev/null 2>&1 &';

    exec($command);
}

