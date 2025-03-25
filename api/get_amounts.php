<?php
header('Content-Type: application/json');

// Database connection
require 'configs.php';


try{
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

    // Return the data as JSON
    echo json_encode($rates);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>