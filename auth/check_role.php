<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // This will not be called if session is already active
}

function requireRole($allowedRoles = []) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: /lms/login.php");
        exit();
    }

    if (!in_array($_SESSION['role'], $allowedRoles)) {
        // Optionally store an alert message
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Unauthorized access!'
        ];
        header("Location: /lms/dashboard.php");
        exit();
    }
}
