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
    <link rel="stylesheet" href="css/parcelDetails.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    
</head>
<body>
    <div class="container">
        <!-- Parcel Details -->
        <div class="details-card">
            <h1>Parcel Details</h1>
            <p><strong>Title Deed Number:</strong> <?php echo htmlspecialchars($parcel['titledeedno']); ?></p>
            <p><strong>Date Created:</strong> <?php echo htmlspecialchars($parcel['datecreated']); ?></p>
            <p><strong>Land Type ID:</strong> <?php echo htmlspecialchars($parcel['landtypeid']); ?></p>
            <p><strong>Status ID:</strong> <?php echo htmlspecialchars($parcel['statusid']); ?></p>
        </div>

        <!-- Map -->
        <div id="map"></div>
    </div>

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

