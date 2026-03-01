<?php
//totalement pas safe de mettre les infos la faudra faut qqchose
$servername = "localhost";
$username = "root";
$db_pass = "";
$dbname = "users";


$conn = new mysqli($servername, $username, $db_pass, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
