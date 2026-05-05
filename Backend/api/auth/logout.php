<?php
session_start();
session_unset();
session_destroy();
setcookie("t_user", "", time() - 3600, "/");
header('Location: ../../Frontend/signin.html');
exit();
?>