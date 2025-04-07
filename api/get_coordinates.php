<?php
// Start output buffering to prevent unintended output
session_start();

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

// Database connection
require(__DIR__ . '/../configs.php');

try{

    // Get the title deed number from the request
    $data = json_decode(file_get_contents('php://input'), true);
    $titleDeed = $data['titleDeed'];

    // Query the database
    $query = "SELECT coordinates FROM parcel WHERE titledeedno = :titledeed";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':titledeed', $titleDeed);
    $stmt->execute();
    $parcelCoordinates = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return the coordinates as JSON
    if ($parcelCoordinates) {
        echo json_encode(['coordinates' => json_decode($parcelCoordinates['coordinates'])]);
        exit();
    } else {
        echo json_encode(['error' => 'No coordinates found']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>