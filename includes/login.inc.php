<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/db_connection.php';
require __DIR__ . '/../config/lang.php';
require __DIR__ . '/../config/mailtrap.php';

$lang = $_SESSION['lang'] ?? 'en';

if (!isset($_POST['login-submit'])) {
    header('Location: ../auth/login.php');
    exit;
}

$uid = trim($_POST['uid'] ?? '');
$pwd = trim($_POST['pwd'] ?? '');

if ($uid === '' || $pwd === '') {
    $_SESSION['login_error'] = ($lang === 'de')
        ? 'Bitte Benutzername und Passwort eingeben.'
        : 'Please enter username and password.';
    header('Location: ../auth/login.php');
    exit;
}

$sql  = "SELECT pk_username, password, role, firstName, lastName, email,
                mustChangePassword, status
         FROM user
         WHERE pk_username = ? OR email = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $uid, $uid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    $_SESSION['login_error'] = ($lang === 'de')
        ? 'Benutzer nicht gefunden.'
        : 'User not found.';
    header('Location: ../auth/login.php');
    exit;
}

if (!password_verify($pwd, $row['password'])) {
    $_SESSION['login_error'] = ($lang === 'de')
        ? 'Ungültiges Passwort.'
        : 'Invalid password.';
    header('Location: ../auth/login.php');
    exit;
}

if ($row['status'] !== 'verified') {
    $_SESSION['login_error'] = ($lang === 'de')
        ? 'Bitte bestätige zuerst deine E-Mail-Adresse.'
        : 'Please verify your email address first.';
    header('Location: ../auth/login.php');
    exit;
}

// Generate 6-digit OTP and store in email_tokens
$otp       = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
$username  = $row['pk_username'];

$pdo->prepare("DELETE FROM email_tokens WHERE fk_username = ? AND type = 'otp_login'")
    ->execute([$username]);
$pdo->prepare(
    "INSERT INTO email_tokens (token, fk_username, type, expires_at) VALUES (?, ?, 'otp_login', ?)"
)->execute([$otp, $username, $expiresAt]);

// Send OTP email
try {
    $mail = createMailer();
    $mail->addAddress($row['email'], trim($row['firstName'] . ' ' . $row['lastName']));
    $mail->Subject = 'Your login code — Weather Station';
    $mail->isHTML(true);
    $mail->Body    = "<h2>Login verification</h2>
        <p>Your one-time code is:</p>
        <h1 style='letter-spacing:8px;font-size:2.5rem;'><strong>{$otp}</strong></h1>
        <p>This code expires in 15 minutes. Do not share it.</p>";
    $mail->AltBody = "Your login code: {$otp}";
    $mail->send();
} catch (\Exception $e) {
    error_log('Mailtrap OTP error: ' . $e->getMessage());
}

// Store pending state — full session is set only after OTP is verified
$_SESSION['otp_pending_user']          = $username;
$_SESSION['otp_must_change_password']  = (bool)$row['mustChangePassword'];

header('Location: ../auth/verify_otp.php');
exit;
