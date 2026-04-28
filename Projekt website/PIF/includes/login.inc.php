<?php
// login.inc.php
session_start();
require '../includes/db_connection.php';  // Changed from 'db_connection.php'
require '../config/lang.php';          // Changed from 'lang.php'

$lang = $_SESSION['lang'] ?? 'en';

if (isset($_POST['login-submit'])) {
    $uid = trim($_POST['uid'] ?? '');
    $pwd = trim($_POST['pwd'] ?? '');

    if (empty($uid) || empty($pwd)) {
        $_SESSION['login_error'] = ($lang === 'de') 
            ? 'Bitte Benutzername und Passwort eingeben.' 
            : 'Please enter username and password.';
        header('Location: ../auth/login.php');  // Changed from 'login.php'
        exit;
    }

    // Check if user exists by username or email
    $sql = "SELECT pk_username, password, role, firstName, lastName, email, mustChangePassword 
            FROM user 
            WHERE pk_username = ? OR email = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $uid, $uid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($pwd, $row['password'])) {
            // Login successful
            $_SESSION['loggedin'] = true;
            $_SESSION['pk_username'] = $row['pk_username'];
            $_SESSION['username'] = $row['pk_username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['firstName'] = $row['firstName'];
            $_SESSION['lastName'] = $row['lastName'];
            $_SESSION['email'] = $row['email'];

            // Check if user must change password
            if ($row['mustChangePassword'] == 1) {
                // Redirect to force change password page
                header('Location: ../includes/force_change_password.php');  // Changed from 'force_change_password.php'
                exit;
            } else {
                // Normal login - go to dashboard
                header('Location: ../user/dashboard.php');  // Changed from 'dashboard.php'
                exit;
            }
        } else {
            $_SESSION['login_error'] = ($lang === 'de') 
                ? 'Ungültiges Passwort.' 
                : 'Invalid password.';
            header('Location: ../auth/login.php');  // Changed from 'login.php'
            exit;
        }
    } else {
        $_SESSION['login_error'] = ($lang === 'de') 
            ? 'Benutzer nicht gefunden.' 
            : 'User not found.';
        header('Location: ../auth/login.php');  // Changed from 'login.php'
        exit;
    }

    mysqli_stmt_close($stmt);
} else {
    header('Location: ../auth/login.php');  // Changed from 'login.php'
    exit;
}
?>