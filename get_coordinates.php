<?php
header('Content-Type: application/json');

// Database connection
require 'configs.php';

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
    } else {
        echo json_encode(['error' => 'No coordinates found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

