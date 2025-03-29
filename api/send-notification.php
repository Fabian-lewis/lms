<?php
session_start();
header('Content-Type: application/json');

require(__DIR__ . '/../configs.php');

try {
    // Ensure database connection is established
    if (!isset($conn)) {
        throw new Exception("Database connection error.");
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception("Invalid request data.");
    }

    // Validate required fields
    $requiredFields = ['phone', 'ownerName', 'email', 'titledeed'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate session
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        throw new Exception("Session expired. Please login again.");
    }

    // Sanitize inputs
    $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $ownerName = htmlspecialchars($data['ownerName']);
    $titleDeed = htmlspecialchars($data['titledeed']);

    // Get receiver ID securely
    $query = "SELECT id FROM users WHERE phone = :phone AND email = :email LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);

    if (!$stmt->execute()) {
        throw new Exception("Database query failed.");
    }

    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$receiver) {
        throw new Exception("User not found with provided phone and email.");
    }

    // Prepare notification message
    $message = sprintf(
        "Dear %s, kindly pay up your outstanding lease rate for Land Parcel %s.",
        $ownerName,
        $titleDeed
    );

    // Insert notification
    $query = "INSERT INTO notifications 
              (sender_id, receiver_id, message, date, status_id) 
              VALUES (:sender_id, :receiver_id, :message, NOW(), 9)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':receiver_id', $receiver['id'], PDO::PARAM_INT);
    $stmt->bindParam(':sender_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':message', $message, PDO::PARAM_STR);

    if (!$stmt->execute()) {
        throw new Exception("Failed to save notification.");
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Notification sent successfully',
        'notificationId' => $conn->lastInsertId()
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Notification Error: " . $e->getMessage());

    // Return error response with proper HTTP code
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
