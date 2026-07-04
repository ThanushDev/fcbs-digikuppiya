<?php
require_once "db.php";

$quiz_id = intval($_GET['quiz_id']);

$questions = [];
$qResult = $conn->query("SELECT * FROM questions WHERE quiz_id = $quiz_id");
while ($q = $qResult->fetch_assoc()) {
    $opts = [];
    $oResult = $conn->query("SELECT id, option_text, is_correct+0 AS is_correct FROM options WHERE question_id = {$q['id']}");
    while ($o = $oResult->fetch_assoc()) {
        $o['id'] = (int)$o['id'];
        $o['is_correct'] = (int)$o['is_correct'];
        $opts[] = $o;
    }
    $q['allow_multiple'] = (int)$q['allow_multiple'];
    $q['options'] = $opts;
    $questions[] = $q;
}

echo json_encode(['questions' => $questions]);
?>
