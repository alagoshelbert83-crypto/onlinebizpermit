<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config_mail.php';

/**
 * Sends an application-related email.
 *
 * @param string $to_email      Recipient's email address.
 * @param string $to_name       Recipient's name.
 * @param string $subject       The email subject.
 * @param string $body          The HTML email body.
 * @param array  $attachments   Optional attachments (each is an array with 'string', 'filename', 'type')
 * @return bool                 True on success.
 * @throws Exception            Throws PHPMailer exception on failure.
 */
function sendApplicationEmail(string $to_email, string $to_name, string $subject, string $body, array $attachments = []): bool {
    // Check if email sending is disabled in config
    if (!defined('MAIL_SMTP_ENABLED') || MAIL_SMTP_ENABLED !== true) {
        error_log("Email sending is disabled in config_mail.php. Email to {$to_email} was not sent.");
        return true; // Don't break the flow
    }

    $mail = new PHPMailer(true);

    try {
        // --- SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = MAIL_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_SMTP_USERNAME;
        $mail->Password   = MAIL_SMTP_PASSWORD;
        $mail->SMTPSecure = MAIL_SMTP_SECURE;
        $mail->Port       = MAIL_SMTP_PORT;

        // --- SENDER & RECIPIENT ---
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        // --- ATTACHMENTS (optional) ---
        foreach ($attachments as $attachment) {
            if (isset($attachment['string'], $attachment['filename'], $attachment['type'])) {
                $mail->addStringAttachment($attachment['string'], $attachment['filename'], 'base64', $attachment['type']);
            }
        }

        // --- EMAIL CONTENT ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        // --- SEND EMAIL ---
        if (!$mail->send()) {
            throw new Exception("Mailer Error: " . $mail->ErrorInfo);
        }

        return true;
    } catch (Exception $e) {
        error_log("Email sending failed to {$to_email}: " . $e->getMessage());
        echo "Email sending failed: " . $e->getMessage();
        return false;
    }
}
