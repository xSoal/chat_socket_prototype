<?php

$link =  mysqli_connect("localhost", "root", "", "test_socket");

if (!$link) {
	printf("Невозможно подключиться к базе данных. Код ошибки: %s\n", mysqli_connect_error());
}
else {
	mysqli_set_charset($link, "utf8");
	
}