<?php
session_start();

// Load database connection from the correct folder
if (!file_exists(__DIR__ . '/db_connection.php')) {
    $_SESSION['register_error'] = 'Database configuration error.';
    header('Location: ../auth/register.php');
    exit;
}

include __DIR__ . '/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['register'])) {
    header('Location: ../auth/register.php');
    exit;
}

// Get form data
$pk_username = trim($_POST['username'] ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

// Store form data in session for repopulation
$_SESSION['register_username'] = $pk_username;
$_SESSION['register_firstname'] = $firstName;
$_SESSION['register_lastname'] = $lastName;
$_SESSION['register_email'] = $email;

// Validation
$errors = [];

if ($pk_username === '') {
    $errors[] = 'Username is required';
}

if ($firstName === '') {
    $errors[] = 'First name is required';
}

if ($lastName === '') {
    $errors[] = 'Last name is required';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if ($password === '') {
    $errors[] = 'Password is required';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

// Check if username or email already exists
if (empty($errors)) {
    $check_sql = "SELECT pk_username FROM user WHERE pk_username = ? OR email = ?";
    $stmt = mysqli_prepare($link, $check_sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $pk_username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $errors[] = 'Username or email already exists';
        }

        mysqli_stmt_close($stmt);
    } else {
        $errors[] = 'Database error. Please try again.';
    }
}

// If there are errors, go back
if (!empty($errors)) {
    $_SESSION['register_error'] = implode('. ', $errors);
    header('Location: ../auth/register.php');
    exit;
}

// Insert new user without verification step
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$insert_sql = "INSERT INTO user (pk_username, firstName, lastName, email, password, status, role)
               VALUES (?, ?, ?, ?, ?, 'verified', 'User')";
$stmt = mysqli_prepare($link, $insert_sql);

if ($stmt) {
    mysqli_stmt_bind_param(
        $stmt,
        'sssss',
        $pk_username,
        $firstName,
        $lastName,
        $email,
        $password_hash
    );

    if (mysqli_stmt_execute($stmt)) {
        // Clear saved form data
        unset(
            $_SESSION['register_username'],
            $_SESSION['register_firstname'],
            $_SESSION['register_lastname'],
            $_SESSION['register_email'],
            $_SESSION['register_error']
        );

        // Log the user in directly
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $pk_username;
        $_SESSION['pk_username'] = $pk_username;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['role'] = 'User';
        $_SESSION['verified'] = true;

        header('Location: ../user/dashboard.php');
        exit;
    } else {
        $_SESSION['register_error'] = 'Registration failed. Please try again.';
    }

    mysqli_stmt_close($stmt);
} else {
    $_SESSION['register_error'] = 'Database error. Please try again.';
}

header('Location: ../auth/register.php');
exit;
?>