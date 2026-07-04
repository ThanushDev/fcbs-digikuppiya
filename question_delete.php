<?php
require_once "db.php";
if (!isset($_SESSION['admin'])) { header("Location: qadmin_login.php"); exit; }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM options WHERE question_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: qadmin.php?msg=Question+deleted+successfully");
    exit;
} else {
    header("Location: qadmin.php?msg=Invalid+question+ID");
    exit;
}
?>
