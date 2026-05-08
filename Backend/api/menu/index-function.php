<?php
// including connect file
require_once __DIR__ . '/../../config/connection.php';




//getting plats of menu
   function getMenu(){
      global $conn;

        // condition to check isset or not
        if(!isset($_GET['cat'])){

      $select_query="Select * from menu order by rand() limit 0,6";
      $result_query=mysqli_query($conn,$select_query);
      while($row=mysqli_fetch_assoc($result_query)){
        $plat_id=$row['IdMenu'];
        $plat_name=$row['ItemName'];
        $plat_price=$row['ItemPrice'];
        $plat_img=$row['ImageURL'];
        $cat_id=$row['IdCategory'];

echo ' <div class="col-md-4 mb-2">
             <div class="card">
               <div class="card-body">
                <img src="' . $plat_img . '" class="card-img-top" alt="' . $plat_name . '">
                <h5 class="card-title">' . $plat_name . '</h5>
                <p class="card-text">$ ' . $plat_price . '</p>
                <form method="post">
                    <input type="hidden" name="item_id" value="' . $plat_id . '">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" name="add_to_cart" class="btn btn-green px-4">Add to cart</button>
                </form>
               </div>
             </div>
         </div>';
      }
        
   }
   }

   //getting plats by categories
      function getMenuByCat(){
       global $conn;

        // condition to check isset or not
        if(isset($_GET['cat'])){
       $cat=$_GET['cat'];
      $select_query="Select * from menu where IdCategory=$cat";
      $result_query=mysqli_query($conn,$select_query);
      while($row=mysqli_fetch_assoc($result_query)){
        $plat_id=$row['IdMenu'];
        $plat_name=$row['ItemName'];
        $plat_price=$row['ItemPrice'];
        $plat_img=$row['ImageURL'];
        $cat_id=$row['IdCategory'];

echo ' <div class="col-md-4 mb-2">
             <div class="card">
               <div class="card-body">
                <img src="' . $plat_img . '" class="card-img-top" alt="' . $plat_name . '">
                <h5 class="card-title">' . $plat_name . '</h5>
                <p class="card-text">$ ' . $plat_price . '</p>
                <form method="post">
                    <input type="hidden" name="item_id" value="' . $plat_id . '">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" name="add_to_cart" class="btn btn-green px-4">Add to cart</button>
                </form>
               </div>
             </div>
         </div>';
      }
        
   }
   }



   //getting the categories
       function getCat(){
        global $conn;
              $select_cat="Select * from category";
             $result_cat = mysqli_query($conn, $select_cat);
            while($row=mysqli_fetch_assoc($result_cat)){
             $cat_id=$row['IdCategory'];
             $cat_name=$row['CategoryName'];
             echo "<li class=\"nav-item\"><a href='index.php?cat=$cat_id#menu-section' class=\"cat-sidebar\">" . $cat_name . "</a></li>"; 
    }
   }


   //searching menu
   function search_menu(){
    global $conn;
      
  
       if (isset($_GET['search_btn'])){
      $search_data=$_GET['search_data'];
      $search_query="select * from menu where ItemDescription like '%$search_data%'";
      $result_query=mysqli_query($conn,$search_query);
      while($row=mysqli_fetch_assoc($result_query)){
        $plat_id=$row['IdMenu'];
        $plat_name=$row['ItemName'];
        $plat_price=$row['ItemPrice'];
        $plat_img=$row['ImageURL'];
        $cat_id=$row['IdCategory'];

echo ' <div class="col-md-4 mb-2">
             <div class="card">
               <div class="card-body">
                <img src="' . $plat_img . '" class="card-img-top" alt="' . $plat_name . '">
                <h5 class="card-title">' . $plat_name . '</h5>
                <p class="card-text">$ ' . $plat_price . '</p>
                <form method="post">
                    <input type="hidden" name="item_id" value="' . $plat_id . '">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" name="add_to_cart" class="btn btn-green px-4">Add to cart</button>
                </form>
               </div>
             </div>
         </div>';
      }
        
   }
   
   }


   

   















      ?>



