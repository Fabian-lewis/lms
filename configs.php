<?php
$host = " dpg-cvfadurqf0us73fo3jf0-a.oregon-postgres.render.com";  // Your Render database host
$dbname = "klms";
$user = "klms_user";
$password = "CRwSIcxPiQb6sz0k8ShbroeNIrPxhdu0";

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
