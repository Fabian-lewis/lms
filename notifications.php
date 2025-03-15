<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
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

$query = "SELECT
            n.id,
            n.sender_id,
            n.message,
            n.date,
            CONCAT(sender.fname, ' ', sender.sname) AS sender_name,
            u.phone,
            u.email
        FROM
            notifications n
        JOIN users sender ON n.sender_id = sender.id
        JOIN users u ON n.sender_id = u.id
        WHERE receiver_id = :receiver_id AND n.status_id = 9;";
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
            <img src="images/lms logo2.png" alt="LMS Logo">
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
                        <button >Mark as Read</button>

                    </div>
                <?php endforeach; ?>
        </div>

        <script>
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('click', async (e) => {
                    const notificationId = e.target.parentElement.querySelector('h2').textContent;
                    const response = await fetch('api/mark_as_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ notificationId })
                    });
                    const data = await response.json();
                    if (data.success) {
                        e.target.parentElement.remove();
                    }
                });
            });
        </script>
    </main>
</body>
</html>
