<?php
// Get app name from config
function getAppName() {
    static $appName = null;
    if ($appName === null) {
        try {
            $db = getDB();
            $config = $db->fetchOne("SELECT config_value FROM app_config WHERE config_key = 'app_name'");
            $appName = $config ? $config['config_value'] : 'Shooter';
        } catch (Exception $e) {
            $appName = 'Shooter';
        }
    }
    return $appName;
}

// Check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login');
        exit;
    }
}

// Check if user has admin role
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        header('Location: dashboard');
        exit;
    }
}

// Check if user has visor role
function requireVisor() {
    requireLogin();
}

// Get current user info
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }
    return null;
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Send email with SMTP
function sendEmail($to, $subject, $body, $smtp_config) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_config['smtp_host'];
        $mail->Port = $smtp_config['smtp_port'];
        $mail->SMTPSecure = $smtp_config['smtp_encryption'] === 'ssl' ? 'ssl' : ($smtp_config['smtp_encryption'] === 'tls' ? 'tls' : '');
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_config['smtp_username'];
        $mail->Password = $smtp_config['smtp_password'];
        
        // Recipients
        $mail->setFrom($smtp_config['smtp_from_email'], $smtp_config['smtp_from_name']);
        
        // Handle multiple recipients
        $recipients = is_array($to) ? $to : [$to];
        foreach ($recipients as $recipient) {
            $mail->addAddress(trim($recipient));
        }
        
        // Process inline images (base64 to inline attachments)
        $body = processInlineImages($mail, $body);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// Process inline images - convert base64 to inline attachments
function processInlineImages($mail, $body) {
    // Find all base64 images in the HTML
    preg_match_all('/<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"[^>]*>/i', $body, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $mimeType = $match[1];
        $base64Data = $match[2];
        $imgTag = $match[0];
        
        // Decode the base64 data
        $imageData = base64_decode($base64Data);
        
        // Generate a unique CID
        $cid = md5($base64Data) . '@shooter.local';
        
        // Add the image as an inline attachment
        $mail->addStringEmbeddedImage($imageData, $cid, 'image.' . getExtensionFromMime($mimeType), 'base64', getMimeContentType($mimeType));
        
        // Replace the src with the CID reference
        $newSrc = 'cid:' . $cid;
        $body = str_replace($imgTag, '<img src="' . $newSrc . '">', $body);
    }
    
    return $body;
}

// Get file extension from MIME type
function getExtensionFromMime($mime) {
    $map = [
        'jpeg' => 'jpg',
        'jpg' => 'jpg',
        'png' => 'png',
        'gif' => 'gif',
        'webp' => 'webp',
        'svg+xml' => 'svg'
    ];
    return $map[$mime] ?? 'jpg';
}

// Get proper MIME content type
function getMimeContentType($mime) {
    return 'image/' . ($mime === 'svg+xml' ? 'svg+xml' : $mime);
}

// Get active SMTP config
function getActiveSMTP() {
    $db = getDB();
    return $db->fetchOne("SELECT * FROM smtp_config WHERE is_active = 1 LIMIT 1");
}

// Format datetime for display
function formatDateTime($datetime) {
    if (!$datetime) return '';
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i');
}

// Calculate next execution based on repeat settings
function calculateNextExecution($reminder) {
    $current = new DateTime($reminder['next_execution']);
    
    switch ($reminder['repeat_type']) {
        case 'none':
            return null;
            
        case 'daily':
            $current->modify('+1 day');
            break;
            
        case 'weekly':
            $days = json_decode($reminder['repeat_days'] ?? '[1,2,3,4,5]', true);
            do {
                $current->modify('+1 day');
                $dayOfWeek = (int)$current->format('N');
            } while (!in_array($dayOfWeek, $days));
            break;
            
        case 'monthly':
            $monthDays = json_decode($reminder['repeat_month_days'] ?? '[1]', true);
            $currentDay = (int)$current->format('d');
            
            do {
                $current->modify('+1 month');
                $currentDay = (int)$current->format('d');
            } while (!in_array($currentDay, $monthDays));
            break;
            
        case 'yearly':
            $current->modify('+1 year');
            break;
            
        case 'custom':
            $interval = (int)$reminder['repeat_interval'];
            $unit = $reminder['repeat_unit'];
            $current->modify("+{$interval} {$unit}");
            break;
    }
    
    // Check end conditions
    if ($reminder['repeat_end_type'] === 'after') {
        $maxCount = (int)$reminder['repeat_count'];
        if ($reminder['execution_count'] >= $maxCount) {
            return null;
        }
    } elseif ($reminder['repeat_end_type'] === 'on_date') {
        $endDate = new DateTime($reminder['end_date']);
        if ($current > $endDate) {
            return null;
        }
    }
    
    return $current->format('Y-m-d H:i:s');
}

// Show toast notification
function showToast($type, $message) {
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
}
