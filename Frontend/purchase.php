
<?php
session_start();

require_once __DIR__ . '/../Backend/config/connection.php';
require_once __DIR__ . '/../Backend/api/cart/cart-function.php';
require_once __DIR__ . '/../Backend/api/purchase/purchase-function.php';
require_once __DIR__ . '/../Backend/api/purchase/send-order.php';

$userId = (int)$_SESSION['user_id'];



if(isset($_SESSION['pending_order_id'])){
  $pendingOrderId=$_SESSION['pending_order_id'] ;
} else{
  $pendingOrderId=0;
};


if($pendingOrderId <= 0) {
    header('Location: cart.php');
    exit;
}

$orderData = getOrderDisplayRows($conn, $pendingOrderId);
$displayRows = $orderData['displayRows'];
$totalPaid = (float)$orderData['totalPaid'];

$nextOrderId = $pendingOrderId;


if(isset($_POST['confirm_order'])) {
  $orderId = $_SESSION['pending_order_id'];
  if ($orderId > 0) {
   confirmOrder($conn, $userId, $orderId);
   
   // Send email of the order details
   try {
     sendOrderEmailForUser($conn, $userId, $orderId);
   } catch (Throwable $e) {
     // ignore email errors
   }
}


  clearCart();
  unset($_SESSION['pending_order_id']);

  // Send email with order details (best-effort)
  try {
    sendOrderEmailForUser($conn, $userId, $orderId);
  } catch (Throwable $e) {
    // ignore email errors
  }

  echo "<script>alert('An email has been sent to confirm the order.');window.location='index.php';</script>";
  exit;

}


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartBite Purchase</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<link rel="stylesheet" href="css/purchase.css">
<link rel="stylesheet" href="css/main.css">
</head>
<body>
<div class="container my-5">

<div class="section-header">
  <h2>Order Summary</h2>
  <div class="divider"></div>
</div>

<div class="order-box">
  <p>Order Number: <strong>#<?php echo htmlspecialchars((string)$nextOrderId); ?></strong></p>
</div>

<div class="table-responsive">
<table class="table text-center align-middle">
<thead>
<tr>
  <th>Product</th>
  <th>Quantity</th>
  <th>Price</th>
</tr>
</thead>
<tbody>
<?php if (empty($displayRows)): ?>
<tr>
  <td colspan="3" class="text-center py-5">No items found.</td>
</tr>
<?php else: ?>
<?php foreach ($displayRows as $row): ?>
<tr>
  <td><?php echo htmlspecialchars($row['item_name']); ?></td>
  <td><?php echo (int)$row['quantity']; ?></td>
  <td>$<?php echo number_format((float)$row['item_price'], 2); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
<tfoot>
<tr>
  <td colspan="2" class="text-end">Total:</td>
  <td><strong>$<?php echo number_format((float)$totalPaid, 2); ?></strong></td>
</tr>
</tfoot>
</table>
</div>

<div class="cart-summary">
  <h4>Total Paid: <strong>$<?php echo number_format((float)$totalPaid, 2); ?></strong></h4>

  <div class="cart-actions">
    <form method="post" action="purchase.php" style="display:inline">
      <button type="submit" name="confirm_order" value="1" class="btn btn-green">Confirm order</button>
    </form>

    <a href="index.php" class="btn-outline-green">
      <i class="fa-solid fa-arrow-left"></i> Continue Shopping
    </a>
  </div>
</div>

</div>

<footer class="text-center py-4 text-muted border-top small">
  © 2026 SmartBite Restaurants. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

