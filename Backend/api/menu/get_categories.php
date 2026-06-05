<?php
require_once __DIR__ . '/../../config/connection.php';

header('Content-Type: application/json');

$query  = "SELECT IdCategory, CategoryName FROM category ORDER BY CategoryName";
$result = mysqli_query($conn, $query);

$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = [
        'IdCategory'   => $row['IdCategory'],
        'CategoryName' => $row['CategoryName']
    ];
}

echo json_encode($categories);
?>