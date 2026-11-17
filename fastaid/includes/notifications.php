<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp_config.php';
require_once __DIR__ . '/db.php';

// Simple helper to log notification attempt to database
function log_notification($user_id, $channel, $recipient, $subject, $body, $status = 'pending') {
    $db = DB::connect();
    $stmt = $db->prepare("INSERT INTO notifications_log (user_id, channel, recipient, subject, body, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $channel, $recipient, $subject, $body, $status]);
    return $db->lastInsertId();
}

// Placeholder for sending email (In real app, use PHPMailer or similar library)
function send_email($to, $subject, $message, $user_id) {
    // Log to DB first
    $log_id = log_notification($user_id, 'email', $to, $subject, $message);

    // NOTE: This is a placeholder. INTEGRATE PHPMailer HERE.
    // Example:
    // $mail = new PHPMailer();
    // Configure with SMTP_ vars...
    // $mail->send();

    // Mark as sent in DB
    $db = DB::connect();
    $db->prepare("UPDATE notifications_log SET status='sent' WHERE id=?")->execute([$log_id]);
    return true;
}
?>
