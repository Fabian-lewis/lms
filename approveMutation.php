<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: mutationFormView.php");
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_owner_natid = filter_input(INPUT_POST, 'current_owner_natid', FILTER_SANITIZE_NUMBER_INT);
    $proposed_owner_natid = filter_input(INPUT_POST, 'proposed_owner_natid', FILTER_SANITIZE_NUMBER_INT);
    $titledeed_no = filter_input(INPUT_POST, 'titledeed_no', FILTER_SANITIZE_STRING);
    $form_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    if (!$current_owner_natid || !$proposed_owner_natid || !$titledeed_no || !$form_id) {
        echo json_encode(["status" => "error", "message" => "Missing required form fields"]);
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
        $stmt = $conn->prepare("
            UPDATE ownership 
            SET status_id = 2, date_end = NOW() 
            WHERE titledeed_no = :titledeed_no AND owner_id = :owner_id
        ");
        $stmt->execute([':titledeed_no' => $titledeed_no, ':owner_id' => $current_owner_id]);

        // Fetch proposed owner ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE nat_id = :proposed_owner_natid");
        $stmt->execute([':proposed_owner_natid' => $proposed_owner_natid]);
        $proposed_owner_id = $stmt->fetchColumn();

        if (!$proposed_owner_id) {
            throw new Exception("Proposed owner not found");
        }

        // Insert new ownership
        $stmt = $conn->prepare("
            INSERT INTO ownership (titledeed_no, owner_id, status_id, date_started) 
            VALUES (:titledeed_no, :owner_id, 1, NOW())
        ");
        $stmt->execute([':titledeed_no' => $titledeed_no, ':owner_id' => $proposed_owner_id]);

        // Update form status to approved
        $stmt = $conn->prepare("UPDATE ownership_form SET status_id = 2 WHERE id = :form_id");
        $stmt->execute([':form_id' => $form_id]);

        // Commit transaction
        $conn->commit();

        echo json_encode(["status" => "success", "message" => "Mutation approved"]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
