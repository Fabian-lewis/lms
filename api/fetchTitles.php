<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Database connection
require(__DIR__ . '/../configs.php');


$titleDeed = $_GET['title_deed'] ?? null;

if (!$titleDeed) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Title deed not provided']);
    exit();
}

$query = "SELECT COUNT(*) AS count FROM parcel WHERE titledeedno = :title_deed";
$stmt = $conn->prepare($query);
$stmt->bindValue(':title_deed', $titleDeed, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}
?>