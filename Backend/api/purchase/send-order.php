<?php
require_once __DIR__ . '/../../config/connection.php';

require_once __DIR__ . '/../../config/secrets.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

function sendOrderEmailForUser(mysqli $conn, $userId, $orderId) {
   //query to fetch username , email of the client
    $stmtUser = $conn->prepare(
        'select UserName, UserEmail  from users where IdUser = ?'
    );

    $stmtUser->bind_param('i', $userId);

    //execute the query
    $stmtUser->execute();
   
    // in case the statement didn't execute
    if (!$stmtUser->execute()) {
        throw new RuntimeException(' user fetch failed: ' . $conn->error);
    }
   
    //get result of the query
    $resultUser = $stmtUser->get_result();
   

    // loop through the result to get the email and username of the client ( le resultat= 1 ligne)
    $user=null;
    if ($resultUser){
        while ($row =$resultUser->fetch_assoc()){
            $user=$row;
        }
    }

    $stmtUser->close();

  
    $toEmail = $user['UserEmail'];
   

    $toName='Customer';
      if(isset($user) && isset($user['UserName']) && !empty($user['UserName'])){
       $toName=$user['UserName'];
      }



   $stmtOrder = $conn->prepare(
    'select oi.Quantity, oi.PriceAtTime, m.ItemName 
    from orderitems oi 
    join  orders o on o.IdOrder=oi.IdOrder
    join menu m on m.IdMenu=oi.IdMenu
    where oi.IdOrder=? and o.IdUser=?'
   );


    $stmtOrder->bind_param('ii', $orderId, $userId);
    $stmtOrder->execute();

if (!$stmtOrder->execute()) {
    throw new RuntimeException('Order fetch failed: ' . $conn->error);
}

    $resultOrder = $stmtOrder->get_result();

    $items = [];
    $total = 0.0;

    while ($row = $resultOrder->fetch_assoc()) {
        $qty = $row['Quantity'];
        $price = (float)$row['PriceAtTime'];
        $line = $qty * $price;

        $items[] = [
            'name' => $row['ItemName'], 
            'qty' => $qty,
             'price' => $price, 'line' => $line
             ];
        $total += $line;
    }

    $stmtOrder->close();


    $lines = '';
    foreach ($items as $it) {
        $lines .= '<tr>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #eee;">' . htmlspecialchars($it['name']) . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:center;">' . $it['qty'] . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;">$' . number_format((float)$it['price'], 2) . '</td>'
            . '</tr>';
    }

    $html = '<p>Hi ' . htmlspecialchars($toName) . ',</p>'
        . '<p>Your order <strong>#' . $orderId . '</strong> has been confirmed.</p>'
        . '<table style="border-collapse:collapse;width:100%;margin:10px 0;">'
        . '<thead><tr><th style="text-align:left;padding:8px 10px;border-bottom:2px solid #ddd;">Product</th>'
        . '<th style="text-align:center;padding:8px 10px;border-bottom:2px solid #ddd;">Qty</th>'
        . '<th style="text-align:right;padding:8px 10px;border-bottom:2px solid #ddd;">Price</th>'
        . '</tr></thead>'
        . '<tbody>' . $lines . '</tbody>'
        . '</table>'
        . '<p><strong>Total: $' . number_format((float)$total, 2) . '</strong></p>';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USER;
    $mail->Password = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $fromEmail = defined('MAIL_USER') ? MAIL_USER : 'smartbite169@gmail.com';
    $fromName = 'SmartBite';

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail, $toName);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'SmartBite - Order Confirmation #' . $orderId;
    $mail->Body = $html;
    $mail->AltBody = "Order #{$orderId} confirmed. Total: $" . number_format((float)$total, 2);

    $mail->send();
    return true;
}

?>
