
<?php
// Start session for cart
session_start();

// connect file
require_once __DIR__ . '/../Backend/config/connection.php';

// php function files
require_once __DIR__ . '/../Backend/api/menu/index-function.php';
require_once __DIR__ . '/../Backend/api/cart/cart-function.php';


// remember me function
require_once __DIR__ . '/../Backend/config/check_remember.php';
checkRememberMe($conn);



// Handle cart form submissions (direct approach)
if (isset($_POST['add_to_cart'])) {
    addToCart($_POST['item_id'], isset($_POST['quantity']) ? $_POST['quantity'] : 1);
}
if (isset($_POST['remove_item'])) {
    removeFromCart($_POST['item_id']);
}
if (isset($_POST['update_qty'])) {
    updateCartQuantity($_POST['item_id'],$_POST['quantity']);
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
    <title>SmartBite project</title>
    <!--Bootstrap-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <!--font awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!--css file-->
<link rel="stylesheet" href="css/index.css">
<link rel="stylesheet" href="css/main.css">
<link rel="stylesheet" href="css/chatbot.css">
</head>
<body>
<!--bootstrap js-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <!--Navbar-->
    <div class="container-fluid p-0">
       

<nav class="navbar navbar-expand-lg shadow-sm">
<div class="container">

<a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.html">
  <div class="logo">
    <i class="fa-solid fa-utensils me-2 icon-green"></i>
  <span>Smart</span>Bite
    </div>
    </a>

<button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#menu">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="menu">

    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

      <li class="nav-item">
        <a class="nav-link" href="#menu-section">Menu</a>
      </li>
<li class="nav-item">
<a class="nav-link " href="/SmartBite/Frontend/reservation.html">Reservations</a>
</li>
<li class="nav-item">
<a class="nav-link" href="/SmartBite/Frontend/review.html">Reviews</a>
</li>
<li class="nav-item">
<a class="nav-link" href="cart.php"><i class="fa-solid fa-cart-arrow-down"></i> <sup><?php echo getCartItemCount(); ?></sup></a>
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
<!--Third child-->
<section class="hero">
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <h1>SmartBite</h1>
        <p>Fresh • Organic • Local</p>
        
       <form class="d-flex justify-content-center" action="search-menu.php#menu-section" method="get">
        <div class="search-box">
            <input type="text" placeholder="Search for food" name="search_data"  class="text-search">
           <!-- <button>Search</button>-->
            <button type="submit" class="search-button" name="search_btn" value="Search"> Search</button>

        </div>
</form>
    </div>
</section>



<!--fourth section-->

<div class="row"id="menu-section" >
  <!-- Section Title -->
        <div class="section-header ">
            
            <h2>OUR MENU</h2>
            <div class="divider"></div>
            
        </div>

   <div class="col-md-10  ">
    <!--Products-->
     <div class="row">
       <!---------------------------------------fetching the  menu------------------------------------>

      <?php
        getMenu();
        getMenuByCat();
      ?>

        <!--row end-->
     </div>
     <!--col end-->
   </div>


   
    <!-- Side nav-->
   <div class="col-md-2  sidebar p-0 ">

  
 <!---displaying categorie name from database-->
     <ul class="navbar-nav me-auto ">
      <div class="section-header">
            <h3>CATERORIES</h3>
            <div class="divider"></div>
      
<!-----------------------fetching the  categories--------------------------------------->
      <?php 
         getCat();
    ?>
      
     </ul>
   </div>


</div>


<!--Fifth section -->
<section class="section about-section mb-2">
    <div class="container">

        <!-- Section Title -->
        <div class="section-header">
            <h2>OUR STORY</h2>
            <div class="divider"></div>
            <p>Discover the passion behind our farm-to-table philosophy</p>
        </div>

        <!-- Content -->
        <div class="about">

            <!-- Image -->
            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5" alt="Restaurant interior">
            </div>

            <!-- Text -->
            <div class="about-text">
                <h3>Sustainable Dining Since 2010</h3>

                <p>
                    Founded with a simple mission: to create delicious meals using only
                    the freshest, locally-sourced ingredients while maintaining sustainable practices.
                </p>

                <p>
                    We partner with local farms to bring you seasonal dishes that celebrate
                    natural flavors and organic cooking.
                </p>

                <!-- Features -->
                <div class="about-features">

                    <div class="feature">
                        <i class="fas fa-seedling"></i>
                        <span>100% Organic</span>
                    </div>

                    <div class="feature">
                        <i class="fas fa-truck"></i>
                        <span>Locally Sourced</span>
                    </div>

                    <div class="feature">
                        <i class="fas fa-recycle"></i>
                        <span>Eco-Friendly</span>
                    </div>

                </div>

            </div>
        </div>

    </div>
</section>

 
<!-- Chatbot -->
<button id="chatbot-toggler" aria-label="Open chatbot">
    <span><i class="fa-regular fa-message"></i></span>
    <span><i class="fa-solid fa-xmark"></i></span>
  </button>
  <div class="aiChat">
    <div class="chatbot-popup">
      <div class="chat-header">
        <div class="header-info">
          <i class="fa-solid fa-robot chatbot-logo"></i>
          <h2 class="logo-text">SmartBite AI</h2>
        </div>
        <button id="close-chatbot" aria-label="Close chatbot">
          <i class="fa-solid fa-angle-down"></i>
        </button>
      </div>
      <div class="chat-body">
        <div class="message bot-message">
          <i class="fa-solid fa-robot bot-avatar"></i>
          <div class="message-text">
            Salut, je suis l'assistant SmartBite. Je peux vous aider pour le menu et les recommandations.
          </div>
        </div>
      </div>
      <div class="chat-footer">
        <form action="#" class="chat-form">
          <textarea class="message-input" required placeholder="Posez votre question..."></textarea>
          <div class="chat-controls">
            <button type="button" id="emoji-picker" aria-label="Emoji picker">
              <i class="fa-regular fa-face-smile"></i>
            </button>
            <button type="submit" class="send-btn" id="send-message" aria-label="Send message">
              <i class="fa-solid fa-paper-plane"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js"></script>
  <script src="js/chatbot.js"></script>
<script src="js/auth_navbar.js"></script>


   <!--last child-->
   <div class="bg-body-tertiary p-3">
   <section class="social">
        <div class="container text-center">
            <ul>
                <li>
                    <a href="#"><img src="https://img.icons8.com/fluent/50/000000/facebook-new.png"/></a>
                </li>
                <li>
                    <a href="#"><img src="https://img.icons8.com/fluent/48/000000/instagram-new.png"/></a>
                </li>
                <li>
                    <a href="#"><img src="https://img.icons8.com/fluent/48/000000/twitter.png"/></a>
                </li>
            </ul>
        </div>
        <!-- FOOTER -->
       <footer class="text-center py-4 text-muted border-top small">
        © 2026 SmartBite Restaurants. All rights reserved.
</footer>
    </section>
    
    
   </div>

</div>

</body>
</html>
