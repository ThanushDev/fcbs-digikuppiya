<?php
// --- 1. Security & Initialization ---
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['response' => 'Please log in to use the study assistant.']);
    exit;
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['response' => 'Invalid request method.']);
    exit;
}

// Retrieve the raw POST data
$data = json_decode(file_get_contents("php://input"), true);
$userQuery = trim($data['query'] ?? '');

// NEW: Get the target language from the frontend request
$targetLang = $data['target_lang'] ?? 'en'; 

if (empty($userQuery)) {
    http_response_code(400); // Bad Request
    echo json_encode(['response' => 'No query provided.']);
    exit;
}

// --- 2. Gemini API Configuration ---
// !!! WARNING: REPLACE WITH YOUR CORRECT, AUTHORIZED GEMINI API KEY !!!
$apiKey = "AIzaSyCNWTIB1iUS2l1HLF3EE2knIbAzljHWLeQ"; 
$geminiEndpoint = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$userName = htmlspecialchars($_SESSION['user']['firstName'] ?? 'Student');

// --- 3. Construct the API Request Payload (TRANSLATION INTEGRATION) ---

// Define the base system instruction
$systemInstruction = "You are an expert, friendly, and helpful study assistant for university students. 
Your goal is to provide clear, concise, and academically relevant answers related to Finance, Accounting, Management, and IT subjects, specifically covering Macro Economics, Cost and Management Accounting, Management Information Systems, Business Skills, and Business Law.
Address the user by their name: $userName. Keep answers focused on educational content.";

// NEW: Append a translation instruction if the target language is not English
if ($targetLang === 'si') {
    $systemInstruction .= " IMPORTANT: After generating your academic answer, translate the ENTIRE answer into **Sinhala** and provide ONLY the translated text. Do not include the English text.";
} elseif ($targetLang === 'ta') {
    $systemInstruction .= " IMPORTANT: After generating your academic answer, translate the ENTIRE answer into **Tamil** and provide ONLY the translated text. Do not include the English text.";
}

// Combine the system instruction with the user's query
$fullQuery = $systemInstruction . "\n\n--- USER QUERY ---\n" . $userQuery;

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $fullQuery] // This is now the full, combined prompt
            ]
        ]
    ],
    // The problematic 'systemInstruction' field has been completely removed.
    "generationConfig" => [ 
        "temperature" => 0.6 
    ]
];

$jsonPayload = json_encode($payload);

// --- 4. Execute the API Call using cURL ---
$ch = curl_init($geminiEndpoint);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 

$apiResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors (network or SSL issues)
if (curl_errno($ch)) {
    $curlError = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['response' => "API connection error (cURL failure). Code: " . $httpCode . " Message: " . $curlError]);
    exit;
}
curl_close($ch);

// --- 5. Process and Respond (Enhanced Debugging) ---
$data = json_decode($apiResponse, true);
$geminiText = '';

// Check if the API returned an HTTP error code (e.g., 400, 403 - usually API Key issue)
if ($httpCode >= 400) {
    $apiMessage = $data['error']['message'] ?? 'Unknown API Error. Check server logs.';
    $geminiText = "Request failed. HTTP Status: " . $httpCode . ". Message: " . $apiMessage;
} 
// Check for successful data
elseif (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $geminiText = $data['candidates'][0]['content']['parts'][0]['text'];
} 
// Handle Content Blocking or other structured API rejection
elseif (isset($data['promptFeedback'])) {
    $safetyReason = $data['promptFeedback']['blockReason'] ?? 'Safety filters blocked the query.';
    $geminiText = "I encountered an issue processing that query or the content was blocked. Reason: " . $safetyReason;
}
// Handle generic parsing failure (unexpected structure)
else {
    $geminiText = "I received an unreadable response from the API. Debug Code: " . $httpCode . " | Raw Data Sample: " . substr($apiResponse, 0, 150) . "...";
}

header('Content-Type: application/json');
echo json_encode(['response' => $geminiText]);
exit;
?>