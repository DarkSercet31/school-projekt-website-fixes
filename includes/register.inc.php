<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/db_connection.php';
require __DIR__ . '/../config/lang.php';
require __DIR__ . '/../config/mailtrap.php';

$lang = $_SESSION['lang'] ?? 'en';

if (!isset($_POST['register'])) {
    header('Location: ../auth/register.php');
    exit;
}

$username  = trim($_POST['username']         ?? '');
$firstName = trim($_POST['firstName']        ?? '');
$lastName  = trim($_POST['lastName']         ?? '');
$email     = trim($_POST['email']            ?? '');
$password  = $_POST['password']              ?? '';
$confirm   = $_POST['confirm_password']      ?? '';

$_SESSION['register_username']  = $username;
$_SESSION['register_firstname'] = $firstName;
$_SESSION['register_lastname']  = $lastName;
$_SESSION['register_email']     = $email;

if ($username === '' || $firstName === '' || $lastName === '' || $email === '') {
    $_SESSION['register_error'] = ($lang === 'de')
        ? 'Alle Felder sind Pflichtfelder.'
        : 'All fields are required.';
    header('Location: ../auth/register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = ($lang === 'de')
        ? 'Bitte gib eine gültige E-Mail-Adresse ein.'
        : 'Please enter a valid email address.';
    header('Location: ../auth/register.php');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['register_error'] = ($lang === 'de')
        ? 'Die Passwörter stimmen nicht überein.'
        : 'Passwords do not match.';
    header('Location: ../auth/register.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['register_error'] = ($lang === 'de')
        ? 'Das Passwort muss mindestens 8 Zeichen lang sein.'
        : 'Password must be at least 8 characters.';
    header('Location: ../auth/register.php');
    exit;
}

$stmt = mysqli_prepare($link, "SELECT pk_username FROM user WHERE pk_username = ? OR email = ?");
mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$exists = mysqli_stmt_num_rows($stmt) > 0;
mysqli_stmt_close($stmt);

if ($exists) {
    $_SESSION['register_error'] = ($lang === 'de')
        ? 'Benutzername oder E-Mail bereits vergeben.'
        : 'Username or email is already taken.';
    header('Location: ../auth/register.php');
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$sql  = "INSERT INTO user
             (pk_username, firstName, lastName, password, email, role, status, mustChangePassword)
         VALUES (?, ?, ?, ?, ?, 'User', 'notverified', 0)";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 'sssss', $username, $firstName, $lastName, $hash, $email);

if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['register_error'] = 'Registration failed. Please try again.';
    mysqli_stmt_close($stmt);
    header('Location: ../auth/register.php');
    exit;
}
mysqli_stmt_close($stmt);

// Generate verification token and store in email_tokens
$token     = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
$pdo->prepare(
    "INSERT INTO email_tokens (token, fk_username, type, expires_at) VALUES (?, ?, 'verify', ?)"
)->execute([$token, $username, $expiresAt]);

// Send verification link via Mailtrap
$verifyLink = 'https://10.246.45.135/auth/verify.php?token=' . $token;
    try {
        $mail = createMailer();
        $mail->addAddress($email, $firstName . ' ' . $lastName);
        $mail->Subject = 'Verify your Weather Station account';
        $mail->isHTML(true);
        $mail->Body = "
            <h2>Welcome, " . htmlspecialchars($firstName) . "!</h2>
            <p>Click the link below to verify your email address. The link expires in 24 hours.</p>
            <p><a href='{$verifyLink}'>{$verifyLink}</a></p>
            <p>If you did not create an account, ignore this email.</p>
        ";
        $mail->AltBody = "Verify your account: {$verifyLink}";
    
        $mail->send();
        echo "MAIL SENT";
        exit;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        exit;
    }

unset(
    $_SESSION['register_username'],
    $_SESSION['register_firstname'],
    $_SESSION['register_lastname'],
    $_SESSION['register_email']
);

$_SESSION['login_error'] = ($lang === 'de')
    ? 'Registrierung erfolgreich! Bitte prüfe deine E-Mail und klicke auf den Bestätigungslink.'
    : 'Registration successful! Please check your email and click the verification link.';

header('Location: ../auth/login.php');
exit;
