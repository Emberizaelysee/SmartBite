<?php
require_once __DIR__ . '/../api/profile/reservation_helpers.php';

// DashboardModel - acces aux donnees du dashboard admin
class DashboardModel
{
    private mysqli $db;

    // Constructor de connection
    public function __construct(mysqli $db)
    {
        // donne la connection db a la propriete
        $this->db = $db;
    }

    // Menu 

    // retourner tous les items de menu jointes avec le nom de la categorie.
    public function getMenuItems(): array
    {
        $items = [];
        $result = $this->db->query(
            "SELECT m.IdMenu, m.ItemName, m.ItemDescription, m.ItemIngredients,
                    m.ItemPrice, m.ImageURL, c.CategoryName, m.IdCategory
             FROM menu m
             LEFT JOIN category c ON m.IdCategory = c.IdCategory
             ORDER BY c.CategoryName, m.ItemName"
        );

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = [
                    'id' => (int) $row['IdMenu'],
                    'name' => $row['ItemName'],
                    'description' => $row['ItemDescription'],
                    'ingredients' => $row['ItemIngredients'] ?? '',
                    'price' => (float) $row['ItemPrice'],
                    'category' => $row['CategoryName'] ?? '',
                    'category_id' => (int) $row['IdCategory'],
                    'image' => $row['ImageURL'],
                ];
            }
        }

        return $items;
    }

    // retourner toutes les categories pour les dropdowns
    public function getCategories(): array
    {
        $cats = [];
        $result = $this->db->query("SELECT IdCategory, CategoryName FROM category ORDER BY CategoryName");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cats[] = ['id' => (int) $row['IdCategory'], 'name' => $row['CategoryName']];
            }
        }
        return $cats;
    }

    // Reservations

    // retourner toutes les reservations jointes avec les informations de la table et le nom d'utilisateur
    public function getAllReservations(): array
    {
        $reservations = [];
        // recuperer toutes les reservations
        $result = $this->db->query(
            "SELECT r.IdReservation, r.ReservationDate, r.ReservationTime,
                    r.GuestNumber, r.SpecialNotes, r.created_at,
                    r.IdTable, r.IdUser,
                    t.TableNumber, t.TableCapacity,
                    u.UserName
             FROM reservations r
             JOIN restauranttable t ON r.IdTable = t.IdTable
             JOIN users u           ON r.IdUser  = u.IdUser
             ORDER BY r.ReservationDate DESC, r.ReservationTime DESC"
        );

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                //ajout des reservations dans le tableau reservations
                $reservations[] = [
                    'id' => (int) $row['IdReservation'],
                    'date' => $row['ReservationDate'],
                    'time' => $row['ReservationTime'],
                    'guests' => (int) $row['GuestNumber'],
                    'table_number' => $row['TableNumber'],
                    'table_id' => (int) $row['IdTable'],
                    'user_id' => (int) $row['IdUser'],
                    'customer_name' => $row['UserName'] ?? 'Unknown',
                    'special_notes' => $row['SpecialNotes'] ?? '',
                    'created_at' => $row['created_at'],
                ];
            }
        }

        return $reservations;
    }

    // Orders

    // retourner toutes les commandes jointes avec le nom d'utilisateur, chacune incluant leurs items de commande
    public function getAllOrders(): array
    {
        $orders = [];
        // recuperer toutes les commandes
        $result = $this->db->query(
            "SELECT o.IdOrder, o.OrderTotalAmount, o.Status,
                    o.SpecialInstructions, o.created_at, o.IdUser,
                    u.UserName
             FROM orders o
             LEFT JOIN users u ON o.IdUser = u.IdUser
             ORDER BY o.created_at DESC"
        );

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // on recupere l id de la commande
                $orderId = (int) $row['IdOrder'];
                // on ajoute la commande dans le tableau orders
                $orders[] = [
                    'id' => $orderId,
                    'user_id' => (int) $row['IdUser'],
                    'username' => $row['UserName'] ?? 'Unknown',
                    'total_amount' => (float) $row['OrderTotalAmount'],
                    'status' => $row['Status'],
                    'special_instructions' => $row['SpecialInstructions'] ?? '',
                    'order_date' => $row['created_at'],
                    'items' => $this->getOrderItems($orderId),
                ];
            }
        }

        return $orders;
    }

    // Users

    // retourner tous les utilisateurs
    public function getAllUsers(): array
    {
        $users = [];
        // recuperer toutes les users
        $result = $this->db->query(
            "SELECT IdUser, UserName, UserEmail, UserRole, created_at
             FROM users ORDER BY created_at DESC"
        );

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // on ajoute les users dans le tableau users
                $users[] = [
                    'id' => (int) $row['IdUser'],
                    'username' => $row['UserName'],
                    'email' => $row['UserEmail'],
                    'role' => $row['UserRole'],
                    'created_at' => $row['created_at'],
                ];
            }
        }

        return $users;
    }

    //  Reviews

    // retourner toutes les reviews jointes avec les informations de l'utilisateur et de l'item de menu
    public function getReviews(): array
    {
        $reviews = [];
        $ratingSum = 0;
        // recuperer toutes les reviews
        $result = $this->db->query(
            "SELECT r.IdReviews, r.UserRating, r.RatingDescription, r.created_at,
                    r.IdUser, r.IdMenu,
                    u.UserName, u.UserAvatar,
                    m.ItemName
             FROM reviews r
             LEFT JOIN users u ON r.IdUser = u.IdUser
             LEFT JOIN menu  m ON r.IdMenu  = m.IdMenu
             ORDER BY r.created_at DESC"
        );

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // on ajoute les reviews dans le tableau reviews
                $reviews[] = [
                    'id' => (int) $row['IdReviews'],
                    'user_id' => (int) $row['IdUser'],
                    'menu_id' => (int) $row['IdMenu'],
                    'author_name' => $row['UserName'] ?? 'Anonymous',
                    'rating' => (int) $row['UserRating'],
                    'content' => $row['RatingDescription'] ?? '',
                    'created_at' => $row['created_at'],
                    'avatar' => $row['UserAvatar'] ?? null,
                    'item_name' => $row['ItemName'] ?? '',
                ];
                $ratingSum += (int) $row['UserRating'];
            }
        }

        $total = count($reviews);
        return [
            'data' => $reviews,
            'total' => $total,
            'average_rating' => $total > 0 ? round($ratingSum / $total, 1) : 0,
        ];
    }

    // Stats

    // revenue hebdomadaire en utilisant orders.created_at et OrderTotalAmount
    public function getWeeklyRevenue(): array
    {
        $rows = [];
        // recuperer le revenue de la semaine
        $res = $this->db->query(
            "SELECT DAYNAME(created_at) AS day_name, SUM(OrderTotalAmount) AS revenue
             FROM orders
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DAYNAME(created_at), DAYOFWEEK(created_at)
             ORDER BY DAYOFWEEK(created_at)"
        );

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                // on ajoute le revenue dans le tableau rows
                $rows[] = ['day' => $row['day_name'], 'revenue' => (float) $row['revenue']];
            }
        }

        return $rows;
    }

    //  Suppression generique

    // supprimer un enregistrement par clé primaire. Maps each allowed table to its real PK.
    public function deleteById(string $table, int $id): bool
    {
        // recuperer les tables et leurs clés primaires
        $pkMap = [
            'menu' => 'IdMenu',
            'reservations' => 'IdReservation',
            'reviews' => 'IdReviews',
            'users' => 'IdUser',
            'orders' => 'IdOrder',
            'restauranttable' => 'IdTable',
        ];

        // verifier si la table existe
        if (!isset($pkMap[$table])) {
            return false;
        }
        // recuperer la clé primaire de la table
        $pk = $pkMap[$table];
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE {$pk} = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    // supprimer une commande
    public function deleteOrderFully(int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }
        $this->deleteOrderItems($orderId);
        return $this->deleteById('orders', $orderId);
    }

    // supprimer un item de menu
    public function deleteMenuItemFully(int $menuId): bool
    {
        if ($menuId <= 0) {
            return false;
        }

        $stmtReviews = $this->db->prepare('DELETE FROM reviews WHERE IdMenu = ?');
        if ($stmtReviews) {
            $stmtReviews->bind_param('i', $menuId);
            $stmtReviews->execute();
            $stmtReviews->close();
        }

        $stmtItems = $this->db->prepare('DELETE FROM orderitems WHERE IdMenu = ?');
        if ($stmtItems) {
            $stmtItems->bind_param('i', $menuId);
            $stmtItems->execute();
            $stmtItems->close();
        }

        return $this->deleteById('menu', $menuId);
    }

    // supprimer un utilisateur
    public function deleteUserFully(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $stmtRes = $this->db->prepare('DELETE FROM reservations WHERE IdUser = ?');
        if ($stmtRes) {
            $stmtRes->bind_param('i', $userId);
            $stmtRes->execute();
            $stmtRes->close();
        }

        $stmtReviews = $this->db->prepare('DELETE FROM reviews WHERE IdUser = ?');
        if ($stmtReviews) {
            $stmtReviews->bind_param('i', $userId);
            $stmtReviews->execute();
            $stmtReviews->close();
        }

        $orderIds = [];
        $res = $this->db->prepare('SELECT IdOrder FROM orders WHERE IdUser = ?');
        if ($res) {
            $res->bind_param('i', $userId);
            $res->execute();
            $rows = $res->get_result();
            while ($row = $rows->fetch_assoc()) {
                $orderIds[] = (int) $row['IdOrder'];
            }
            $res->close();
        }
        foreach ($orderIds as $orderId) {
            $this->deleteOrderFully($orderId);
        }

        return $this->deleteById('users', $userId);
    }

    // supprimer une table de restaurant
    public function deleteRestaurantTableFully(int $tableId): bool
    {
        if ($tableId <= 0) {
            return false;
        }

        $stmtRes = $this->db->prepare('DELETE FROM reservations WHERE IdTable = ?');
        if ($stmtRes) {
            $stmtRes->bind_param('i', $tableId);
            $stmtRes->execute();
            $stmtRes->close();
        }

        return $this->deleteById('restauranttable', $tableId);
    }

    // statut / role

    // modifier le statut d'une commande
    public function setOrderStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE orders SET Status = ? WHERE IdOrder = ?');
        $stmt->bind_param('si', $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // modifier le role d'un utilisateur
    public function setUserRole(int $id, string $role): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET UserRole = ? WHERE IdUser = ?');
        $stmt->bind_param('si', $role, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    //  Menu CRUD

    // modifier un item de menu existant
    public function updateMenuItem(int $id, string $name, string $description, string $ingredients, float $price, int $categoryId, string $imageUrl): array
    {
        $stmt = $this->db->prepare(
            'UPDATE menu SET ItemName=?, ItemDescription=?, ItemIngredients=?, ItemPrice=?, IdCategory=?, ImageURL=? WHERE IdMenu=?'
        );
        $stmt->bind_param('sssdisi', $name, $description, $ingredients, $price, $categoryId, $imageUrl, $id);
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error' => $error];
    }

    // ajouter un nouvel item de menu
    public function addMenuItem(string $name, string $description, string $ingredients, float $price, int $categoryId, string $imageUrl): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO menu (ItemName, ItemDescription, ItemIngredients, ItemPrice, IdCategory, ImageURL) VALUES (?,?,?,?,?,?)'
        );
        $stmt->bind_param('sssdis', $name, $description, $ingredients, $price, $categoryId, $imageUrl);
        $success = $stmt->execute();
        $error = $stmt->error;
        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => $success, 'error' => $error, 'new_id' => $newId];
    }

    // Reservation CRUD 

    // fonction pour verifier la capacite de la table et la disponibilite de la slot
    public function validateReservationSlot(
        int $tableId,
        string $date,
        string $time,
        int $guests,
        ?int $excludeReservationId = null
    ): array {
        if ($guests < 1) {
            return ['valid' => false, 'message' => 'Guest count must be at least 1.'];
        }

        $time = normalizeReservationTime($time);

        $stmt = $this->db->prepare(
            'SELECT TableCapacity, IsActive FROM restauranttable WHERE IdTable = ? LIMIT 1'
        );
        if (!$stmt) {
            return ['valid' => false, 'message' => 'Database error.'];
        }
        $stmt->bind_param('i', $tableId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return ['valid' => false, 'message' => 'Table not found.'];
        }
        if ((int) $row['IsActive'] !== 1) {
            return ['valid' => false, 'message' => 'This table is not active.'];
        }
        if ((int) $row['TableCapacity'] < $guests) {
            return [
                'valid' => false,
                'message' => 'Table capacity (' . (int) $row['TableCapacity'] . ') is less than the number of guests (' . $guests . ').',
            ];
        }

        if (tableHasReservationConflict($this->db, $tableId, $date, $time, $excludeReservationId)) {
            return [
                'valid' => false,
                'message' => 'This table is already reserved for that date and time slot.',
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    // ajouter une nouvelle reservation
    public function addReservation(int $userId, int $tableId, string $date, string $time, int $guests, ?string $specialNotes): array
    {
        $time = normalizeReservationTime($time);
        $check = $this->validateReservationSlot($tableId, $date, $time, $guests);
        if (!$check['valid']) {
            return ['success' => false, 'error' => $check['message'], 'new_id' => 0];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO reservations (IdUser, IdTable, ReservationDate, ReservationTime, GuestNumber, SpecialNotes) VALUES (?,?,?,?,?,?)'
        );
        $stmt->bind_param('iissis', $userId, $tableId, $date, $time, $guests, $specialNotes);
        $success = $stmt->execute();
        $error = $stmt->error;
        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => $success, 'error' => $error, 'new_id' => $newId];
    }

    // modifier une reservation existante
    public function updateReservation(int $id, int $userId, int $tableId, string $date, string $time, int $guests, ?string $specialNotes): array
    {
        $time = normalizeReservationTime($time);
        $check = $this->validateReservationSlot($tableId, $date, $time, $guests, $id);
        if (!$check['valid']) {
            return ['success' => false, 'error' => $check['message']];
        }

        $stmt = $this->db->prepare(
            'UPDATE reservations SET IdUser=?, IdTable=?, ReservationDate=?, ReservationTime=?, GuestNumber=?, SpecialNotes=? WHERE IdReservation=?'
        );
        $stmt->bind_param('iissisi', $userId, $tableId, $date, $time, $guests, $specialNotes, $id);
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error' => $error];
    }

    // Review CRUD

    // ajouter une nouvelle review
    public function addReview(int $userId, int $menuId, int $rating, ?string $description): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reviews (IdUser, IdMenu, UserRating, RatingDescription) VALUES (?,?,?,?)'
        );
        $stmt->bind_param('iiis', $userId, $menuId, $rating, $description);
        $success = $stmt->execute();
        $error = $stmt->error;
        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => $success, 'error' => $error, 'new_id' => $newId];
    }

    // User CRUD

    // ajouter un nouvel utilisateur
    public function addUser(string $username, string $email, string $passwordHash, string $role): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (UserName, UserEmail, UserPassword, UserRole) VALUES (?,?,?,?)'
        );
        $stmt->bind_param('ssss', $username, $email, $passwordHash, $role);
        $success = $stmt->execute();
        $error = $stmt->error;
        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => $success, 'error' => $error, 'new_id' => $newId];
    }

    // Order CRUD

    // ajouter une nouvelle commande
    public function addOrder(int $userId, float $totalAmount, string $status, ?string $specialInstructions): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orders (IdUser, OrderTotalAmount, Status, SpecialInstructions) VALUES (?,?,?,?)'
        );
        $stmt->bind_param('idss', $userId, $totalAmount, $status, $specialInstructions);
        $success = $stmt->execute();
        $error = $stmt->error;
        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => $success, 'error' => $error, 'new_id' => $newId];
    }

    // modifier une commande existante
    public function updateOrder(int $id, int $userId, float $totalAmount, string $status, ?string $specialInstructions): array
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET IdUser=?, OrderTotalAmount=?, Status=?, SpecialInstructions=? WHERE IdOrder=?'
        );
        $stmt->bind_param('idssi', $userId, $totalAmount, $status, $specialInstructions, $id);
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error' => $error];
    }

    // Order Items CRUD

    // retourner tous les orderitems pour une commande donnee, joint avec le nom du plat
    public function getOrderItems(int $orderId): array
    {
        $items = [];
        $stmt = $this->db->prepare(
            // recuperer les items de l'order avec le nom du plat
            "SELECT oi.IdOrderItems, oi.Quantity, oi.PriceAtTime, oi.IdMenu, m.ItemName
             FROM orderitems oi
             JOIN menu m ON oi.IdMenu = m.IdMenu
             WHERE oi.IdOrder = ?"
        );
        // si statement n existe pas return array vide
        if (!$stmt)
            return $items;
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        // fetch les items
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int) $row['IdOrderItems'],
                'menu_id' => (int) $row['IdMenu'],
                'name' => $row['ItemName'],
                'quantity' => (int) $row['Quantity'],
                'price' => (float) $row['PriceAtTime'],
            ];
        }
        $stmt->close();
        return $items;
    }

    // ajouter tous les orderitems pour une commande donnee
    public function addOrderItems(int $orderId, array $items): array
    {
        if (empty($items))
            return ['success' => true, 'error' => ''];

        $stmt = $this->db->prepare(
            'INSERT INTO orderitems (IdMenu, IdOrder, Quantity, PriceAtTime) VALUES (?,?,?,?)'
        );
        if (!$stmt)
            return ['success' => false, 'error' => $this->db->error];

        $errors = [];
        foreach ($items as $item) {
            $menuId = (int) ($item['menu_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price_at_time'] ?? 0);
            if ($menuId <= 0 || $qty <= 0)
                continue;
            $stmt->bind_param('iiid', $menuId, $orderId, $qty, $price);
            if (!$stmt->execute())
                $errors[] = $stmt->error;
        }
        $stmt->close();
        return ['success' => empty($errors), 'error' => implode('; ', $errors)];
    }

    // supprimer tous les orderitems pour une commande donnee (utilise avant de re-insert sur edit)
    public function deleteOrderItems(int $orderId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orderitems WHERE IdOrder = ?');
        if (!$stmt)
            return false;
        $stmt->bind_param('i', $orderId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // User Search

    // recherche d'un utilisateur par nom ou email (max 10 resultats)
    public function searchUsers(string $term): array
    {
        $users = [];
        $pattern = '%' . $term . '%';
        $stmt = $this->db->prepare(
            // recherche d'un utilisateur par nom ou email
            "SELECT IdUser, UserName, UserEmail FROM users
              WHERE UserName LIKE ? OR UserEmail LIKE ?
              ORDER BY UserName LIMIT 10"
        );
        // si statement n existe pas return array vide
        if (!$stmt)
            return $users;
        $stmt->bind_param('ss', $pattern, $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // fetch les utilisateurs
            $users[] = [
                'id' => (int) $row['IdUser'],
                'username' => $row['UserName'],
                'email' => $row['UserEmail'],
            ];
        }
        $stmt->close();
        return $users;
    }

    // retourner toutes les tables de restaurant
    public function getAllRestaurantTables(): array
    {
        $tables = [];
        $result = $this->db->query(
            "SELECT IdTable, TableNumber, TableCapacity, IsActive
             FROM restauranttable
             ORDER BY TableNumber"
        );
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tables[] = [
                    'id' => (int) $row['IdTable'],
                    'number' => (int) $row['TableNumber'],
                    'capacity' => (int) $row['TableCapacity'],
                    'is_active' => (int) $row['IsActive'] === 1,
                ];
            }
        }
        return $tables;
    }

    // ajouter une nouvelle table de restaurant
    public function addRestaurantTable(int $number, int $capacity, int $isActive): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO restauranttable (TableNumber, TableCapacity, IsActive) VALUES (?,?,?)'
        );
        $stmt->bind_param('iii', $number, $capacity, $isActive);
        $success = $stmt->execute();
        $error = $stmt->error;
        $newId = (int) $stmt->insert_id;
        $stmt->close();
        return ['success' => $success, 'error' => $error, 'new_id' => $newId];
    }

    // modifier une table de restaurant existante
    public function updateRestaurantTable(int $id, int $number, int $capacity, int $isActive): array
    {
        $stmt = $this->db->prepare(
            'UPDATE restauranttable SET TableNumber=?, TableCapacity=?, IsActive=? WHERE IdTable=?'
        );
        $stmt->bind_param('iiii', $number, $capacity, $isActive, $id);
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error' => $error];
    }

    // Tables Lookup

    // retourner toutes les tables de restaurant actives pour le modal de reservation
    public function getTables(): array
    {
        $tables = [];
        $result = $this->db->query(
            // recuperer les tables actives
            "SELECT IdTable, TableNumber, TableCapacity
              FROM restauranttable
              WHERE IsActive = 1
              ORDER BY TableNumber"
        );
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // fetch les tables
                $tables[] = [
                    'id' => (int) $row['IdTable'],
                    'number' => (int) $row['TableNumber'],
                    'capacity' => (int) $row['TableCapacity'],
                ];
            }
        }
        return $tables;
    }
}
?>