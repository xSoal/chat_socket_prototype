<?php

date_default_timezone_set("Europe/Kiev");

require_once './vendor/autoload.php';
require_once 'functions.php';
require_once 'bd.php';


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
    global $connections, $link;
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
        } else {
            $connection->send(json_encode("errorAuthorize"));
            unset($connections[$connection->id]);
        }

        return;
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

    }


    
    
};



Worker::runAll();