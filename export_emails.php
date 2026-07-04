<?php
session_start();
include 'connect.php';

// Only admin can access
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: index.php");
    exit;
}

// Fetch all emails
$result = $conn->query("SELECT email FROM users ORDER BY id ASC");
$emails = [];
while($row = $result->fetch_assoc()) {
    $emails[] = $row['email'];
}

// Set headers for download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="all_emails.txt"');

// Output emails
echo implode("\n", $emails);
exit;
