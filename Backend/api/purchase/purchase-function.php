<?php
//CREATING AN ORDER BY DEFAULT SHOULD BE "PENDING"

function createPendingOrderFromCart(mysqli $conn, array $cart, int $userId, string $specialInstructions)
{

   //initilize total at o
    $orderTotal = 0.0;
    //create an empty array to store the item of cart in it
    $items = [];

    //loop throygh the cart
    foreach ($cart as $cartItem) {
        $idMenu = $cartItem['item_id'];
        $qty = $cartItem['quantity'];
        $priceAtTime = $cartItem['item_price'];
        
        //storing item from cart into the array created before
        $items[] = [
            'idMenu' => $idMenu,
            'qty' => $qty,
            'priceAtTime' => $priceAtTime,
        ];
        //calculate total 
        $orderTotal += $priceAtTime * $qty;
    }


//FINDINF LAST ID ORDER IN DATABASE
    // initialize the next order id to 1 in case there is no privious order
    $nextOrderId = 1;
    //Fetch the last id order in table orders
    $res = $conn->query('select max(IdOrder) as max_id from orders');
   
    //in case there is previous order 
    if ($res) {
        $row = $res->fetch_assoc();
        if (isset($row['max_id'])) {
            $nextOrderId = (int)$row['max_id'] + 1;
        }
    }

    //the status of the order will be "pending" when its first created
    $status = 'Pending';

    //prepare statement to insert the order in table orders
    $stmtOrder = $conn->prepare(
        'insert into orders (IdOrder, IdUser, OrderTotalAmount, Status, specialInstructions, created_at)
        values (?, ?, ?, ?, ?, NOW())'
    );

    if (!$stmtOrder) {
        throw new RuntimeException('Prepare orders failed: ' . $conn->error);
    }

    $stmtOrder->bind_param(
        'iidss',
        $nextOrderId,
        $userId,
        $orderTotal,
        $status,
        $specialInstructions
    );

    if (!$stmtOrder->execute()) {
        $stmtOrder->close();
        throw new RuntimeException('Failed to insert into orders: ' .$stmtOrder->error );
    }

    $stmtOrder->close();

   //prepare statement to insert the items inside orderitems
    $stmtItems = $conn->prepare(
        'insert into orderitems (IdOrder, IdMenu, Quantity, PriceAtTime) values (?, ?, ?, ?)'
    );

    if (!$stmtItems) {
        throw new RuntimeException('Prepare orderitems failed: ' . $conn->error);
    }

    foreach ($items as $item) {
        $idMenu = $item['idMenu'];
        $qty = $item['qty'];
        $priceAtTime = $item['priceAtTime'];

        $stmtItems->bind_param(
            'iiid',
             $nextOrderId,
              $idMenu, $qty,
               $priceAtTime);

        if (!$stmtItems->execute()) {
            $stmtItems->close();
            throw new RuntimeException('Failed to insert into order items: ' .$stmtItems->error );
            ;
        }
    }

    $stmtItems->close();


//calling function getOrder to display order details
    $display = getOrderDisplayRows($conn, $nextOrderId);

    return [
        'nextOrderId' => $nextOrderId,
        'displayRows' => $display['displayRows'],
        'totalPaid' => $display['totalPaid'],
    ];
}

////////////////////////////////////////////////////////////////////////////////////

function getOrderDisplayRows(mysqli $conn, int $orderId): array
{
    $stmt = $conn->prepare(
        'SELECT oi.IdMenu, m.ItemName, oi.Quantity, oi.PriceAtTime, m.ImageURL
         FROM orderitems oi
         JOIN menu m ON oi.IdMenu = m.IdMenu
         WHERE oi.IdOrder = ?'
    );

    if (!$stmt) {
        throw new RuntimeException('Prepare fetch failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $orderId);
    $stmt->execute();

    $result = $stmt->get_result();

    $displayRows = [];
    $totalPaid = 0.0;

    while ($row = $result->fetch_assoc()) {
        $qty = (int)$row['Quantity'];
        $price = (float)$row['PriceAtTime'];

        $displayRows[] = [
            'item_name' => $row['ItemName'],
            'quantity' => $qty,
            'item_price' => $price,
        ];

        $totalPaid += $price * $qty;
    }

    $stmt->close();

    return [
        'displayRows' => $displayRows,
        'totalPaid' => (float)$totalPaid,
    ];
}

///////////////////////////////////////////////////////////////////////////////
//cofmirm order
function confirmOrder(mysqli $conn, int $userId, int $orderId)
{
    $stmt = $conn->prepare(
        "update orders set  Status ='Confirmed' where IdOrder = ? and IdUser = ?"
    );

    if (!$stmt) {
        throw new RuntimeException('Prepare update order failed: ' . $conn->error);
    }

    $stmt->bind_param(
        'ii',
         $orderId,
          $userId);

    $ok = $stmt->execute();
    $stmt->close();

  
    if(!$ok){
        return False;
    }else{
        return True;
    }


}

