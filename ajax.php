<?php

session_start();

if(!$_SESSION["user_id"]) exit("");


require_once ('./bd.php');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if(isset($data["get_messages"])){
    // $user_id = $data["get_messages"]["author"];
    // $au = $data["get_messages"]["from_user"];

}