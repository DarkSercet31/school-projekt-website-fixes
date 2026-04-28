<?php
if (!defined('HOST')) {
    define("HOST", "localhost");
}
if (!defined('DB_USER')) {
    define("DB_USER", "");
}
if (!defined('DB_PASSWORD')) {
    define("DB_PASSWORD", "");
}
if (!defined('DB_NAME')) {
    define("DB_NAME", "weather_station_db");
}

$link = @mysqli_connect(HOST, DB_USER, DB_PASSWORD, DB_NAME);

if (!$link) {
    die('Database connection failed (' . mysqli_connect_errno() . '): ' . mysqli_connect_error());
}


mysqli_set_charset($link, 'utf8mb4');

// Language array for German/English
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';

$texts = [
    'en' => [
        'login_title' => 'Login',
        'register_title' => 'Register',
        'username' => 'Username',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'login_btn' => 'Login',
        'register_btn' => 'Register',
        'verify_btn' => 'Verify Code',
        'already_member' => 'Already a member?',
        'not_member' => 'Not a member?',
        'login_here' => 'Login here',
        'register_here' => 'Register here',
        'logout_btn' => 'Logout',
        'user_error' => 'Username or email already taken',
        'password_error' => 'Passwords do not match',
        'login_success' => 'Login successful!',
        'register_success' => 'Registration successful! Verify your email.',
        'verify_success' => 'Email verified! You can now login.',
        'verify_error' => 'Invalid verification code',
        'user_not_found' => 'Username or password incorrect',
        'not_verified' => 'Please verify your email first',
        'logout_success' => 'You have been logged out',
        'verification_code' => 'Verification Code',
        'enter_code' => 'Enter the code sent to your email',
        'code_sent' => 'Your verification code is',
        'forgot_password' => 'Forgot password?',
        'password_requirements' => 'Password must contain at least 8 characters, uppercase, lowercase, number, and special character',
    ],
    'de' => [
        'login_title' => 'Anmelden',
        'register_title' => 'Registrieren',
        'username' => 'Benutzername',
        'email' => 'Email',
        'password' => 'Passwort',
        'confirm_password' => 'Passwort bestätigen',
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'login_btn' => 'Anmelden',
        'register_btn' => 'Registrieren',
        'verify_btn' => 'Code bestätigen',
        'already_member' => 'Bereits Mitglied?',
        'not_member' => 'Noch nicht Mitglied?',
        'login_here' => 'Hier anmelden',
        'register_here' => 'Hier registrieren',
        'logout_btn' => 'Abmelden',
        'user_error' => 'Benutzername oder Email bereits vergeben',
        'password_error' => 'Passwörter stimmen nicht überein',
        'login_success' => 'Anmeldung erfolgreich!',
        'register_success' => 'Registrierung erfolgreich! Bestätigen Sie Ihre Email.',
        'verify_success' => 'Email bestätigt! Sie können sich jetzt anmelden.',
        'verify_error' => 'Ungültiger Bestätigungscode',
        'user_not_found' => 'Benutzername oder Passwort falsch',
        'not_verified' => 'Bitte bestätigen Sie zunächst Ihre Email',
        'logout_success' => 'Sie wurden abgemeldet',
        'verification_code' => 'Bestätigungscode',
        'enter_code' => 'Geben Sie den Code ein, der an Ihre Email gesendet wurde',
        'code_sent' => 'Ihr Bestätigungscode ist',
        'forgot_password' => 'Passwort vergessen?',
        'password_requirements' => 'Passwort muss mindestens 8 Zeichen, Großbuchstaben, Kleinbuchstaben, Zahlen und Sonderzeichen enthalten',
    ]
];

$t = $texts[$lang] ?? $texts['en'];
?>
