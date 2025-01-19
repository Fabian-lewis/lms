<?php
// Database connection
$host = "localhost";
$port = "5432";
$dbname = "klms";
//$user = "postgres";
$password = "gredev";
try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", 'postgres', $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get parcel ID from URL
$parcel_id = $_GET['parcel_id'];

$query = "SELECT * FROM parcel WHERE id = :parcel_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':parcel_id', $parcel_id, PDO::PARAM_INT);
$stmt->execute();

$parcel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parcel) {
    die("Parcel not found!");
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcel Details</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <style>
        #map { height: 400px; }
    </style>
</head>
<body>
    <h1>Details for Parcel <?php echo htmlspecialchars($parcel['id']); ?></h1>
    <p><strong>Title Deed Number:</strong> <?php echo htmlspecialchars($parcel['titledeedno']); ?></p>
    <p><strong>Date Created:</strong> <?php echo htmlspecialchars($parcel['datecreated']); ?></p>
    <div id="map"></div>

    <script>
        const map = L.map('map').setView([0, 0], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        const parcelCoordinates = <?php echo $parcel['coordinates']; ?>;
        const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);

        map.fitBounds(geoJsonLayer.getBounds());
    </script>
</body>
</html>
