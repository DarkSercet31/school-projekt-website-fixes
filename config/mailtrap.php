<?php
// Mailtrap SMTP configuration for email testing
// Sign up free at https://mailtrap.io and paste your credentials below

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'sandbox.smtp.mailtrap.io'; // Mailtrap SMTP host
    $mail->SMTPAuth   = true;
    $mail->Username   = 'YOUR_MAILTRAP_USERNAME';   // <-- Replace this
    $mail->Password   = 'YOUR_MAILTRAP_PASSWORD';   // <-- Replace this
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 2525;

    $mail->setFrom('noreply@weatherstation.local', 'Weather Station');
    $mail->CharSet = 'UTF-8';

    return $mail;
}
