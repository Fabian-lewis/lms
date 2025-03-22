<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: stk_push.php');
    exit();
}

header("Content-Type: application/json");

// Database connection
require_once 'configs.php';  // Ensure this contains the $conn (PDO) connection

// Get title deed from the URL parameter
$titleDeed = isset($_GET['titledeed']) ? $_GET['titledeed'] : null;

// Log M-Pesa Response
$mpesaResponse = file_get_contents("php://input");
$logFile = "mpesa_callback.json";
file_put_contents($logFile, $mpesaResponse . PHP_EOL, FILE_APPEND);

$response = json_decode($mpesaResponse, true);

// Check if response is valid
if (!$response || !isset($response['Body']['stkCallback']['ResultCode'])) {
    echo json_encode(['message' => 'Invalid response received']);
    exit();
}

$ResultCode = $response['Body']['stkCallback']['ResultCode'];

if ($ResultCode == 0) {
    // Extract payment details
    $amountPaid = $response['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
    $receiptNumber = $response['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
    $transactionDate = date('Y-m-d H:i:s');  // Use PHP date instead of MySQL now()
    $phoneNumber = $response['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];

    // Get user ID from session
    $userId = $_SESSION['user_id'];

    // Ensure title deed is provided
    if (!$titleDeed) {
        echo json_encode(['message' => 'Title deed number missing']);
        exit();
    }

    // Insert payment into `rate_payment` table
    $query = "INSERT INTO rate_payment (user_id, titledeed_no, datepayed, amount) 
              VALUES (:id, :titledeed, :datepayed, :amount)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':titledeed', $titleDeed, PDO::PARAM_STR);
    $stmt->bindParam(':datepayed', $transactionDate, PDO::PARAM_STR);
    $stmt->bindParam(':amount', $amountPaid, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Payment received successfully']);
    } else {
        echo json_encode(['message' => 'Failed to save payment']);
    }
} else {
    echo json_encode(['message' => 'Payment failed']);
}
?>
