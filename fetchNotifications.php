<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Database connection
require 'configs.php';


$query = "SELECT * FROM notifications WHERE receiver_id = :receiver_id";
$stmt = $conn->prepare($query);
$stmt->bindValue(':receiver_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);



echo json_encode($notifications);


?>