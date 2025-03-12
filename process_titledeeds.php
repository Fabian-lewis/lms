<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: approveDivForm.php");
    exit();
}

header('Content-Type: application/json');

// Database connection
$host = "localhost";
$port = "5432";
$dbname = "klms";
$user = "postgres";
$password = "gredev";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Read and decode JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input', 'raw_data' => $inputJSON]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($input)) {
    try {
        $conn->beginTransaction(); // Start transaction

        $newTitleDeeds = $input['new_title_deeds'] ?? [];
        $form_id = $input['form_id'] ?? null;
        $owner_id = $input['owner_id'] ?? null;
        $landtypeid = $input['landtypeid'] ?? null;
        $divisions = $input['divisions'] ?? [];
        $currentTitleDeed = $input['currentTitleDeed'] ?? null;

        if (empty($newTitleDeeds) || !$form_id || !$owner_id || !$landtypeid) {
            echo json_encode(['success' => false, 'message' => 'Missing required data']);
            exit;
        }
        foreach ($newTitleDeeds as $index => $titleDeed) {
            $coordinates = json_encode($divisions[$index] ?? []);

            // Insert into parcel table
            $stmt = $conn->prepare("INSERT INTO parcel (titledeedno, coordinates, landtypeid, statusid, datecreated) 
                                    VALUES (:titledeed, :coordinates, :landtypeid, 1, NOW())");
            $stmt->execute([
                ':titledeed' => $titleDeed,
                ':coordinates' => $coordinates,
                ':landtypeid' => $landtypeid
            ]);

            // Insert into ownership table
            $stmt = $conn->prepare("INSERT INTO ownership (titledeed_no, owner_id, status_id, date_started) 
                                    VALUES (:titledeed_no, :owner_id, 1, NOW())");
            $stmt->execute([
                ':titledeed_no' => $titleDeed,
                ':owner_id' => $owner_id
            ]);
        }
        // Update division form status
        $stmt = $conn->prepare("UPDATE division_form SET status_id = 5 WHERE id = :form_id");
        $stmt->execute([':form_id' => $form_id]);

        // Update Current Ownership
        $stmt = $conn->prepare("UPDATE ownership SET status_id = 2 WHERE owner_id = :owner_id && titledeed_no = :currentTitleDeed");
        $stmt->execute([':owner_id' => $owner_id, ':currentTitleDeed' => $currentTitleDeed]);
        

        $conn->commit(); // Commit transaction

        echo json_encode(['success' => true, 'message' => 'Title deeds created successfully']);
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback transaction on error
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>