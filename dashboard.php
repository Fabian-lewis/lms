<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    if($_SESSION['role'] === 'ministry_official'){
        ;
    }else{
        header("Location: login.php");
    }
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
                s.status,
                l.landtype
                FROM
                ownership o
                JOIN parcel p ON o.titledeed_no = p.titledeedno
                JOIN status s ON o.status_id = s.id
                JOIN landtype l ON p.landtypeid = l.id
                WHERE
                o.owner_id = :owner_id AND o.status_id = :status_id";
    $stmt2 = $conn->prepare($query);

    // Bind the parameter
    $stmt2->bindValue(':owner_id', $id, PDO::PARAM_INT);
    $stmt2->bindValue(':status_id', 1, PDO::PARAM_INT);

    // Execute the query
    $stmt2->execute();

    // Fetch results
    $parcels = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    //Ownership change Mutation Form
    $query1 = "SELECT
                o.id,
                o.titledeed_no,
                o.date_submitted,
                o.current_owner_natid,
                CONCAT(curr_owner.fname, ' ', curr_owner.sname) AS current_owner_name,
                o.proposed_owner_natid,
                CONCAT(prop_owner.fname, ' ', prop_owner.sname) AS proposed_owner_name,
                s.status
                FROM
                ownership_form o
                JOIN status s ON o.status_id = s.id
                JOIN users curr_owner ON o.current_owner_natid = curr_owner.nat_id
                JOIN users prop_owner ON o.proposed_owner_natid = prop_owner.nat_id
                WHERE
                o.surveyor_id = :owner_id";
    $stmt3 = $conn->prepare($query1);
    $stmt3->bindValue(':owner_id', $id, PDO::PARAM_INT);
    $stmt3->execute();
    $mutationForms = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    //Division Mutation
    $query2 = "SELECT
                d.id,
                d.titledeed,
                d.number_of_divs,
                d.date_submitted,
                d.surveyor_id,
                d.status_id,
                s.status
                FROM
                division_form d
                JOIN status s ON d.status_id = s.id
                WHERE
                d.surveyor_id = :owner_id";
    $stmt4 = $conn->prepare($query2);
    $stmt4->bindValue(':owner_id', $id, PDO::PARAM_INT);
    $stmt4->execute();
    $divisionForms = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    // Submitted Division Mutatition Forms for Ministry officials
    $query3 = "SELECT
                d.id,
                d.titledeed,
                d.number_of_divs,
                d.date_submitted,
                d.divisions_coordinates,
                CONCAT(surveyor.fname, ' ', surveyor.sname) AS surveyor,
                d.surveyor_id,
                d.status_id,
                s.status
                FROM
                division_form d
                JOIN status s ON d.status_id = s.id
                JOIN users surveyor ON d.surveyor_id = surveyor.id
                WHERE
                d.status_id = :status_id";
    $stmt5 = $conn->prepare($query3);
    $stmt5->bindValue(':status_id', 3, PDO::PARAM_INT);
    $stmt5->execute();
    $submittedDivisionForms = $stmt5->fetchAll(PDO::FETCH_ASSOC);

    // Submitted Ownership Mutatition Forms for Ministry officials
    $query4 = "SELECT
                o.id,
                o.titledeed_no,
                o.current_owner_natid,
                o.proposed_owner_natid,
                o.date_submitted,
                CONCAT(curr_owner.fname, ' ', curr_owner.sname) AS current_owner_name,
                CONCAT(prop_owner.fname, ' ', prop_owner.sname) AS proposed_owner_name,
                CONCAT(surveyor.fname, ' ', surveyor.sname) AS surveyor,
                o.surveyor_id,
                o.status_id,
                s.status
                FROM
                ownership_form o
                JOIN status s ON o.status_id = s.id
                JOIN users surveyor ON o.surveyor_id = surveyor.id
                JOIN users curr_owner ON o.current_owner_natid = curr_owner.nat_id
                JOIN users prop_owner ON o.proposed_owner_natid = prop_owner.nat_id
                WHERE
                o.status_id = :status_id";
    $stmt6 = $conn->prepare($query4);
    $stmt6->bindValue(':status_id', 3, PDO::PARAM_INT);
    $stmt6->execute();
    $submittedOwnershipForms = $stmt6->fetchAll(PDO::FETCH_ASSOC);



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
                <li>
                <button id="notificationBtn">
                    <img src="images/small_notification.png" alt="Notifications">
                    <span id="notificationCount">0</span> <!-- Badge for unread notifications -->
                </button>
                </li>
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

        <div id="notificationPane" class="notification-pane">
    <div class="notification-header">
        <h3>Notifications</h3>
        <button id="markAllAsRead">Mark All as Read</button>
    </div>
    <ul id="notificationList">
        <!-- Notifications will be dynamically inserted here -->
    </ul>
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
                    <p><strong>Land Type:</strong> <?php echo $parcel['landtype']; ?></p>
                    <p><strong>Status:</strong> <?php echo $parcel['status']; ?></p>
                    <a href="parcelDetails.php?parcel_id=<?php echo $parcel['id']; ?>" class="btn btn-primary">View Details</a>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if($_SESSION['role'] === 'surveyor'): ?>
        <div class="parcels-container">
            <h2>Land Division Mutation Forms</h2>
            <a href="mutationForm.php"><button class="new">Create New Form</button></a>
            <div class="card-wrapper">
                <?php foreach ($divisionForms as $form): ?>
                    <div class="card">
                        <h3>Form ID: <?php echo $form['id']; ?></h3>
                        <p><strong>Date Submitted:</strong> <?php echo $form['date_submitted']; ?></p>
                        <p><strong>Title Deed:</strong> <?php echo $form['titledeed']; ?></p>
                        <p><strong>Number of Divisions:</strong> <?php echo $form['number_of_divs']; ?></p>
                        <p><strong>Status:</strong> <?php echo $form['status']; ?></p>
                    </div>
                <?php endforeach; ?>
            
        </div>
        <div class="parcels-container">
            <h2>Ownership Change Mutation Forms</h2>
            <a href="mutationForm.php"><button class="new">Create New Form</button></a>
            <div class="card-wrapper">
                <?php foreach ($mutationForms as $form): ?>
                    <div class="card">
                        <h3>Form ID: <?php echo $form['id']; ?></h3>
                        <p><strong>Date Submitted:</strong> <?php echo $form['date_submitted']; ?></p>
                        <p><strong>Current Owner:</strong> <?php echo $form['current_owner_name']; ?></p>
                        <p><strong>Current Owner Nat ID:</strong><?php echo $form['current_owner_natid']?></p>
                        <p><strong>Proposed Owner:</strong> <?php echo $form['proposed_owner_name']; ?></p>
                        <p><strong>Proposed Owner Nat ID:</strong><?php echo $form['proposed_owner_natid']?></p>
                        <p><strong>Status:</strong> <?php echo $form['status']; ?></p>

                    </div>
                <?php endforeach; ?>

        

        </div>
    <?php endif; ?>

    <?php if($_SESSION['role'] === 'ministry_official'): ?>
        <div class="parcels-container">
            <h2>Land Division Mutation Forms</h2>
            <div class="card-wrapper">
                <?php foreach ($submittedDivisionForms as $form): ?>
                    <div class="card">
                        <h3>Form ID: <?php echo $form['id']; ?></h3>
                        <p><strong>Date Submitted:</strong> <?php echo $form['date_submitted']; ?></p>
                        <p><strong>Surveyor:</strong> <?php echo $form['surveyor']; ?></p>
                        <p><strong>Title Deed:</strong> <?php echo $form['titledeed']; ?></p>
                        <p><strong>Number of Divisions:</strong> <?php echo $form['number_of_divs']; ?></p>
                        <p><strong>Status:</strong> <?php echo $form['status']; ?></p>
                        <a href="mutationFormView.php?form_id=<?php echo $form['id'];?>&form_type=<?php echo 'division'?>" class="btn btn-primary">View Details</a>

                    </div>
                <?php endforeach; ?>
            
        </div>
        <div class="parcels-container">
            <h2>Ownership Change Mutation Forms</h2>
            <div class="card-wrapper">
                <?php foreach ($submittedOwnershipForms as $form): ?>
                    <div class="card">
                        <h3>Form ID: <?php echo $form['id']; ?></h3>
                        <p><strong>Date Submitted:</strong> <?php echo $form['date_submitted']; ?></p>
                        <p><strong>Surveyor:</strong> <?php echo $form['surveyor']; ?></p>
                        <p><strong>Current Owner:</strong> <?php echo $form['current_owner_name']; ?></p>
                        <p><strong>Proposed Owner:</strong> <?php echo $form['proposed_owner_name']; ?></p>
                        <p><strong>Status:</strong> <?php echo $form['status']; ?></p>
                        <a href="mutationFormView.php?form_id=<?php echo $form['id'];?>&form_type=<?php echo 'ownership'?>" class="btn btn-primary">View Details</a>

                    </div>
                <?php endforeach; ?>
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
            // Fetch Notifications
