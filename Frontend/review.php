<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartBite - Review</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/review.css">
</head>

<body>

<div class="container min-vh-100 d-flex flex-column justify-content-center py-4">

  <!-- LOGO -->
  <div class="d-flex justify-content-center mb-3">
    <a href="index.php" class="text-decoration-none">
      <div class="logo">
        <i class="fa-solid fa-utensils me-2 icon-green"></i>
        <span>Smart</span>Bite
      </div>
    </a>
  </div>

  <div class="row justify-content-center align-items-start g-4">

    <!-- Reviews colonne gauche -->
    <div class="col-12 col-lg-4 order-2 order-lg-1">
      <h5 class="fw-bold mb-3">What others say <span class="text-green">about us</span></h5>
      <?php include '../Backend/api/review/get_reviews.php'; ?>
    </div>

    <!-- Form -->
    <div class="col-12 col-lg-5 order-1 order-lg-2">
      <div class="card card-review">

        <h3 class="title text-center mb-2">
          Leave a <span>Review</span>
        </h3>
        <p class="text-center text-muted mb-4">
          Tell us about your experience at SmartBite
        </p>

        <form id="reviewForm" method="POST" action="../Backend/api/review/submit_review.php">

          <!-- CATEGORY DROPDOWN -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Select a Category</label>
            <select class="form-select" id="categorySelect">
              <option value="">-- Choose a category --</option>
            </select>
          </div>

          <!-- ITEM SELECTION TABLE -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Select a Dish to Review</label>
            <table class="table table-bordered align-middle text-center" id="dishTable">
              <thead class="table-light">
                <tr>
                  <th>Dish</th>
                  <th>Image</th>
                  <th>Select</th>
                </tr>
              </thead>
              <tbody id="dishTableBody"></tbody>
            </table>
          </div>

          <!-- RATING -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Rating</label>
            <div class="stars d-flex gap-1">
              <i class="fa-solid fa-star star" data-value="1"></i>
              <i class="fa-solid fa-star star" data-value="2"></i>
              <i class="fa-solid fa-star star" data-value="3"></i>
              <i class="fa-solid fa-star star" data-value="4"></i>
              <i class="fa-solid fa-star star" data-value="5"></i>
            </div>
            <input type="hidden" id="rating" name="rating">
          </div>

          <!-- TEXTAREA -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Your Review</label>
            <textarea class="form-control" id="review" name="review" rows="4"
              placeholder="Write your experience..."></textarea>
          </div>

          <button type="submit" class="btn btn-green w-100">Submit Review</button>

          <?php if (!isset($_SESSION['user_id'])): ?>
          <p class="text-center mt-3 mb-0 small">
            Log in to leave a review
            <a href="signin.html" class="signin-link">Sign In</a>
          </p>
          <?php endif; ?>

        </form>
      </div>
    </div>

  </div>
</div>

<footer class="text-center py-4 text-muted border-top small">
  © 2026 SmartBite Restaurants. All rights reserved.
</footer>

<script src="js/review.js"></script>
</body>
</html>