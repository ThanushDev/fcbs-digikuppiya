<?php
session_start(); // START SESSION to access the $_SESSION['user'] data

header('Content-Type: application/json');

// -----------------------------------------------------
// 0. AUTHENTICATION AND USER ID EXTRACTION
// -----------------------------------------------------
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

// Get the ID of the currently logged-in user
$current_user_id = $_SESSION['user']['id']; 

// -----------------------------------------------------
// 1. DATABASE CONFIGURATION
// !! IMPORTANT: Using your provided credentials !!
// -----------------------------------------------------
$servername = "sql112.infinityfree.com";
$username = "if0_41943903"; 
$password = "Thanush21122003"; 
$dbname = "if0_41943903_finance_tracker"; 

// -----------------------------------------------------
// 2. DATABASE CONNECTION
// -----------------------------------------------------
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// NOTE: You must have already run the ALTER TABLE SQL command
// to add the `user_id` column to your `transactions` table.

// Get the action from the POST request
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action or missing parameters.'];

switch ($action) {
    
    // --- A. ADD NEW TRANSACTION (Saves with user_id) ---
    case 'addTransaction':
        if (isset($_POST['date'], $_POST['type'], $_POST['amount'], $_POST['category'])) {
            $date = $_POST['date'];
            $type = $_POST['type'];
            $amount = $_POST['amount'];
            $category = $_POST['category'];
            $description = $_POST['description'] ?? '';
            
            // Modified SQL: Added user_id column
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, date, type, amount, category, description) VALUES (?, ?, ?, ?, ?, ?)");
            // Modified Bind: 'i' for user_id (integer), followed by 's', 's', 'd', 's', 's'
            $stmt->bind_param("issdss", $current_user_id, $date, $type, $amount, $category, $description);

            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Transaction added successfully.'];
            } else {
                $response = ['success' => false, 'message' => 'Error adding transaction: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Missing required transaction data.'];
        }
        break;

    // --- B. GET MONTHLY SUMMARY AND ALL TRANSACTIONS (Filtered by user_id) ---
    case 'getMonthlyData':
        if (isset($_POST['year'], $_POST['month'])) {
            $year = (int)$_POST['year'];
            $month = (int)$_POST['month']; 
            
            // Filter by date AND user_id
            $datePattern = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '%';

            // Modified SQL: Added WHERE user_id = ?
            $stmt = $conn->prepare("SELECT id, date, type, amount, category, description FROM transactions WHERE user_id = ? AND date LIKE ? ORDER BY date ASC");
            $stmt->bind_param("is", $current_user_id, $datePattern); // 'i' for user_id, 's' for datePattern
            $stmt->execute();
            $result = $stmt->get_result();
            
            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            $stmt->close();

            // Calculate totals (logic remains the same)
            $totalIncome = 0;
            $totalExpenses = 0;
            
            foreach ($transactions as $t) {
                if ($t['type'] === 'income') {
                    $totalIncome += (float)$t['amount'];
                } else if ($t['type'] === 'expense') {
                    $totalExpenses += (float)$t['amount'];
                }
            }
            // Category aggregation is done in the frontend JS for chart data in this model

            $response = [
                'success' => true,
                'transactions' => $transactions, // Send all transactions to JS for aggregation
                'summary' => [
                    'totalIncome' => number_format($totalIncome, 2, '.', ''),
                    'totalExpenses' => number_format($totalExpenses, 2, '.', ''),
                    'netBalance' => number_format($totalIncome - $totalExpenses, 2, '.', ''),
                ]
            ];
            
        } else {
            $response = ['success' => false, 'message' => 'Missing year or month parameters.'];
        }
        break;

    default:
        break;
}

// -----------------------------------------------------
// 4. CLOSE CONNECTION AND OUTPUT
// -----------------------------------------------------
$conn->close();
echo json_encode($response);
?>