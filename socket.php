<?php

date_default_timezone_set("Europe/Kiev");

require_once './vendor/autoload.php';
require_once 'functions.php';
// require_once 'bd.php';


use Workerman\Lib\Timer;
use Workerman\Worker;

$connections = []; 

$worker = new Worker("websocket://0.0.0.0:8081");

$worker->onConnect = function($connection)
{
    $connection->onWebSocketConnect = function($connection) {
        global $connections;
        // $connection->id  уникальный id самого сокета
        $connections[$connection->id] = $connection;
        $connection->send(json_encode("ok"));

        var_dump(count($connections));
    };

};

$worker->onClose = function($connection) use(&$connections)
{
    if (!isset($connections[$connection->id])) {
        return;
    }
    unset($connections[$connection->id]);
    
};


$worker->onMessage = function($connection, $message) use (&$connections){
    global $connections;
    $link =  mysqli_connect("localhost", "root", "", "test_socket");

    var_dump($message);
    $messageData = json_decode($message, true);
    
    $action = $messageData["action"];

    if($action === 'authorize'){
        $user_id = $messageData["user_id"];
        $token = $messageData["token"];

        $q = mysqli_query($link, "SELECT * FROM `users` WHERE id = '$user_id' ");

        $user = mysqli_fetch_assoc($q);
        if(!$user) return;

        $user_real_token = $user["token"];


        if($token === $user_real_token){
            $connection->isLogin = true;
            $connection->user_id = $user_id;
            $connection->send(json_encode("isAuthorized"));

            $new_token = generateRandomString(25);
            mysqli_query($link, "UPDATE `users` SET token = '$new_token' WHERE id = '$user_id'");
        }

    }

    if(!$connection->user_id){
        $connection->send(json_encode("errorAuthorize"));
        unset($connections[$connection->id]);
    }


    if($action === 'send_message'){
        $dataMessage = json_decode($message, true);
        $to_user_id  = $dataMessage["to"];
        $to_user_connection = null;

        foreach($connections as $c){
          if($c->user_id === $to_user_id){
            $to_user_connection = $c;
          }
        }

        if(!$to_user_connection) return;


        $date_time = date("Y-m-d H:i:s", time());

        $to_user_connection->send(json_encode([
            "action" => "new_message",
            "from" => $dataMessage["from"],
            "message" => $dataMessage["message"],
            "date_time" => $date_time
        ], JSON_UNESCAPED_UNICODE));

        return;
    }   

    if($action === 'want_send_file'){
        $connection->send(json_encode("server_wait_for_upload"));
        $connection->server_wait_for_upload = $messageData["file_name"];
    }

    if( isset($connection->server_wait_for_upload) && $connection->server_wait_for_upload ){
        var_dump("WAITING FOR FILE TRANSFER");
        
        $file = $message;
        
        // $finfo = new finfo(FILEINFO_MIME_TYPE);
        // $mimeType = $finfo->buffer($file);

        
        // if($mimeType === ""){
        // }

        if(probably_binary($file) && file_put_contents(__DIR__. "/files/" . $connection->server_wait_for_upload, $file)){
            var_dump("FILE IS SAVE");
            unset($connection->server_wait_for_upload);
        }

        

    }

    mysqli_close($link);
};


function probably_binary($stringa) {
    $is_binary=false;
    $stringa=str_ireplace("\t","",$stringa);
    $stringa=str_ireplace("\n","",$stringa);
    $stringa=str_ireplace("\r","",$stringa);
    if(is_string($stringa) && ctype_print($stringa) === false){
        $is_binary=true;
    }
    return $is_binary;
}


Worker::runAll();