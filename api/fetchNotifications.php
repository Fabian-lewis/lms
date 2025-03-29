<?php
// Ensure no unintended output
// ob_start();
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Set response header to JSON
header('Content-Type: application/json');

require(__DIR__ . '/../configs.php');

try {
    // Clean the output buffer to remove any whitespace or unwanted characters
    // ob_clean();

    // Prepare and execute query
    $query = "SELECT * FROM notifications WHERE receiver_id = :receiver_id AND status_id = 9 ORDER BY date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':receiver_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output JSON
    echo json_encode(["success" => true, "notifications" => $notifications ?: []]);
    exit(); // Ensure no further output

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit();
}
?>
