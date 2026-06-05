<?php
require_once __DIR__ . '/../../config/connection.php';

$query = "SELECT r.UserRating, r.RatingDescription, r.created_at,
                 u.UserName, m.ItemName
          FROM reviews r
          JOIN users u ON r.IdUser = u.IdUser
          JOIN menu m ON r.IdMenu = m.IdMenu
          ORDER BY r.created_at DESC
          LIMIT 5";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $name    = htmlspecialchars($row['UserName']);
    $rating  = (int) $row['UserRating'];
    $comment = htmlspecialchars($row['RatingDescription']);
    $dish    = htmlspecialchars($row['ItemName']);
    $date    = date('d/m/Y', strtotime($row['created_at']));

    $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

    echo '
    <div class="review-card">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <span class="fw-semibold">' . $name . '</span>
                <p class="text-muted small mb-0">' . $date . '</p>
                <p class="text-muted small mb-0"><em>' . $dish . '</em></p>
            </div>
            <span class="review-stars">' . $stars . '</span>
        </div>
        <p class="text-muted small mb-0">' . $comment . '</p>
    </div>';
}
?>