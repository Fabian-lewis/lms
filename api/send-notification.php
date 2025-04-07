<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

header('Content-Type: application/json');

require(__DIR__ . '/../configs.php');

try {
    // Ensure sender is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized: User not logged in.");
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

    // Sanitize inputs
    $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $ownerName = htmlspecialchars($data['ownerName']);
    $titleDeed = htmlspecialchars($data['titledeed']);

    // Get receiver ID
    $query = "SELECT id FROM users WHERE phone = :phone AND email = :email LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':phone', $phone);
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$receiver) {
        throw new Exception("User not found with provided phone and email.");
    }

    // Compose message
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
    $stmt->bindParam(':sender_id', $_SESSION['user_id']);
    $stmt->bindParam(':receiver_id', $receiver['id']);
    $stmt->bindParam(':message', $message);

    if (!$stmt->execute()) {
        throw new Exception("Failed to save notification.");
    }


    echo json_encode([
        'success' => true,
        'message' => 'Notification sent successfully',
        'alertMessage' => 'Notification sent successfully!',
        'notificationId' => $conn->lastInsertId()
    ]);
    exit;

} catch (Exception $e) {
    // Send JSON error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
