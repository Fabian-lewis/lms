<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
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
$id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT fname, sname FROM users WHERE id = ?");
$stmt->execute([$id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);
if($user){
    $_SESSION['fname'] = $user['fname'];
    $_SESSION['sname'] = $user['sname'];
}
// Pass data to the profile page
try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", 'postgres', $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
$id = $_SESSION['user_id'];
$query = "SELECT
                p.id,
                p.coordinates,
                p.datecreated,
                p.titledeedno,
                o.date_started,
                o.status_id
                FROM
                ownership o
                JOIN
                parcel p
                ON
                o.titledeed_no = p.titledeedno
                WHERE
                o.owner_id = :owner_id";
$stmt2 = $conn->prepare($query);

// Bind the parameter
$stmt2->bindValue(':owner_id', $id, PDO::PARAM_INT);

// Execute the query
$stmt2->execute();

// Fetch results
$parcels = $stmt2->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <script>
    document.getElementById('fullname').innerText = "<?php echo $user['fname'] . ' ' . $user['sname']; ?>";
    document.getElementById('role').innerText = "<?php echo $user['role']; ?>";
    //document.getElementById('jobSecurity').innerText = " /** echo $user['job_securityNumber'] ?: 'N/A'; ?>";*/

    
</script>
</head>
<body>
<header>
        <div class="logo">
            <img src="images/lms logo2.png" alt="LMS Logo">
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
        </nav>
    </header>

    <main>
    <div class="profile-container">
        <h2>User Profile</h2>
        <div class="profile-info">
            <img src="images/default-profile.png" alt="Profile Picture" class="profile-pic">
            <div class="details">
            <p><strong>First Name:</strong> <?php echo htmlspecialchars($_SESSION['fname']); ?></p>
            <p><strong>Surname:</strong> <?php echo htmlspecialchars($_SESSION['sname']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></p>
                <p><strong>Job Security Number:</strong> <span id="jobSecurity">N/A</span></p>
            </div>
        </div>
        <div class="profile-actions">
            <button id="editProfileBtn">Edit Profile</button>
            <button id="logoutBtn">Logout</button>
        </div>
    </div>
    <div class="parcels-container">
        <h2>Your Lands</h2>
        <div class="card-wrapper">
            <?php foreach ($parcels as $parcel): ?>
                <div class="card">
                    <h3>Parcel: <?php echo $parcel['titledeedno']; ?></h3>
                    <p><strong>Coordinates:</strong> <?php echo $parcel['coordinates']; ?></p>
                    <p><strong>Date Created:</strong> <?php echo $parcel['datecreated']; ?></p>
                    <a href="parcelDetails.php?parcel_id=<?php echo $parcel['id']; ?>" class="btn btn-primary">View Details</a>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if($_SESSION['role'] === 'surveyor'): ?>
        <div class="parcels-container">
            <h2>Land Division Mutation Forms</h2>
            <button class="new">Create New Form</button>
        </div>
    <div class="parcels-container">
        <h2>Ownership Change Mutation Forms</h2>
        <button class="new">Create New Form</button>

    </div>
    <?php endif; ?>
    <!-- Modal for Editing Profile -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <h3>Edit Profile</h3>
            <form action="update_profile.php" method="POST">
                <label for="fname">First Name:</label>
                <input type="text" id="fname" name="fname" required>
                
                <label for="sname">Surname:</label>
                <input type="text" id="sname" name="sname" required>

                <label for="role">Role:</label>
                <input type="text" id="roleInput" name="role" disabled>

                <button type="submit">Save Changes</button>
                <button type="button" id="closeModalBtn">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        // JavaScript for Modal Toggle
        const editProfileBtn = document.getElementById('editProfileBtn');
        const editProfileModal = document.getElementById('editProfileModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        editProfileBtn.addEventListener('click', () => {
            editProfileModal.style.display = 'block';
        });

        closeModalBtn.addEventListener('click', () => {
            editProfileModal.style.display = 'none';
        });
    </script>

    </main>
    
</body>
</html>
