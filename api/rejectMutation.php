<?php
ob_start();  // Start output buffering
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
    require(__DIR__ . '/../configs.php');

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
            echo json_encode(["status" => "success", "message" => "Mutation rejected successfully!"]);

            try {
                // Determine the correct table
                $table = ($form_type == "division") ? "division_form" : "ownership_form";
            
                // Fetch Surveyor ID of the form
                $surveyorQuery = "SELECT surveyor_id FROM $table WHERE id = :form_id";
                $surveyorStmt = $conn->prepare($surveyorQuery);
                $surveyorStmt->execute([':form_id' => $form_id]);
                $surveyor_id = $surveyorStmt->fetchColumn();
            
                // Ensure surveyor_id is valid before inserting the notification
                if ($surveyor_id) {
                    // Send Notification
                    $notificationQuery = "INSERT INTO notifications (sender_id, receiver_id, message, date, status_id) 
                                          VALUES (:sender_id, :receiver_id, :message, NOW(), 9)";
                    $notificationStmt = $conn->prepare($notificationQuery);
                    $notificationStmt->execute([
                        ':sender_id' => $_SESSION['user_id'],
                        ':receiver_id' => $surveyor_id,
                        ':message' => "Your $form_type mutation request, Form ID: $form_id has been rejected."
                    ]);
                } else {
                    echo "Error: Could not find the surveyor for this form.";
                }
            } catch (PDOException $e) {
                die("Error: " . $e->getMessage());
            }
            
            exit();
        } else {
            echo json_encode(["status" => "error", "message" => "No rows were updated. The form ID might not exist."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error executing query: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Missing required form fields."]);
}

ob_end_flush();  // Flush output buffer
?>
