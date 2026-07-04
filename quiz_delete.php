<?php
// quiz_delete.php
require_once "db.php";
if (!isset($_SESSION['admin'])) { header("Location: qadmin_login.php"); exit; }

if (isset($_GET['id'])) {
    $quiz_id = intval($_GET['id']);

    // Delete all questions & options of this quiz first (to maintain FK integrity)
    $conn->query("DELETE FROM options WHERE question_id IN (SELECT id FROM questions WHERE quiz_id=$quiz_id)");
    $conn->query("DELETE FROM questions WHERE quiz_id=$quiz_id");

    // Delete the quiz itself
    $conn->query("DELETE FROM quizzes WHERE id=$quiz_id");

    header("Location: qadmin.php?msg=Quiz+deleted+successfully");
    exit;
}
?>
