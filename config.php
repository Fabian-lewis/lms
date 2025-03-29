<?php
$host = "localhost";  // Your Render database host
$dbname = "klms";
$user = "postgres";
$password = "gredev";

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!-- <?php
// $host = getenv('DB_HOST'); // Render database hostname
// $dbname = getenv('DB_NAME');
// $user = getenv('DB_USER');
// $pass = getenv('DB_PASS');

// try {
//     $conn = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass, [
//         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
//     ]);
// } catch (PDOException $e) {
//     die("Database connection failed: " . $e->getMessage());
// }

?>