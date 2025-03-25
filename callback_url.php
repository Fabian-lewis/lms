<?php
header("Content-Type: application/json");
// session_start();
// if(!isset($_SESSION['user_id'])){
//     header('location:stk_push.php');
// }

// Database connection
require_once 'configs.php';

// // Get title deed from the URL parameter
// $titleDeed = $_GET['titledeed'] ?? null;

// Log M-Pesa Response
$mpesaResponse = file_get_contents("php://input");
$logFile = "mpesa_callback.json";
file_put_contents($logFile, $mpesaResponse . PHP_EOL, FILE_APPEND);

$response = json_decode($mpesaResponse, true);

// Helper function to safely get values
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
    $titleDeed =    getMpesaValue($items, "AccountReference");

    if (!$amountPaid || !$receiptNumber || !$phoneNumber || !$titleDeed) {
        echo json_encode(['message' => 'Missing required payment details']);
        exit();
    }

    // Ensure title deed is provided
    if (!$titleDeed) {
        echo json_encode(['message' => 'Title deed number missing']);
        exit();
    }

    // // Get parcel id from parcels table
    // $stmt = $conn->prepare("SELECT id FROM parcel WHERE titledeed_no = :titledeed");
    // $stmt->bindParam(':titledeed', $titleDeed);
    // $stmt->execute();
    // $parcel_id = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get userID from users ownership
    $stmt = $conn->prepare("SELECT owner_id FROM ownership WHERE titledeed_no = :titledeed AND status_id = 1");
    $stmt->bindParam(':titledeed', $titleDeed);
    $stmt->execute();
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$owner) {
             echo json_encode(['message' => 'Owner not found for this title deed']);
            exit();
    }


    // // Get parcel id from parcels table
    // $stmt = $conn->prepare("SELECT user_id FROM parcels WHERE titledeed_no = :titledeed");
    // $stmt->bindParam(':titledeed', $titleDeed);
    // $stmt->execute();
    // $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    // if (!$owner) {
    //     echo json_encode(['message' => 'Owner not found for this title deed']);
    //     exit();
    // }

    $userId = $owner['user_id'];

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
?>
