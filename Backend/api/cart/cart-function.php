<?php

//ADD TO CART FONCTION
function addToCart($itemId,$quantity=1){

    //initialize cart session if it doesn't exist

     if(!isset($_SESSION['cart'])){
        $_SESSION['cart']=[];
     }

     //fetch items from db
     global $conn;
     $itemId=$itemId;
     $quantity=$quantity;
     $query="Select * from menu where IdMenu= $itemId";
     $result=mysqli_query($conn,$query);
     if($row=mysqli_fetch_assoc($result)){

            //check if item is in cart already
         if(isset($_SESSION['cart'][$itemId])){
           $itemName=$row['ItemName'];
           echo'<script>
            window.onload=function(){
           alert("'.$itemName.'is already in your cart!");}   
            </script>';
         }
         else{
            //add the item to the cart
            $_SESSION['cart'][$itemId]=
            [
                'item_id'=>$row['IdMenu'],
                'item_name'=>$row['ItemName'],
                'item_price'=>$row['ItemPrice'],
                'item_image'=>$row['ImageURL'],
                'quantity'=>$quantity
            ];
            // alert msg after adding the item ti cart
            $itemName=$row['ItemName'];
            echo'<script>
            window.onload=function(){
            alert("'.$itemName.'added to your cart!");  }
            </script>';

         }
        
     }

}
//////////////////////////////////////////////////////////////////////////

// DELETE ITEM FROM CART
function removeFromCart($itemId){
     if(isset($_SESSION['cart'][$itemId])){
        unset($_SESSION['cart'][$itemId]);
     }
}

/////////////////////////////////////////////////////////////////////////

// UPDATE quantity item
function updateCartQuantity($itemId,$quantity){
$itemId=$itemId;
$quantity=$quantity;
      if(isset($_SESSION['cart'][$itemId])){
          
          if($quantity < 1){
             $quantity = 1;
          }
          $_SESSION['cart'][$itemId]['quantity']=$quantity;
          return true;
      }
 return false;
}

/////////////////////////////////////////////////////////////////////////

//  GET THE ITEM IN THE CART
function getCartItems(){
    if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])){
        return $_SESSION['cart'];
        }
        return[];
}

///////////////////////////////////////////////////////////////////////////

// GET CART ITEM COUNT
function getCartItemCount(){
    $count=0;
    if(isset($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $item){
            $count += $item['quantity'];
        }
    }
    return $count;
}

//////////////////////////////////////////////////////////////////////////////

// GET CART TOTAL (PRICE TOTAL)
function getCartTotal(){
    $total=0;
    if(isset($_SESSION['cart'])){
        foreach($_SESSION['cart']as $item){
            $total += $item['item_price'] * $item['quantity'];
    }      
  }
  return $total;
}


////////////////////////////////////////////////////////////////////////////////

// DISPLAY CART ITEMS
function displayCartItems(){
    $cart=getCartItems();

    if(empty($cart)){
        echo'<tr> 
             <td colspan="5" class="text-center py-5">   <h4> Your cart is empty </h4> </td>
             </tr>';
             return;
    }
    foreach($cart as $itemId=>$item){
         $item_price=$item['item_price'];
         echo'<tr> 
                 <td> '.$item['item_name'].' </td>
                 <td> <img src="'.$item['item_image'].'" alt="'.$item['item_name'].'" class="cart_img"> </td>
                 <td> <form method="post" action="cart.php" class="d-flex align-items-center justify-content-center">
                        <input type="hidden" name="item_id" value="'.$itemId.'">
                        <input type="number" name="quantity" value="'.$item['quantity'].'"  min="1" class="form-control" style="width:70px;">
                       <button type="submit" name="update_qty" class="btn btn-green"> Update </button>     </form> </td>
                <td> $'.number_format($item['item_price'],2).' </td>
                <td> <form method="post">
                       <input type="hidden" name="item_id" value="'.$itemId.'">
                       <button type="submit" name="remove_item" class="btn btn-remove">Remove</button>  
                       </form> </td>

             </tr>';  
    }
                       }

//////////////////////////////////////////////////////////////////////////////
 
// DISPLAY THE TOTAL PRICE
function displayCartSummary(){
    $total=getCartTotal();
    echo'<h4> Subtotal:<strong> $'.number_format($total,2).'</strong> </h4>';
    }

//////////////////////////////////////////////////////////////////////////////

// CLEAR CART
function clearCart(){
    if (isset($_SESSION['cart'])){
        unset($_SESSION['cart']);
    }

        }

?>

