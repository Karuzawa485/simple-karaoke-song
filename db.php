<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "karaoke_db";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
?>