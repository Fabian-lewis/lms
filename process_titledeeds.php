<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: approveDivForm.php");
    exit();
}

// Database connection
$host = "localhost";
$port = "5432";
$dbname = "klms";
$password = "gredev";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction(); // Start transaction

        $newTitleDeeds = $_POST['new_title_deeds'] ?? [];
        $form_id = $_POST['form_id'] ?? null;
        $owner_id = $_POST['owner_id'] ?? null;
        $landtypeid = $_POST['landtypeid'] ?? null;
        $divisions = $_POST['divisions'] ?? [];

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

        // Deactivate current ownership
        $stmt = $conn->prepare("UPDATE ownership SET status_id = 2, date_end = NOW() 
                                WHERE titledeed_no = :titledeed_no AND owner_id = :owner_id");
        $stmt->execute([
            ':titledeed_no' => $_POST['old_titledeed'] ?? '',
            ':owner_id' => $owner_id
        ]);

        $conn->commit(); // Commit transaction

        echo json_encode(['success' => true, 'message' => 'Title deeds created successfully']);
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback transaction on error
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
