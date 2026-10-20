<?php
$conn = new mysqli("localhost", "root", "", "studenthub");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>
