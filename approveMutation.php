<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

// Get FormID and FormType from URL
$form_id = $_GET['form_id'] ?? null;
$form_type = $_GET['form_type'] ?? null;

// Check if all required POST parameters are set
if (isset($_POST['form_id'], $_POST['current_owner_natid'], $_POST['proposed_owner_natid'], $_POST['titledeed_no'])) {
    $form_id = filter_input(INPUT_POST, 'form_id', FILTER_SANITIZE_NUMBER_INT);
    $current_owner_natid = filter_input(INPUT_POST, 'current_owner_natid', FILTER_SANITIZE_NUMBER_INT);
    $proposed_owner_natid = filter_input(INPUT_POST, 'proposed_owner_natid', FILTER_SANITIZE_NUMBER_INT);
    $titledeed_no = filter_input(INPUT_POST, 'titledeed_no', FILTER_SANITIZE_STRING);

    if (!$form_id || !$current_owner_natid || !$proposed_owner_natid || !$titledeed_no) {
        echo json_encode(["status" => "error", "message" => "Missing required form fields"]);
        exit();
    }

    // Database connection
    $host = "localhost";
    $port = "5432";
    $dbname = "klms";
    $username = "postgres";
    $password = "gredev";

    try {
        $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database connection failed"]);
        exit();
    }

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Fetch current owner ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE nat_id = :current_owner_natid");
        $stmt->execute([':current_owner_natid' => $current_owner_natid]);
        $current_owner_id = $stmt->fetchColumn();

        if (!$current_owner_id) {
            throw new Exception("Current owner not found");
        }

        // Deactivate current ownership
        $stmt = $conn->prepare("UPDATE ownership SET status_id = 2, date_end = NOW() WHERE titledeed_no = :titledeed_no AND owner_id = :owner_id");
        $stmt->execute([':titledeed_no' => $titledeed_no, ':owner_id' => $current_owner_id]);

        // Fetch proposed owner ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE nat_id = :proposed_owner_natid");
        $stmt->execute([':proposed_owner_natid' => $proposed_owner_natid]);
        $proposed_owner_id = $stmt->fetchColumn();

        if (!$proposed_owner_id) {
            throw new Exception("Proposed owner not found");
        }

        // Insert new ownership
        $stmt = $conn->prepare("INSERT INTO ownership (titledeed_no, owner_id, status_id, date_started) VALUES (:titledeed_no, :owner_id, 1, NOW())");
        $stmt->execute([':titledeed_no' => $titledeed_no, ':owner_id' => $proposed_owner_id]);

        // Update form status to approved
        $stmt = $conn->prepare("UPDATE ownership_form SET status_id = 5 WHERE id = :form_id");
        $stmt->execute([':form_id' => $form_id]);

        
        // Send Notification to proposed owner
        $stmt = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, message, date, status_id) VALUES (:sender_id, :receiver_id, 'Ownership transfer of Land Title Deed $titledeed_no approved. You now own the Land.'), NOW(), 9");
        $stmt->execute([':sender_id' => $_SESSION['user_id'], ':receiver_id' => $proposed_owner_id]);
         
        // Send Notification to current Owner
        $stmt = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, message, date, status_id) VALUES (:sender_id, :receiver_id, 'Ownership transfer of Land Title Deed $titledeed_no approved. The Land has a new owner'), NOW(), 9");
        $stmt->execute([':sender_id' => $_SESSION['user_id'], ':receiver_id' => $current_owner_id]);

        // Send Notification to Surveyor
        $stmt = $conn->prepare("SELECT surveyor_id FROM ownership_form WHERE id = :form_id");
        $stmt->execute([':form_id' => $form_id]);
        $surveyor_id = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, message, date, status_id) VALUES (:sender_id, :receiver_id, 'Ownership Mutation Form $form_id has been approved'), NOW(), 9");
        $stmt->execute([':sender_id' => $_SESSION['user_id'], ':receiver_id' => $surveyor_id]);




        // Commit transaction
        $conn->commit();

        // Success response
        echo json_encode(["status" => "success", "message" => "Mutation approved successfully"]);
        header("Location: dashboard.php?user_id=" . $_SESSION['user_id']);
    } catch (Exception $e) {
        // Rollback transaction in case of errors
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing required form fields"]);
}
?>
