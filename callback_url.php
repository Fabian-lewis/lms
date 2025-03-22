<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location:stk_push.php');
}
header("Content-Type: application/json");

// Database connection
require_once 'configs.php';

// Get title deed from the url parameter
$titleDeed = isset($_GET['titledeed']) ? $_GET['titledeed'] : null;


$mpesaResponse = file_get_contents("php://input");
$logFile = "mpesa_callback.json";
file_put_contents($logFile, $mpesaResponse, FILE_APPEND);

$response = json_decode($mpesaResponse, true);
$ResultCode = $response['Body']['stkCallback']['ResultCode'];

if ($ResultCode == 0) {
    $amountPaid = $response['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
    $receiptNumber = $response['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
    $transactionDate = $response['Body']['stkCallback']['CallbackMetadata']['Item'][3]['Value'];
    $phoneNumber = $response['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];

    // Store transaction in database (TODO: Add your DB connection)
    // Example: INSERT INTO payments (phone, amount, receipt, date) VALUES ($phoneNumber, $amountPaid, $receiptNumber, $transactionDate);

    // Sava data to database (rate_payment)
    $id = $_SESSION['user_id'];

    $query = "INSERT INTO rate_payment (user_id, titledeed_no, datepayed, amount ) VALUES (:id, :titledeed, :datepayed, :amount)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':titledeed', $titleDeed);
    $stmt->bindParam(':datepayed', now());
    $stmt->bindParam(':amount', $amountPaid);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Payment received successfully']);
    } else {
        echo json_encode(['message' => 'Failed to save payment']);
    }
} else {
    echo json_encode(['message' => 'Payment failed']);
    

}
?>
