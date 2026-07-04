<?php
include 'db.php';
header('Content-Type: application/json');

$res = $conn->query("SELECT id, title FROM quizzes ORDER BY id DESC");
$quizzes = [];
while ($r = $res->fetch_assoc()) {
  $quizzes[] = $r;
}
echo json_encode($quizzes);
?>
