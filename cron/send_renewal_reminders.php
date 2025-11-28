<?php
/**
 * Automated Renewal Reminder Script
 *
 * This script should be run automatically once per day (e.g., via Vercel Cron Jobs).
 * It finds business permits that are expiring soon and sends a reminder email to the applicant.
 */

// Set the script to run from the project root to make includes easier.
chdir(dirname(__DIR__));

require './db.php';
require_once './config_mail.php';
require_once './Staff-dashboard/email_functions.php';

// --- Configuration ---
$reminder_days = 30; // Send reminder for permits expiring in the next 30 days.

echo "Starting renewal reminder process...\n";

// --- 1. Find Expiring Permits ---
// Select applications that are:
// - 'complete' (meaning a permit was issued)
// - Have a renewal date set
// - Are expiring within the next $reminder_days
// - Have NOT had a reminder sent yet (renewal_notice_sent_at IS NULL)
$sql = "
    SELECT
        a.id, a.user_id, a.business_name, a.renewal_date,
        u.name as applicant_name, u.email as applicant_email
    FROM applications a
    JOIN users u ON a.user_id = u.id
    WHERE
        a.status = 'complete'
        AND a.renewal_date IS NOT NULL
        AND a.renewal_notice_sent_at IS NULL
        AND a.renewal_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '$reminder_days days'
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$expiring_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($expiring_applications) === 0) {
    echo "No expiring permits found that need a reminder. All done.\n";
    exit;
}

echo "Found " . count($expiring_applications) . " expiring permit(s) to notify.\n";

// --- 2. Process Each Expiring Application ---
foreach ($expiring_applications as $app) {
    $application_id = $app['id'];
    $business_name_safe = htmlspecialchars($app['business_name']);
    $renewal_date_formatted = (new DateTime($app['renewal_date']))->format('F j, Y');

    echo "Processing application #{$application_id} for '{$business_name_safe}'...\n";

    try {
        // --- 3. Send Email Notification ---
        $protocol = getenv('PROTOCOL') ?: 'https';
        $host = getenv('HOST') ?: 'yourdomain.com';
        $absolute_link = "{$protocol}://{$host}/Applicant-dashboard/view_my_application.php?id={$application_id}";

        $email_subject = "Action Required: Your Business Permit for '{$business_name_safe}' is Expiring Soon";

        $email_body = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                <h2 style='color: #d35400;'>Permit Renewal Reminder</h2>
                <p>Dear " . htmlspecialchars($app['applicant_name']) . ",</p>
                <p>This is a friendly reminder that your business permit for <strong>'{$business_name_safe}'</strong> is scheduled to expire on <strong>{$renewal_date_formatted}</strong>.</p>
                <p>To ensure your business remains compliant, please begin the renewal process at your earliest convenience. You can view your application and start the renewal by clicking the button below:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='" . htmlspecialchars($absolute_link) . "' style='background-color: #d35400; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Renew My Permit</a>
                </p>
                <p>If you have already renewed your permit, please disregard this notice.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 0.9em; color: #777;'>Thank you for your prompt attention to this matter.<br><strong>The OnlineBizPermit Team</strong></p>
            </div>
        </div>";

        sendApplicationEmail($app['applicant_email'], $app['applicant_name'], $email_subject, $email_body);

        // --- 4. Update Database to Mark as Sent ---
        $update_stmt = $conn->prepare("UPDATE applications SET renewal_notice_sent_at = NOW() WHERE id = ?");
        $update_stmt->execute([$application_id]);

        echo "  -> Reminder sent successfully for application #{$application_id}.\n";

    } catch (Exception $e) {
        // Log the error and continue to the next application
        $error_message = "Failed to send renewal reminder for application ID {$application_id}: " . $e->getMessage();
        echo "  -> ERROR: {$error_message}\n";
        error_log($error_message);
    }
}

echo "Renewal reminder process finished.\n";
?>
