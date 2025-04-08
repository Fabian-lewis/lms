<?php
header("Content-Type: application/json");

// Database connection
require(__DIR__ . '/../configs.php');

// Log M-Pesa Response
$mpesaResponse = file_get_contents("php://input");  // Keep this to log raw data if needed
$logFile = "mpesa_callback.json";
file_put_contents($logFile, $mpesaResponse . PHP_EOL, FILE_APPEND);

// Extract GET parameters from M-Pesa callback
$ResultCode = $_GET['ResultCode'] ?? null;
$Amount = $_GET['Amount'] ?? null;
$MpesaReceiptNumber = $_GET['MpesaReceiptNumber'] ?? null;
$PhoneNumber = $_GET['PhoneNumber'] ?? null;
$AccountReference = $_GET['AccountReference'] ?? null;

if (!$ResultCode || !$Amount || !$MpesaReceiptNumber || !$PhoneNumber || !$AccountReference) {
    echo json_encode(['message' => 'Missing required payment details']);
    exit();
}

if ($ResultCode == 0) {
    // Proceed with saving to the database if payment was successful
    // Get userID from users ownership
    $stmt = $conn->prepare("SELECT owner_id FROM ownership WHERE titledeed_no = :titledeed AND status_id = 1");
    $stmt->bindParam(':titledeed', $AccountReference);
    $stmt->execute();
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$owner) {
        echo json_encode(['message' => 'Owner not found for this title deed']);
        exit();
    }

    $userId = $owner['owner_id'];

    // Insert payment into rate_payment table
    $query = "INSERT INTO rate_payment (user_id, titledeed_no, datepayed, amount) 
              VALUES (:id, :titledeed, NOW(), :amount)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':titledeed', $AccountReference, PDO::PARAM_STR);
    $stmt->bindParam(':amount', $Amount, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Payment received successfully']);
    } else {
        echo json_encode(['message' => 'Failed to save payment']);
    }
} else {
    echo json_encode(['message' => 'Payment failed']);
}

// Ensure response is sent to M-Pesa
http_response_code(200);
exit();
?>
