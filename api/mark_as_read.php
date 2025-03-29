<?php
session_start();
require(__DIR__ . '/../configs.php');

header("Content-Type: application/json");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['notificationId'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID missing']);
    exit();
}

$notificationId = intval($data['notificationId']);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Update notification status
$query = "UPDATE notifications SET status_id = 10 WHERE id = :id AND receiver_id = :receiver_id"; // Assuming status_id = 10 means 'read'
$stmt = $conn->prepare($query);
$stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
$stmt->bindValue(':receiver_id', $_SESSION['user_id'], PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
}
?>
