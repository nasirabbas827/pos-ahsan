<?php
// define database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pos_db";

// create database connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// check if connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>