<?php
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
                    m.ItemName      AS item_name
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
                    ];
                }
            }
        }

        $stmt->close();
        // return orders indexe numeriquement
        return array_values($orders);
    }

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
                $reservations[] = [
                    'id' => (int) $row['IdReservation'],
                    'date' => $row['ReservationDate'],
                    'time' => $row['ReservationTime'],
                    'guests' => (int) $row['GuestNumber'],
                    'table_number' => $row['TableNumber'],
                    'special_notes' => $row['SpecialNotes'] ?? '',
                    'created_at' => $row['created_at'],
                ];
            }
        }

        $stmt->close();
        return $reservations;
    }

    public function updateProfile(int $userId, string $username): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET UserName = ? WHERE IdUser = ?');
        $stmt->bind_param('si', $username, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }


    public function updateAvatar(int $userId, string $avatarPath): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET UserAvatar = ? WHERE IdUser = ?');
        $stmt->bind_param('si', $avatarPath, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

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

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET UserPassword = ? WHERE IdUser = ?');
        $stmt->bind_param('si', $passwordHash, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteAccount(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE IdUser = ?');
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
