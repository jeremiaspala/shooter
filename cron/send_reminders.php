<?php
/**
 * Cron Job - Send Reminders
 * Run this script every minute to check and send pending reminders
 * 
 * Usage: php /path/to/cron/send_reminders.php
 * Or add to crontab: * * * * * php /var/www/html/shooter/cron/send_reminders.php
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Function to send email (using PHP mail() as fallback)
function sendReminderEmail($to, $subject, $body, $smtpConfig) {
    // If SMTP config exists, use PHPMailer
    if ($smtpConfig) {
        return sendEmail($to, $subject, $body, $smtpConfig);
    }
    
    // Fallback to PHP mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Sistema de Recordatorios <noreply@localhost>\r\n";
    
    $recipients = is_array($to) ? implode(', ', $to) : $to;
    
    $result = @mail($recipients, $subject, $body, $headers);
    
    if ($result) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Failed to send email'];
    }
}

// Main execution
echo "[" . date('Y-m-d H:i:s') . "] Starting reminder check...\n";

$db = getDB();
$db->query("SET time_zone = '-03:00'");
$now = new DateTime();

// Get reminders due for execution
$reminders = $db->fetchAll("
    SELECT * FROM reminders 
    WHERE is_active = 1 
    AND next_execution <= NOW()
    ORDER BY next_execution ASC
");

if (empty($reminders)) {
    echo "[" . date('Y-m-d H:i:s') . "] No reminders to send.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Found " . count($reminders) . " reminders to process.\n";

// Get active SMTP config
$smtpConfig = getActiveSMTP();

if (!$smtpConfig) {
    echo "[" . date('Y-m-d H:i:s') . "] WARNING: No active SMTP configuration!\n";
}

foreach ($reminders as $reminder) {
    echo "Processing reminder ID: " . $reminder['id'] . " - " . $reminder['subject'] . "\n";
    
    // Get recipients
    $recipients = json_decode($reminder['recipients'], true);
    
    if (empty($recipients)) {
        echo "  - No recipients, skipping\n";
        continue;
    }
    
    // Send email
    $result = sendReminderEmail(
        $recipients,
        $reminder['subject'],
        $reminder['content'],
        $smtpConfig
    );
    
    // Log the execution
    $status = $result['success'] ? 'success' : 'failed';
    $errorMessage = $result['error'] ?? null;
    
    $db->query("
        INSERT INTO reminder_logs (reminder_id, status, error_message, recipients)
        VALUES (?, ?, ?, ?)
    ", [$reminder['id'], $status, $errorMessage, $reminder['recipients']]);
    
    if ($result['success']) {
        echo "  - Email sent successfully to: " . implode(', ', $recipients) . "\n";
        
        // Update execution count and next execution
        $newExecutionCount = $reminder['execution_count'] + 1;
        
        // Calculate next execution - pass the reminder with NEW execution count
        $reminderForCalc = $reminder;
        $reminderForCalc['execution_count'] = $newExecutionCount;
        $nextExecution = calculateNextExecution($reminderForCalc);
        
        if ($nextExecution) {
            $db->query("
                UPDATE reminders 
                SET execution_count = ?, next_execution = ?
                WHERE id = ?
            ", [$newExecutionCount, $nextExecution, $reminder['id']]);
            echo "  - Next execution scheduled: " . $nextExecution . "\n";
        } else {
            // No more executions (repeat ended)
            $db->query("
                UPDATE reminders 
                SET execution_count = ?, is_active = 0
                WHERE id = ?
            ", [$newExecutionCount, $reminder['id']]);
            echo "  - Reminder deactivated (repetition ended)\n";
        }
    } else {
        echo "  - ERROR: " . $result['error'] . "\n";
        
        // FAILED: Don't update next_execution or execution_count
        // The reminder will be retried on the next cron run
        echo "  - Will retry on next cron run\n";
        
        // Still log the failure but keep the reminder as-is for retry
        // Don't increment execution_count since it didn't actually execute
        // Don't update next_execution so it retries at the same time
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Reminder processing complete.\n";
exit(0);
