<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;

$info = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";
    } else {
        // Generate 4-digit OTP
        $otp = strval(random_int(1000, 9999));
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        // Upsert subscriber
        $pdo = db();
        $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))"); // safe noop
        $stmt = $pdo->prepare("
            INSERT INTO subscribers (email, otp_hash, verified)
            VALUES (?, ?, 0)
            ON DUPLICATE KEY UPDATE otp_hash = VALUES(otp_hash), verified = 0, verified_at = NULL
        ");
        $stmt->execute([$email, $otpHash]);

        // Send OTP via MailHog SMTP
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mailhog';
            $mail->Port = 1025;   // MailHog SMTP port
            $mail->SMTPAuth = false;

            $mail->setFrom('noreply@rtcomic.local', 'RTComic');
            $mail->addAddress($email);
            $mail->Subject = 'Your OTP for RTComic';
            $mail->isHTML(false);
            $mail->Body = "Your OTP is: {$otp}\n(This is a test email captured by MailHog)";

            $mail->send();
            $info = "OTP sent to your email. Open MailHog at http://localhost:8025 to view.";
        } catch (Throwable $e) {
            $error = "Failed to send OTP: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Subscribe - RTComic</title>
  <style>
    body { font-family: sans-serif; max-width: 560px; margin: 40px auto; }
    .msg { padding: 10px; border-radius: 6px; margin: 10px 0; }
    .ok { background: #e9f9ee; border: 1px solid #b7ebc6; }
    .err { background: #fdecea; border: 1px solid #f5c2c7; }
    label { display:block; margin:12px 0 4px; }
    input[type=email], input[type=text] { width:100%; padding:8px; }
    button { margin-top: 12px; padding: 8px 12px; cursor:pointer; }
    .card { border:1px solid #ddd; border-radius:10px; padding:20px; }
    .nav a { margin-right: 10px; }
  </style>
</head>
<body>
  <h1>RTComic – Subscribe</h1>
  <div class="nav">
    <a href="/index.php">Subscribe</a> |
    <a href="/verify.php">Verify OTP</a>
  </div>

  <?php if ($info): ?><div class="msg ok"><?= htmlspecialchars($info) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="post">
      <label>Email</label>
      <input type="email" name="email" required placeholder="you@example.com" />
      <button type="submit">Subscribe</button>
    </form>
    <p style="margin-top:12px;">Check emails at <strong><a href="http://localhost:8025" target="_blank">MailHog</a></strong>.</p>
  </div>
</body>
</html>