<?php
require_once __DIR__ . '/config_mail.php';
require_once __DIR__ . '/vendor/autoload.php'; // If you’re using PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = MAIL_SMTP_HOST;
$mail->SMTPAuth = true;
$mail->Username = MAIL_SMTP_USERNAME;
$mail->Password = MAIL_SMTP_PASSWORD;
$mail->SMTPSecure = MAIL_SMTP_SECURE;
$mail->Port = MAIL_SMTP_PORT;

$mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
$mail->addAddress('yourtestemail@gmail.com');
$mail->isHTML(true);
$mail->Subject = 'Test Email from OnlineBizPermit';
$mail->Body    = 'This is a test email using Brevo SMTP.';

try {
    $mail->send();
    echo "✅ Email sent successfully!";
} catch (Exception $e) {
    echo "❌ Error: " . $mail->ErrorInfo;
}
