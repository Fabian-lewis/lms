<?php
$dsn = " pgsql: host=dpg-cvfadurqf0us73fo3jf0-a.oregon-postgres.render.com;port=5432;dbname=klms;";  // Your Render database host
$user = "klms_user";
$password = "CRwSIcxPiQb6sz0k8ShbroeNIrPxhdu0";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Database connection successful!";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
