<?php
$host = '127.0.0.1:3308';
$user = 'root';
$password = 'Py@db7!';
$database = 'HospitalManagementSystem';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>