<?php
declare(strict_types=1);

// Security: only allow CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use GuzzleHttp\Client;

$pdo = db();

// Get verified subscribers
$subs = $pdo->query("SELECT email FROM subscribers WHERE verified = 1")->fetchAll();
if (!$subs) {
    echo "[cron] No verified subscribers.\n";
    exit(0);
}

// Fetch latest XKCD
$client = new Client(['timeout' => 10]);
$res = $client->get('https://xkcd.com/info.0.json');
$data = json_decode((string)$res->getBody(), true);

$title = $data['safe_title'] ?? 'XKCD';
$img   = $data['img']        ?? '';
$alt   = htmlspecialchars($data['alt'] ?? '', ENT_QUOTES);

$htmlBody = "<h3>{$title}</h3><p><img src=\"{$img}\" alt=\"{$alt}\" /></p><p>— Sent by RTComic (local dev)</p>";

foreach ($subs as $s) {
    $email = $s['email'];

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'mailhog';
        $mail->Port = 1025;
        $mail->SMTPAuth = false;

        $mail->setFrom('noreply@rtcomic.local', 'RTComic');
        $mail->addAddress($email);
        $mail->Subject = 'Your Daily Comic';
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = "Your daily comic: {$img}";

        $mail->send();
        echo "[cron] Sent to {$email}\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "[cron] Failed for {$email}: {$e->getMessage()}\n");
    }
}
