<?php
session_start();
require_once("functions.php");
require_once("bd.php");

if(!isset($_SESSION["user_id"])){
    require_once("login.php");
    mysqli_close($link);
    exit("");
}


require_once("chat_client.php");

?>


<?php
mysqli_close($link);