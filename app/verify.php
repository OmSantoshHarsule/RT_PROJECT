<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

$info = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp   = trim($_POST['otp'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";
    } elseif (!preg_match('/^\d{4}$/', $otp)) {
        $error = "Invalid OTP format.";
    } else {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, otp_hash, verified FROM subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = "Email not found. Please subscribe first.";
        } elseif (!password_verify($otp, $row['otp_hash'])) {
            $error = "Incorrect OTP.";
        } else {
            if ((int)$row['verified'] === 1) {
                $info = "Already verified!";
            } else {
                $upd = $pdo->prepare("UPDATE subscribers SET verified = 1, verified_at = NOW() WHERE id = ?");
                $upd->execute([$row['id']]);
                $info = "Email verified successfully!";
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Verify - RTComic</title>
  <style>
    body { font-family: sans-serif; max-width: 560px; margin: 40px auto; }
    .msg { padding: 10px; border-radius: 6px; margin: 10px 0; }
    .ok { background: #e9f9ee; border: 1px solid #b7ebc6; }
    .err { background: #fdecea; border: 1px solid #f5c2c7; }
    label { display:block; margin:12px 0 4px; }
    input { width:100%; padding:8px; }
    button { margin-top: 12px; padding: 8px 12px; cursor:pointer; }
    .card { border:1px solid #ddd; border-radius:10px; padding:20px; }
    .nav a { margin-right: 10px; }
  </style>
</head>
<body>
  <h1>RTComic – Verify OTP</h1>
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
      <label>OTP</label>
      <input type="text" name="otp" required placeholder="4-digit code" />
      <button type="submit">Verify</button>
    </form>
  </div>
</body>
</html>
