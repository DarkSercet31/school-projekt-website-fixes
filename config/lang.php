<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Switch language via ?lang=en or ?lang=de
if (isset($_GET['lang'])) {
    $lang = ($_GET['lang'] === 'de') ? 'de' : 'en';
    $_SESSION['lang'] = $lang;
    // Redirect back to the same page without the query parameter
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect");
    exit;
}

$lang = $_SESSION['lang'];

$texts = [
    'en' => [
        'login_title'        => 'Login',
        'register_title'     => 'Register',
        'username'           => 'Username',
        'email'              => 'Email',
        'password'           => 'Password',
        'confirm_password'   => 'Confirm Password',
        'first_name'         => 'First Name',
        'last_name'          => 'Last Name',
        'login_btn'          => 'Login',
        'register_btn'       => 'Register',
        'verify_btn'         => 'Verify Code',
        'already_member'     => 'Already a member?',
        'not_member'         => 'Not a member?',
        'login_here'         => 'Login here',
        'register_here'      => 'Register here',
        'logout_btn'         => 'Logout',
        'user_error'         => 'Username or email already taken',
        'password_error'     => 'Passwords do not match',
        'user_not_found'     => 'Username or password incorrect',
        'not_verified'       => 'Please verify your email first',
        'verification_code'  => 'Verification Code',
        'enter_code'         => 'Enter the code sent to your email',
        'verify_success'     => 'Email verified! You can now login.',
    ],
    'de' => [
        'login_title'        => 'Anmelden',
        'register_title'     => 'Registrieren',
        'username'           => 'Benutzername',
        'email'              => 'Email',
        'password'           => 'Passwort',
        'confirm_password'   => 'Passwort bestätigen',
        'first_name'         => 'Vorname',
        'last_name'          => 'Nachname',
        'login_btn'          => 'Anmelden',
        'register_btn'       => 'Registrieren',
        'verify_btn'         => 'Code bestätigen',
        'already_member'     => 'Bereits Mitglied?',
        'not_member'         => 'Noch kein Mitglied?',
        'login_here'         => 'Hier anmelden',
        'register_here'      => 'Hier registrieren',
        'logout_btn'         => 'Abmelden',
        'user_error'         => 'Benutzername oder Email bereits vergeben',
        'password_error'     => 'Passwörter stimmen nicht überein',
        'user_not_found'     => 'Benutzername oder Passwort falsch',
        'not_verified'       => 'Bitte bestätigen Sie zunächst Ihre Email',
        'verification_code'  => 'Bestätigungscode',
        'enter_code'         => 'Geben Sie den Code ein, der an Ihre Email gesendet wurde',
        'verify_success'     => 'Email bestätigt! Sie können sich jetzt anmelden.',
    ],
];

// $t is a shorthand for the active language strings
$t = $texts[$lang];
