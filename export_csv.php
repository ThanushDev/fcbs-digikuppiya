<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: index.php");
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

// CSV header
fputcsv($output, ['ID','First Name','Last Name','Email','Mobile','Department','Profile Pic','Last Login','Last Seen']);

$q = $conn->query("SELECT id, firstName, lastName, email, mobile, department, profilePic, last_login, last_seen FROM users ORDER BY id DESC");
while ($row = $q->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['firstName'],
        $row['lastName'],
        $row['email'],
        $row['mobile'],
        $row['department'],
        $row['profilePic'],
        $row['last_login'],
        $row['last_seen']
    ]);
}
fclose($output);
exit;
