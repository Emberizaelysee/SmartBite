<?php
require_once __DIR__ . '/../../config/connection.php';

if (isset($_GET['cat'])) {
    $cat_id = intval($_GET['cat']);
    $query = "SELECT IdMenu, ItemName, ImageURL FROM menu WHERE IdCategory = $cat_id ORDER BY ItemName";
} else {
    $query = "SELECT IdMenu, ItemName, ImageURL FROM menu ORDER BY ItemName";
}

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $id   = $row['IdMenu'];
    $name = htmlspecialchars($row['ItemName']);
    $img  = htmlspecialchars($row['ImageURL']);

    echo '
    <tr>
        <td>' . $name . '</td>
        <td><img src="' . $img . '" width="70" height="60" class="rounded" style="object-fit:cover;" alt="' . $name . '"></td>
        <td><input type="radio" name="dish_id" value="' . $id . '" required></td>
    </tr>';
}
?>