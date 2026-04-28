<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../includes/db_connection.php';
require '../config/lang.php';
require '../config/mailtrap.php';
$lang = $_SESSION['lang'] ?? 'en';

// Already logged in → go to dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: ../user/dashboard.php');
    exit;
}

$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email !== '') {
        // Find user by email (don't reveal whether it exists)
        $stmt = $pdo->prepare("SELECT pk_username FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a secure reset token
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Remove any existing reset tokens for this user
            $pdo->prepare("DELETE FROM email_tokens WHERE fk_username = ? AND type = 'reset'")
                ->execute([$user['pk_username']]);

            // Insert new token
            $pdo->prepare(
                "INSERT INTO email_tokens (token, fk_username, type, expires_at)
                 VALUES (?, ?, 'reset', ?)"
            )->execute([$token, $user['pk_username'], $expiresAt]);

            // Send reset email
            try {
                $mail = createMailer();
                $mail->addAddress($email);
                $mail->Subject = 'Password Reset — Weather Station';
                $resetLink = 'https://10.246.45.135/auth/reset_password.php?token=' . $token;
                $mail->isHTML(true);
                $mail->Body = "
                    <h2>Password Reset</h2>
                    <p>Click the link below to reset your password. The link expires in 1 hour.</p>
                    <p><a href='{$resetLink}'>{$resetLink}</a></p>
                    <p>If you did not request this, ignore this email.</p>
                ";
                $mail->AltBody = "Password reset link: {$resetLink}";
                $mail->send();
            } catch (\Exception $e) {
                error_log('Mailtrap error: ' . $e->getMessage());
            }
        }
    }

    // Always show the same message to prevent user enumeration
    $info = ($lang === 'de')
        ? 'Falls diese E-Mail-Adresse existiert, wurde ein Reset-Link gesendet.'
        : 'If that email address exists, a reset link has been sent.';
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>" data-theme="dark">
<head>
    <meta charset="utf-8">
    <title><?php echo ($lang === 'de') ? 'Passwort vergessen' : 'Forgot Password'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/layout.css">
</head>
<body>
<div class="container main-shell" style="max-width:420px;">
    <div class="glass-card">
        <div class="glass-card-header">
            <div>
                <h1 class="glass-card-title mb-0">
                    <?php echo ($lang === 'de') ? 'Passwort zurücksetzen' : 'Reset Password'; ?>
                </h1>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Gib deine E-Mail-Adresse ein. Wir schicken dir einen Reset-Link.'
                        : 'Enter your email address and we will send you a reset link.'; ?>
                </p>
            </div>
        </div>

        <?php if ($info): ?>
            <div class="alert alert-info py-2 mb-3"><?php echo htmlspecialchars($info); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-4">
                <label class="form-label">E-Mail</label>
                <input type="email" name="email" class="form-control" required autocomplete="email">
            </div>
            <button type="submit" class="btn btn-primary-soft w-100">
                <?php echo ($lang === 'de') ? 'Link senden' : 'Send reset link'; ?>
            </button>
        </form>
        <div class="mt-3 text-center">
            <a href="login.php" class="small glass-card-sub">
                <?php echo ($lang === 'de') ? '← Zurück zum Login' : '← Back to login'; ?>
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
