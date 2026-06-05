<?php
// constante pour la duree de la reservation
const RESERVATION_DURATION_MINUTES = 90;

// fonction pour normaliser le temps de la reservation
function normalizeReservationTime(string $time): string
{
    $time = trim($time);
    if (preg_match('/^\d{2}:\d{2}$/', $time)) {
        return $time . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
        return $time;
    }
    return $time;
}

// fonction pour convertir le temps de la reservation en minutes
function reservationTimeToMinutes(string $time): int
{
    $normalized = normalizeReservationTime($time);
    $parts = explode(':', $normalized);
    return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
}

// fonction pour verifier si deux reservations se chevauchent
function reservationTimesOverlap(string $timeA, string $timeB, int $durationMinutes = RESERVATION_DURATION_MINUTES): bool
{
    $a = reservationTimeToMinutes($timeA);
    $b = reservationTimeToMinutes($timeB);
    return $a < ($b + $durationMinutes) && $b < ($a + $durationMinutes);
}

// fonction pour verifier si une reservation peut etre modifiee
function canModifyReservationByDate(string $reservationDate): bool
{
    // verif si la date de la reservation est valide
    $reservationDate = trim($reservationDate);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reservationDate)) {
        return false;
    }

    // verif si la date de la reservation est dans le passé
    $today = new DateTime('today');
    $resDate = new DateTime($reservationDate);
    if ($resDate < $today) {
        return false;
    }

    // verif si la date de la reservation est dans le futur
    $deadline = (clone $resDate)->modify('-3 days');
    return $today <= $deadline;
}

// fonction pour verifier si une table a une reservation en conflit
function tableHasReservationConflict(
    mysqli $db,
    int $tableId,
    string $date,
    string $time,
    ?int $excludeReservationId = null
): bool {
    $time = normalizeReservationTime($time);

    // verifier si une table a une reservation en conflit
    $stmt = $db->prepare(
        'SELECT IdReservation, ReservationTime FROM reservations
         WHERE IdTable = ? AND ReservationDate = ?'
    );
    if (!$stmt) {
        return true;
    }
    $stmt->bind_param('is', $tableId, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $existingId = (int) $row['IdReservation'];
        if ($excludeReservationId > 0 && $existingId === $excludeReservationId) {
            continue;
        }
        if (reservationTimesOverlap($time, (string) $row['ReservationTime'])) {
            $stmt->close();
            return true;
        }
    }
    $stmt->close();
    return false;
}

// fonction pour trouver la meilleure table disponible
function findBestAvailableTable(
    mysqli $db,
    int $guests,
    string $date,
    string $time,
    ?int $excludeReservationId = null
): ?array {
    if ($guests < 1 || $date === '' || $time === '') {
        return null;
    }

    $time = normalizeReservationTime($time);

    // trouver la meilleure table disponible
    $stmt = $db->prepare(
        'SELECT IdTable, TableNumber, TableCapacity
         FROM restauranttable
         WHERE IsActive = 1 AND TableCapacity >= ?
         ORDER BY TableCapacity ASC'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $guests);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $tableId = (int) $row['IdTable'];
        if (!tableHasReservationConflict($db, $tableId, $date, $time, $excludeReservationId)) {
            $stmt->close();
            return [
                'id' => $tableId,
                'number' => (int) $row['TableNumber'],
                'capacity' => (int) $row['TableCapacity'],
            ];
        }
    }
    $stmt->close();
    return null;
}

// fonction pour obtenir les options de temps de reservation
function getReservationTimeSlotOptions(): array
{
    // retourne les options de temps de reservation
    return [
        ['label' => '11:30 AM', 'value' => '11:30:00'],
        ['label' => '12:00 PM', 'value' => '12:00:00'],
        ['label' => '12:30 PM', 'value' => '12:30:00'],
        ['label' => '1:00 PM', 'value' => '13:00:00'],
        ['label' => '1:30 PM', 'value' => '13:30:00'],
        ['label' => '2:00 PM', 'value' => '14:00:00'],
        ['label' => '5:30 PM', 'value' => '17:30:00'],
        ['label' => '6:00 PM', 'value' => '18:00:00'],
        ['label' => '6:30 PM', 'value' => '18:30:00'],
        ['label' => '7:00 PM', 'value' => '19:00:00'],
        ['label' => '7:30 PM', 'value' => '19:30:00'],
        ['label' => '8:00 PM', 'value' => '20:00:00'],
    ];
}
?>