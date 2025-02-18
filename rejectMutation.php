<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access!");
}

// Check if form_id and form_type are set in the POST request
if (isset($_POST['form_id']) && isset($_POST['form_type'])) {
    $form_id = $_POST['form_id'];
    $form_type = $_POST['form_type'];

    // Database connection details
    $host = "localhost";
    $port = "5432";
    $dbname = "klms";
    $user = "postgres";
    $password = "gredev";

    try {
        // Establish a database connection
        $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }

    // Determine the table and query based on form_type
    if ($form_type == "division") {
        $updateQuery = "UPDATE division_form SET status_id = 6 WHERE id = :form_id";
    } else if ($form_type == "ownership") {
        $updateQuery = "UPDATE ownership_form SET status_id = 6 WHERE id = :form_id";
    } else {
        die("Invalid form type!");
    }

    try {
        // Prepare and execute the query
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':form_id', $form_id, PDO::PARAM_INT);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            // Redirect to the dashboard after successful update
            header("Location: dashboard.php?user_id=" . $_SESSION['user_id']);
            exit(); // Ensure no further code is executed after the redirect
        } else {
            echo "No rows were updated. The form ID might not exist.";
        }
    } catch (PDOException $e) {
        die("Error executing query: " . $e->getMessage());
    }
} else {
    echo "Missing required form fields.";
}
?>