// Fetch Notifications
async function fetchNotifications() {
    try {
        const response = await fetch('fetchNotifications.php'); // User ID is handled in PHP
        const notifications = await response.json();

        console.log(notifications); // Debugging output

        const notificationList = document.getElementById('notificationList');
        const notificationCount = document.getElementById('notificationCount');

        // Clear existing notifications
        notificationList.innerHTML = '';

        // Count unread notifications (assuming status 0 means unread)
        const unreadCount = notifications.filter(n => n.status_id == 9).length;
        notificationCount.innerText = unreadCount;

        // Render notifications
        notifications.forEach(notification => {
            const li = document.createElement('li');
            li.className = notification.status_id == 9 ? 'unread' : 'read';
            li.innerHTML = `
                <strong>${notification.title || 'Notification'}</strong>
                <p>${notification.message}</p>
                <small>${new Date(notification.date).toLocaleString()}</small>
            `;
            li.addEventListener('click', () => markAsRead(notification.id));
            notificationList.appendChild(li);
        });
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
}


// Mark Notification as Read
async function markAsRead(notificationId) {
    try {
        await fetch(`mark-notification-read.php?id=${notificationId}`, { method: 'POST' });
        fetchNotifications(); // Refresh the list
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

// Mark All Notifications as Read
document.getElementById('markAllAsRead').addEventListener('click', async () => {
    try {
        await fetch('mark-all-notifications-read.php', { method: 'POST' });
        fetchNotifications(); // Refresh the list
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
});

// Toggle Notification Pane
document.getElementById('notificationBtn').addEventListener('click', () => {
    const pane = document.getElementById('notificationPane');
    pane.style.display = pane.style.display === 'block' ? 'none' : 'block';
    fetchNotifications(); // Fetch notifications when the pane is opened
});

// Fetch notifications on page load
fetchNotifications();
    </script>

    </main>
    
</body>
</html>
