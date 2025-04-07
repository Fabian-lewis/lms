<?php
// Place Land on sale
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

require 'configs.php';

header("Content-Type: application/json");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

?>
