<?php
header("Content-Type: application/json");

// Database connection
require(__DIR__ . '/../configs.php');

// Log the M-Pesa Response (for debugging)
$mpesaResponse = file_get_contents("php://input");
$logFile = "mpesa_callback.json";
file_put_contents($logFile, $mpesaResponse . PHP_EOL, FILE_APPEND);

// Get the current timestamp for logging
$timestamp = date("Y-m-d H:i:s");
$logEntry = "Callback received at {$timestamp}";

// Decode the incoming JSON response
$response = json_decode($mpesaResponse, true);

// Log the incoming response
file_put_contents('mpesa_log.txt', "{$logEntry} - Incoming Response: " . json_encode($response) . PHP_EOL, FILE_APPEND);

// Helper function to safely extract values
function getMpesaValue($items, $key) {
    foreach ($items as $item) {
        if ($item['Name'] === $key) {
            return $item['Value'] ?? null;
        }
    }
    return null;
}

// Check if the response is valid and contains the expected data
if (!$response || !isset($response['Body']['stkCallback']['ResultCode'])) {
    $errorMessage = "Invalid response received or missing 'ResultCode'";
    file_put_contents('mpesa_log.txt', "{$logEntry} - ERROR: {$errorMessage}" . PHP_EOL, FILE_APPEND);
    echo json_encode(['message' => $errorMessage]);
    exit();
}

$ResultCode = $response['Body']['stkCallback']['ResultCode'];

if ($ResultCode == 0) {
    // Extract payment details
    $items = $response['Body']['stkCallback']['CallbackMetadata']['Item'];
    $amountPaid = getMpesaValue($items, "Amount");
    $receiptNumber = getMpesaValue($items, "MpesaReceiptNumber");
    $phoneNumber = getMpesaValue($items, "PhoneNumber");
    $titleDeed = getMpesaValue($items, "AccountReference");

    // Log payment details
    $paymentLog = "Amount: {$amountPaid}, Receipt: {$receiptNumber}, Phone: {$phoneNumber}, Title Deed: {$titleDeed}";
    file_put_contents('mpesa_log.txt', "{$logEntry} - Payment Details: {$paymentLog}" . PHP_EOL, FILE_APPEND);

    // Check if required data is present
    if (!$amountPaid || !$receiptNumber || !$phoneNumber || !$titleDeed) {
        $errorMessage = "Missing required payment details";
        file_put_contents('mpesa_log.txt', "{$logEntry} - ERROR: {$errorMessage}" . PHP_EOL, FILE_APPEND);
        echo json_encode(['message' => $errorMessage]);
        exit();
    }

    // Get user ID from the ownership table using the title deed number
    $stmt = $conn->prepare("SELECT owner_id FROM ownership WHERE titledeed_no = :titledeed AND status_id = 1");
    $stmt->bindParam(':titledeed', $titleDeed);
    $stmt->execute();
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$owner) {
        $errorMessage = "Owner not found for this title deed";
        file_put_contents('mpesa_log.txt', "{$logEntry} - ERROR: {$errorMessage}" . PHP_EOL, FILE_APPEND);
        echo json_encode(['message' => $errorMessage]);
        exit();
    }

    $userId = $owner['owner_id'];

    // Insert the payment information into the rate_payment table
    $query = "INSERT INTO rate_payment (user_id, titledeed_no, datepayed, amount) 
              VALUES (:id, :titledeed, NOW(), :amount)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':titledeed', $titleDeed, PDO::PARAM_STR);
    $stmt->bindParam(':amount', $amountPaid, PDO::PARAM_INT); // Ensure this is an integer

    try {
        if ($stmt->execute()) {
            $successMessage = "Payment received successfully";
            file_put_contents('mpesa_log.txt', "{$logEntry} - SUCCESS: {$successMessage}" . PHP_EOL, FILE_APPEND);
            echo json_encode(['message' => $successMessage]);
        } else {
            $errorInfo = $stmt->errorInfo();
            $errorMessage = "Failed to save payment. DB Error: " . json_encode($errorInfo);
            file_put_contents('mpesa_log.txt', "{$logEntry} - ERROR: {$errorMessage}" . PHP_EOL, FILE_APPEND);
            echo json_encode(['message' => 'Failed to save payment']);
        }
    } catch (Exception $e) {
        $errorMessage = "Exception occurred: " . $e->getMessage();
        file_put_contents('mpesa_log.txt', "{$logEntry} - ERROR: {$errorMessage}" . PHP_EOL, FILE_APPEND);
        echo json_encode(['message' => 'Failed to save payment']);
    }

} else {
    $errorMessage = "Payment failed. ResultCode: {$ResultCode}";
    file_put_contents('mpesa_log.txt', "{$logEntry} - ERROR: {$errorMessage}" . PHP_EOL, FILE_APPEND);
    echo json_encode(['message' => 'Payment failed']);
}

// Send HTTP response to M-Pesa
http_response_code(200);
exit();
?>
