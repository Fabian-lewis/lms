<?php
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$port = "5432";
$dbname = "klms";
$user = "postgres";
$password = "gredev";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}


// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
$parcelId =$data['parcelId'] ?? null;
$buyerId = filter_var($data['userId'] ?? null, FILTER_VALIDATE_INT);


if (!$parcelId || !$buyerId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Fetch buyer details
$query = "SELECT * FROM users WHERE id = :buyer_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':buyer_id', $buyerId, PDO::PARAM_INT);
$stmt->execute();
$buyer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$buyer) {
    echo json_encode(['success' => false, 'message' => 'Buyer not found']);
    exit;
}

// Fetch parcel owner ID
$query = "SELECT owner_id FROM ownership WHERE titledeed_no = :parcel_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':parcel_id', $parcelId, PDO::PARAM_STR);
$stmt->execute();
$ownerId = $stmt->fetchColumn();

if (!$ownerId) {
    echo json_encode(['success' => false, 'message' => 'Parcel not found']);
    exit;
}

// Create a new notification for the parcel owner
$message = sprintf(
    "%s %s has shown interest in your parcel with ID: %s. Contact them at %s or %s for more details.",
    $buyer['fname'],
    $buyer['sname'],
    $parcelId,
    $buyer['email'],
    $buyer['phone']
);

$query = "INSERT INTO notifications (sender_id, receiver_id, message, date, status_id) VALUES (:sender_id, :receiver_id, :message, NOW(), 9)";
$stmt = $conn->prepare($query);
$stmt->bindParam(':sender_id', $buyerId, PDO::PARAM_INT);
$stmt->bindParam(':receiver_id', $ownerId, PDO::PARAM_INT);
$stmt->bindParam(':message', $message, PDO::PARAM_STR);

try {
    $stmt->execute();
    error_log("Notification sent: Parcel ID $parcelId, Buyer ID $buyerId, Owner ID $ownerId");
    echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
} catch (PDOException $e) {
    error_log("Failed to send notification: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send notification']);
}
?>
