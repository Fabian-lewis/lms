<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

header('Content-Type: application/json');

// Database connection
require(__DIR__ . '/../configs.php');

// Get parcel ID from URL
$parcel_id = $_GET['parcel_id'];

if (empty($parcel_id)) {
    echo json_encode(['success' => false, 'message' => 'Parcel ID is required']);
    exit();
}
$query = "UPDATE parcel SET statusid = 11 WHERE id = :parcel_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':parcel_id', $parcel_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Parcel placed for sale successfully.'];
} else {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Failed to update parcel status.'];
}
header('Location: /dashboard.php');
exit();
?>