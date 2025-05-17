<?php
// db_config.php

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';       // để trống nếu bạn chưa đặt password cho root
$db_name = 'hospitaldb';
$db_port = 3307;        
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
  die('Connection failed: ' . $conn->connect_error);
}
