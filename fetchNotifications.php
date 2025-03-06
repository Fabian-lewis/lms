<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Database connection
$host = "localhost";
$port = "5432";
$dbname = "klms";
$password = "gredev";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", 'postgres', $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$query = "SELECT * FROM notifications WHERE receiver_id = :receiver_id";
$stmt = $conn->prepare($query);
$stmt->bindValue(':receiver_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);



echo json_encode($notifications);


?>