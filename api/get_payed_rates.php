<?php
// Get the payed rates for a parcel
session_start();
require '../configs.php';

header("Content-Type: application/json");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get the parcel id
$data = json_decode(file_get_contents("php://input"), true);
$parcel_id = $data['parcel_id'] ?? null;

// Validate input
if (!$parcel_id) {
    echo json_encode(['success' => false, 'message' => 'Parcel ID missing']);
    exit();
}

// Get the payed rates for the parcel
$stmt = $conn->prepare("
    SELECT titledeed_no, SUM(amount) AS total_paid 
    FROM rate_payment 
    WHERE user_id = :id 
    GROUP BY titledeed_no
");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($payments);


?>
