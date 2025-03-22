<?php 
  $host = 'your_db_host';
  $username = 'your_db_user';
  $password = 'your_db_password';
  $database = 'your_db_name';

  $conn = new mysqli($host, $user, $pass, $db, $port);
  if($conn->connect_error){
    die("Database Connection Failed: " . $conn->connect_error);
  }
?>
