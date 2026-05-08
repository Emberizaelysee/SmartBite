<?php
// Start session for cart
session_start();

//connect file
require_once __DIR__ . '/../Backend/config/connection.php';



// function files

require_once __DIR__ . '/../Backend/api/menu/index-function.php';
require_once __DIR__ . '/../Backend/api/cart/cart-function.php';


// Handle cart form submissions (direct approach)
if (isset($_POST['add_to_cart'])) {
    addToCart($_POST['item_id'], isset($_POST['quantity']) ? $_POST['quantity'] : 1);
}
if (isset($_POST['remove_item'])) {
    removeFromCart($_POST['item_id']);
}
if (isset($_POST['update_qty'])) {
    updateCartQuantity($_POST['item_id'], $_POST['quantity']);
}
if (isset($_POST['clear_cart'])) {
    clearCart();
}




?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SmartBite Cart</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

<!-- CSS -->
<link rel="stylesheet" href="css/cart.css">
<link rel="stylesheet" href="css/main.css">

</head>

<body>

<!-- NAVBAR -->
<div class="container-fluid p-0">

<nav class="navbar navbar-expand-lg shadow-sm">
<div class="container">

<a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
  <div class="logo">
    <span><i class="fa-solid fa-utensils me-2"></i></span>
   <span>Smart</span>Bite
  </div>
</a>

<button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#menu">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="menu">
<ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

<li class="nav-item"><a class="nav-link" href="index.php#menu-section">Menu</a></li>
<li class="nav-item"><a class="nav-link " href="/SmartBite/Frontend/reservation.html">Reservations</a></li>
<li class="nav-item"><a class="nav-link" href="/SmartBite/Frontend/review.html">Reviews</a></li>
<li class="nav-item">
<a class="nav-link active-page" href="cart.php">
<i class="fa-solid fa-cart-arrow-down"></i> <sup><?php echo getCartItemCount(); ?></sup>
</a>
</li>

<li class="nav-item ms-lg-3 mt-2 mt-lg-0">
<a href="/SmartBite/Frontend/signin.html"><button class="btn btn-green px-4">Log In</button></a>
</li>

</ul>
</div>
</div>
</nav>

<!-- SECOND BAR -->
<nav class="navbar navbar-dark bar">
<div class="container">
    
<?php if(!isset($_SESSION["user_name"])){
      echo'<a class="btn nav-link text-white">Welcome Guest</a>';
 }
 else{
 echo '<a class="btn nav-link text-white">Welcome ' . $_SESSION["user_name"] . '</a>';
 }
?>

</div>
</nav>

<!-- CART -->
<div class="container my-5">

<div class="section-header">
<h2>Your Cart</h2>
<div class="divider"></div>
</div>

<div class="table-responsive">
<table class="table text-center align-middle">

<thead>
<tr>
<th>Product</th>
<th>Image</th>
<th>Qty</th>
<th>Price</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php displayCartItems(); ?>

<?php if (getCartItemCount() > 0): ?>
 <form method="post">
    <tr><td colspan="5" class="text-center py-5">
               <button type="submit" name="clear_cart" value="1" class="btn btn-remove"> Clear cart </button>
              </td></tr>
</form>
<?php endif; ?>

</tbody>
</table>
</div>


<!--special request box-->
<div class="order-notes mt-4">
    <h5 class="mb-3"><i class="fa-solid fa-note-sticky me-2"></i> Special Requests </h5>
    <textarea name="special_request" class="form-control note-input" placeholder="Any special requests on order..."></textarea>
</div>


<!-- SUBTOTAL -->
<div class="cart-summary">
<?php displayCartSummary(); ?>

<div class="cart-actions">
<a href="index.php" class="btn-outline-green">Continue Shopping</a>


<!--if there is no item the "checkout" button should not show-->
<?php if (getCartItemCount() > 0): ?>
<a href="purchase.html" class="btn btn-green">Checkout</a>
<?php endif; ?>


</div>
</div>

</div>
</div>

        <!-- FOOTER -->
       <footer class="text-center py-4 text-muted border-top small">
        © 2026 SmartBite Restaurants. All rights reserved.
</footer>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  


</body>
</html>
