<?php
// track_video.php

session_start();
// Assuming 'connect.php' is in the same directory
include 'connect.php'; 

header('Content-Type: application/json');

// 1. Security Check: Ensure a user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// 2. Input Handling
$user_id = $_SESSION['user']['id'];
// Get JSON data sent from JavaScript
$input = json_decode(file_get_contents('php://input'), true);

$video_id = $input['videoId'] ?? '';
$video_name = $input['videoName'] ?? '';
$video_subject = $input['videoSubject'] ?? '';
$currentTimeSec = (int)($input['currentTime'] ?? 0);

if (empty($video_id) || empty($video_name) || empty($video_subject)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required video data']);
    exit;
}

// 3. Database Logging
// We log a new row every time the tracking script sends an update (every 10 seconds)
$sql = "INSERT INTO video_watches (user_id, video_id, video_name, video_subject, watched_at, current_time_sec) 
        VALUES (?, ?, ?, ?, NOW(), ?)";

$stmt = $conn->prepare($sql);
// 'isssi' stands for integer, string, string, string, integer (the types of the parameters)
$stmt->bind_param("isssi", $user_id, $video_id, $video_name, $video_subject, $currentTimeSec);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Watch event logged']);
} else {
    // Log detailed error for debugging, but send a generic one to the client
    error_log("Video Watch DB Error: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to log watch event']);
}

$stmt->close();
$conn->close();
?>