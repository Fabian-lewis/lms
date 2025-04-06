<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

header('Content-Type: application/json');

// Database connection
require(__DIR__ . '/../configs.php');

// Get parcel ID from URL
$parcel_id = $_GET['parcel_id'];

$query = "UPDATE parcel SET statusid = 11 WHERE id = :parcel_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':parcel_id', $parcel_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Parcel status updated successfully']);
    header("Location: /lms/dashboard.php");
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update parcel status']);
    header("Location: dashboard.php");
    exit();
}

?>