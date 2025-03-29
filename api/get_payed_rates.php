<?php
// Start output buffering to prevent unintended output
// ob_start();
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

header("Content-Type: application/json");

// Start session
session_start();
require(__DIR__ . '/../configs.php');

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // ob_end_clean(); // Clean any previous output
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get the input data
$data = json_decode(file_get_contents("php://input"), true);
$titledeedno = $data['titledeedno'] ?? null;

// Validate input
if (!$titledeedno) {
    // ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Title Deed Number missing']);
    exit();
}

// Validate user authentication
if (!isset($_SESSION['user_id'])) {
    // ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

try {
    // Prepare the SQL query
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total_paid 
        FROM rate_payment 
        WHERE user_id = :user_id AND titledeed_no = :titledeed_no
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':titledeed_no', $titledeedno, PDO::PARAM_STR);
    $stmt->execute();
    
    $total_paid = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure clean JSON response
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'total_paid' => $total_paid['total_paid'] ?? 0
    ]);
    exit();
} catch (PDOException $e) {
    // ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>
