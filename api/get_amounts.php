<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error reporting (for debugging)

// Database connection
require(__DIR__ . '/../configs.php');

try {
    // Query the database
    $query = "SELECT amount, year FROM rates_distribution";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    // Fetch all rows as an associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize data into a dictionary-like structure
    $rates = [];
    foreach ($rows as $row) {
        $year = $row['year']; // Use 'year' as the key
        $rates[$year] = $row['amount']; // Store 'amount' as the value
    }

    // Clear any unexpected output before JSON
    // ob_clean();

    // Return the JSON response
    echo json_encode($rates);
    exit;

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>