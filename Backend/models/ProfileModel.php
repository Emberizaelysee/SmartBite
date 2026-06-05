<?php
require_once __DIR__ . '/../api/profile/reservation_helpers.php';

// ProfileModel Class pour gerer les user related data
class ProfileModel
{
    private mysqli $db;


    // Constructor de connection
    public function __construct(mysqli $db)
    {
        // donne la connection db a la propriete
        $this->db = $db;
    }

    // retourner les informations du profil de l'utilisateur par ID
    public function getProfileById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT IdUser, UserName, UserEmail, UserRole, UserAvatar, created_at FROM users WHERE IdUser = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc() ?: null;
        $stmt->close();

        // no row -> user n existe pas
        if (!$row) {
            return null;
        }

        // mapping des donne user a des key
        return [
            'id' => (int) $row['IdUser'],
            'username' => $row['UserName'],
            'email' => $row['UserEmail'],
            'role' => $row['UserRole'],
            'created_at' => $row['created_at'] ?? null,
            'avatar' => $row['UserAvatar'] ?? null,
        ];
    }

    // retourner les commandes de l'utilisateur par ID
    public function getUserOrders(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.IdOrder       AS order_id,
                    o.created_at    AS order_date,
                    o.OrderTotalAmount AS total_amount,
                    o.Status,
                    oi.IdMenu       AS menu_item_id,
                    oi.Quantity,
                    oi.PriceAtTime,
                    m.ItemName      AS item_name,
                    m.ImageURL      AS item_image
             FROM orders o
             LEFT JOIN orderitems oi ON o.IdOrder = oi.IdOrder
             LEFT JOIN menu m        ON oi.IdMenu  = m.IdMenu
             WHERE o.IdUser = ?
             ORDER BY o.created_at DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orderId = $row['order_id'];
                // si order ID n'est pas dans array orders init structure
                if (!isset($orders[$orderId])) {
                    $orders[$orderId] = [
                        'id' => $orderId,
                        'date' => $row['order_date'],
                        'total' => $row['total_amount'],
                        'status' => $row['Status'],
                        'items' => [],
                    ];
                }
                // si orderitems existe add item
                if ($row['item_name']) {
                    $orders[$orderId]['items'][] = [
                        'menu_item_id' => (int) $row['menu_item_id'],
                        'name' => $row['item_name'],
                        'qty' => (int) $row['Quantity'],
                        'price' => (float) $row['PriceAtTime'],
                        'image' => $row['item_image'] ?? '',
                    ];
                }
            }
        }

        $stmt->close();
        // return orders indexe numeriquement
        return array_values($orders);
    }

    // retourner les reservations de l'utilisateur par ID
    public function getUserReservations(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.IdReservation,
                    r.GuestNumber,
                    r.SpecialNotes,
                    r.ReservationDate,
                    r.ReservationTime,
                    r.created_at,
                    r.IdTable,
                    t.TableNumber
             FROM reservations r
             JOIN restauranttable t ON r.IdTable = t.IdTable
             WHERE r.IdUser = ?
             ORDER BY r.ReservationDate DESC, r.ReservationTime DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $reservations = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // ajoute les donne a l array
                $resDate = $row['ReservationDate'];
                $reservations[] = [
                    'id' => (int) $row['IdReservation'],
                    'date' => $resDate,
                    'time' => $row['ReservationTime'],
                    'guests' => (int) $row['GuestNumber'],
                    'table_number' => $row['TableNumber'],
                    'table_id' => (int) $row['IdTable'],
                    'special_notes' => $row['SpecialNotes'] ?? '',
                    'created_at' => $row['created_at'],
                    'can_modify' => canModifyReservationByDate($resDate),
                ];
            }
        }

        $stmt->close();
        return $reservations;
    }

    // modifier le profil de l'utilisateur par ID
    public function updateProfile(int $userId, string $username): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET UserName = ? WHERE IdUser = ?');
        $stmt->bind_param('si', $username, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }


    // modifier l'avatar de l'utilisateur par ID
    public function updateAvatar(int $userId, string $avatarPath): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET UserAvatar = ? WHERE IdUser = ?');
        $stmt->bind_param('si', $avatarPath, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // retourner le hash du mot de passe de l'utilisateur par ID
    public function getPasswordHash(int $userId): ?string
    {
        $stmt = $this->db->prepare('SELECT UserPassword FROM users WHERE IdUser = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        // mettre le resultat dans $hashed
        $stmt->bind_result($hashed);
        // $found -> true si on a pue recuperer le hash
        $found = $stmt->fetch();
        $stmt->close();

        // return le password hashed si found = true
        return $found ? $hashed : null;
    }

    // modifier le mot de passe de l'utilisateur par ID
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET UserPassword = ? WHERE IdUser = ?');
        $stmt->bind_param('si', $passwordHash, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // supprimer le compte de l'utilisateur par ID
    public function deleteAccount(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE IdUser = ?');
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    // retourner les reviews de l'utilisateur par ID
    public function getUserReviews(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.IdReviews, r.UserRating, r.RatingDescription, r.created_at,
                    m.IdMenu, m.ItemName, m.ImageURL
             FROM reviews r
             JOIN menu m ON r.IdMenu = m.IdMenu
             WHERE r.IdUser = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reviews[] = [
                    'id' => (int) $row['IdReviews'],
                    'rating' => (int) $row['UserRating'],
                    'description' => $row['RatingDescription'] ?? '',
                    'created_at' => $row['created_at'],
                    'menu_item_id' => (int) $row['IdMenu'],
                    'item_name' => $row['ItemName'],
                    'item_image' => $row['ImageURL'] ?? '',
                ];
            }
        }
        $stmt->close();
        return $reviews;
    }

    // supprimer une review de l'utilisateur par ID
    public function deleteUserReview(int $reviewId, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM reviews WHERE IdReviews = ? AND IdUser = ?');
        $stmt->bind_param('ii', $reviewId, $userId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    // retourner la reservation de l'utilisateur par ID
    public function getReservationForUser(int $reservationId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.IdReservation, r.GuestNumber, r.SpecialNotes, r.ReservationDate,
                    r.ReservationTime, r.IdTable, t.TableNumber
             FROM reservations r
             JOIN restauranttable t ON r.IdTable = t.IdTable
             WHERE r.IdReservation = ? AND r.IdUser = ?
             LIMIT 1"
        );
        $stmt->bind_param('ii', $reservationId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['IdReservation'],
            'date' => $row['ReservationDate'],
            'time' => $row['ReservationTime'],
            'guests' => (int) $row['GuestNumber'],
            'table_id' => (int) $row['IdTable'],
            'table_number' => (int) $row['TableNumber'],
            'special_notes' => $row['SpecialNotes'] ?? '',
        ];
    }

    // modifier la reservation de l'utilisateur par ID
    public function updateUserReservation(
        int $reservationId,
        int $userId,
        string $date,
        string $time,
        int $guests,
        ?string $specialNotes
    ): array {
        $existing = $this->getReservationForUser($reservationId, $userId);
        // si la reservation n'existe pas, retourner un message d'erreur
        if (!$existing) {
            return ['success' => false, 'message' => 'Reservation not found.'];
        }

        // si la reservation ne peut pas etre modifiee, retourner un message d'erreur
        if (!canModifyReservationByDate($existing['date'])) {
            return [
                'success' => false,
                'message' => 'Reservations can only be edited at least 3 days before the reservation date.',
            ];
        }

        // si le nombre de guests est inferieur a 1 ou superieur a 10, retourner un message d'erreur
        if ($guests < 1 || $guests > 10) {
            return ['success' => false, 'message' => 'Guest count must be between 1 and 10.'];
        }

        // si la date n'est pas valide, retourner un message d'erreur
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['success' => false, 'message' => 'Invalid date format.'];
        }

        // si la date est dans le passé, retourner un message d'erreur
        $today = new DateTime('today');
        $newDate = new DateTime($date);
        if ($newDate < $today) {
            return ['success' => false, 'message' => 'Cannot book a date in the past.'];
        }

        // si la date ne peut pas etre modifiee, retourner un message d'erreur
        if (!canModifyReservationByDate($date)) {
            return [
                'success' => false,
                'message' => 'The new date must still allow changes (at least 3 days from today).',
            ];
        }

        // normaliser le temps de la reservation
        $time = normalizeReservationTime($time);
        // trouver la meilleure table disponible
        $table = findBestAvailableTable($this->db, $guests, $date, $time, $reservationId);

        // si aucune table disponible, retourner un message d'erreur
        if ($table === null) {
            return [
                'success' => false,
                'message' => 'No availability for the selected date and time. Please choose another slot.',
            ];
        }

        $stmt = $this->db->prepare(
            'UPDATE reservations
             SET IdTable = ?, ReservationDate = ?, ReservationTime = ?, GuestNumber = ?, SpecialNotes = ?
             WHERE IdReservation = ? AND IdUser = ?'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error.'];
        }

        $stmt->bind_param('issisii', $table['id'], $date, $time, $guests, $specialNotes, $reservationId, $userId);
        $ok = $stmt->execute();
        $stmt->close();

        // si la reservation n'a pas pu etre modifiee, retourner un message d'erreur
        if (!$ok) {
            return ['success' => false, 'message' => 'Failed to update reservation.'];
        }

        // retourner un message de succes
        return [
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'table_number' => $table['number'],
        ];
    }

    // annuler la reservation de l'utilisateur par ID
    public function cancelUserReservation(int $reservationId, int $userId): array
    {
        $existing = $this->getReservationForUser($reservationId, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Reservation not found.'];
        }

        if (!canModifyReservationByDate($existing['date'])) {
            return [
                'success' => false,
                'message' => 'Reservations can only be cancelled at least 3 days before the reservation date.',
            ];
        }

        $stmt = $this->db->prepare('DELETE FROM reservations WHERE IdReservation = ? AND IdUser = ?');
        $stmt->bind_param('ii', $reservationId, $userId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();

        if (!$ok) {
            return ['success' => false, 'message' => 'Failed to cancel reservation.'];
        }

        return ['success' => true, 'message' => 'Reservation cancelled successfully.'];
    }
}
