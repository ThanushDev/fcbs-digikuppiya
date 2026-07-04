<?php
// db.php
$DB_HOST = "sql112.infinityfree.com";
$DB_USER = "if0_41943903";
$DB_PASS = "Thanush21122003";
$DB_NAME = "if0_41943903_quiz";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
  http_response_code(500);
  die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// Start session only if none exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
