<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require 'configs.php';


$query = "SELECT
    n.id,
    n.sender_id,
    n.message,
    n.date,
    COALESCE(CONCAT(sender.fname, ' ', sender.sname), 'System') AS sender_name, 
    sender.phone,
    sender.email
FROM notifications n
LEFT JOIN users sender ON n.sender_id = sender.id -- Allow sender_id to be NULL
WHERE n.receiver_id = :receiver_id AND n.status_id = 9;";
$stmt = $conn->prepare($query);
$stmt->bindValue(':receiver_id', $_SESSION['user_id'], PDO::PARAM_INT);

$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="css/notification.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/lms_logo2.PNG" alt="LMS Logo">
        </div>
        <h1>Notifications</h1>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="card-wrapper">
        <?php foreach ($notifications as $notification): ?>
    <div class="card">
        <h3>From: <?= $notification['sender_name'] ?></h3>
        <p>EMAIL: <?= $notification['email'] ?></p>
        <p>PHONE: <?= $notification['phone'] ?></p>
        <p><?= $notification['message'] ?></p>
        <p><?= $notification['date'] ?></p>
        <button class="mark-read-btn" data-id="<?= $notification['id'] ?>">Mark as Read</button>
    </div>
<?php endforeach; ?>

        </div>

        <script>
    document.addEventListener("DOMContentLoaded", () => {
        const buttons = document.querySelectorAll('.mark-read-btn');
        
        buttons.forEach(button => {
            button.addEventListener('click', async (e) => {
                const notificationId = e.target.getAttribute('data-id');

                const response = await fetch('api/mark_as_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ notificationId: parseInt(notificationId) })
                });

                const data = await response.json();

                if (data.success) {
                    e.target.parentElement.remove(); // Remove card after successful update
                } else {
                    alert("Failed to mark notification as read.");
                }
            });
        });
    });
</script>

    </main>
</body>
</html>
