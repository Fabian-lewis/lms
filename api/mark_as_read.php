<?php
// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
$notificationId = filter_var($data['notificationId'] ?? null, FILTER_VALIDATE_INT);

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Mark the notification as read
$query = "UPDATE notifications SET status_id = 10 WHERE id = :notification_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);

try {
    $stmt->execute();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}



?>