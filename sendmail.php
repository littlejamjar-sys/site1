<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/var/www/mailer/PHPMailer/src/Exception.php';
require '/var/www/mailer/PHPMailer/src/PHPMailer.php';
require '/var/www/mailer/PHPMailer/src/SMTP.php';

// Load SMTP credentials from outside the web root
require_once '/etc/phpapp/mail.conf.php';

header('Content-Type: application/json');

function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error"]);
    exit;
}

# Honeypot spam trap
if (!empty($_POST['company'])) {
    http_response_code(200);
    echo json_encode(["status" => "ok"]);
    exit;
}

$name = clean($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$message = clean($_POST['message'] ?? '');
$site = clean($_POST['site'] ?? 'security');

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(["status" => "invalid"]);
    exit;
}

# Determine which mailbox to send from
if ($site === "investigations") {

    $smtp_user = SMTP_USER_INVESTIGATIONS;
    $smtp_pass = SMTP_PASS_INVESTIGATIONS;
    $subject_prefix = "Investigation enquiry";

} else {

    $smtp_user = SMTP_USER_CONTACT;
    $smtp_pass = SMTP_PASS_CONTACT;
    $subject_prefix = "Security enquiry";

}

$subject = $subject_prefix . " – Northwatch website";

$html = "
<h2>New website enquiry</h2>

<p><strong>Name:</strong><br>$name</p>

<p><strong>Email:</strong><br>$email</p>

<p><strong>Message:</strong><br>" . nl2br($message) . "</p>

<hr>

<p style='font-size:12px;color:#666'>
Sent from the Northwatch website contact form.
</p>
";

$text = "
New website enquiry

Name:
$name

Email:
$email

Message:
$message

Sent from the Northwatch website contact form.
";

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_user;
    $mail->Password = $smtp_pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    # Helps SpamAssassin trust the message
    $mail->Hostname = 'northwatchgroup.com';
    $mail->Helo = 'northwatchgroup.com';

    $mail->setFrom($smtp_user, 'Northwatch Website');
    $mail->addAddress('shoredispatch@protonmail.com');

    $mail->addReplyTo($email, $name);

    $mail->Subject = $subject;

    $mail->isHTML(true);
    $mail->Body = $html;
    $mail->AltBody = $text;

    $mail->send();

    $mail->smtpClose();

    echo json_encode(["status" => "sent"]);

} catch (Exception $e) {

    error_log("Mail error: " . $mail->ErrorInfo);

    http_response_code(500);
    echo json_encode(["status" => "error"]);
    exit;
}
