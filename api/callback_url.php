<?php
header("Content-Type: application/json");

// Database connection
require(__DIR__ . '/../configs.php');

// Log M-Pesa Response
$mpesaResponse = file_get_contents("php://input");
$logFile = "mpesa_callback.json";
file_put_contents($logFile, $mpesaResponse . PHP_EOL, FILE_APPEND);

// Check request method (POST vs GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log POST request for debugging
    file_put_contents("mpesa_callback_error_log.txt", "POST request received. Checking if it should be GET.\n", FILE_APPEND);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Proceed with GET request processing
    $mpesaResponse = $_GET; // Access data via GET params
    
    $response = $mpesaResponse;  // Using $_GET data directly here
    
    // Helper function to safely get values from GET params
    function getMpesaValue($items, $key) {
        foreach ($items as $item) {
            if ($item['Name'] === $key) {
                return $item['Value'] ?? null;
            }
        }
        return null;
    }

    // Check if response is valid
    if (!$response || !isset($response['Body']['stkCallback']['ResultCode'])) {
        echo json_encode(['message' => 'Invalid response received']);
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

        if (!$amountPaid || !$receiptNumber || !$phoneNumber || !$titleDeed) {
            echo json_encode(['message' => 'Missing required payment details']);
            exit();
        }

        // Ensure title deed is provided
        if (!$titleDeed) {
            echo json_encode(['message' => 'Title deed number missing']);
            exit();
        }

        // Get userID from users ownership
        $stmt = $conn->prepare("SELECT owner_id FROM ownership WHERE titledeed_no = :titledeed AND status_id = 1");
        $stmt->bindParam(':titledeed', $titleDeed);
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
        $stmt->bindParam(':titledeed', $titleDeed, PDO::PARAM_STR);
        $stmt->bindParam(':amount', $amountPaid, PDO::PARAM_STR);

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
} else {
    // If it's neither POST nor GET, handle accordingly
    echo json_encode(['message' => 'Invalid request method']);
    exit();
}

?>
