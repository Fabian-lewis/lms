<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access!");
}

$host = "localhost";
$port = "5432";
$dbname = "klms";
$password = "gredev";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", 'postgres', $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_id = $_POST['form_id'] ?? null;

    if (!$form_id) {
        die("Form ID is missing!");
    }

    $updateQuery = "UPDATE ownership_form SET status_id = 6 WHERE id = :form_id";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bindParam(':form_id', $form_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo "Mutation Rejected Successfully!";
    } else {
        echo "Error rejecting mutation!";
    }
}
?>
