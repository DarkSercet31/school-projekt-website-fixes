<?php
session_start();

// Route based on login state and role
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: auth/login.php');
    exit;
}

if ($_SESSION['role'] === 'Admin') {
    header('Location: admin/dashboard.php');
} else {
    header('Location: user/dashboard.php');
}
exit;
