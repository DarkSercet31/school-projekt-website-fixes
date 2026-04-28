<?php
// Redirect to login or dashboard
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: user/index.php');
    }
} else {
    header('Location: auth/login.php');
}
exit();
?>