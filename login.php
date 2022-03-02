<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

<?php
    if(isset($_POST["login"])){
        $name = $_POST["name"];
        $password = $_POST["password"];

        $q = mysqli_query($link, "SELECT * FROM `users` WHERE name = '$name' AND password = '$password'");
        if($data = mysqli_fetch_assoc($q)){
            $_SESSION["user_id"] = $data["id"];
            $_SESSION["name"] = $data["name"];
            echo "<script> window.location.href = '/'; </script>";
        } 

    }
?>


<style>
    * {
        padding: 0;
        margin: 0;
    }
    body {
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cont {
        width: 500px;
        height: 500px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e7e7e7;
    }

    .cont form > div {
        margin-top: 25px;
    }

</style>

<div class="cont">
    <form action="" method="post">
        <div>
            <input type="text" name="name" placeholder="name">
        </div>
        <div>
            <input type="text" name="password" placeholder="password">
        </div>
        <div>
            <button name="login" type="submit">login</button>
        </div>
    </form>
</div>
    
</body>
</html>