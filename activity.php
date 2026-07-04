<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user']['id'])) {
    http_response_code(204);
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$stmt = $conn->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();

http_response_code(204); // No content
