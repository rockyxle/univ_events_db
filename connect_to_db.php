<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "KNBCProject";


$connection = new mysqli($servername, $username,  $password, $dbname);


if ($connection->connect_error) {
   die("Database connection failed: " . $connection->connect_error);
}
?>
