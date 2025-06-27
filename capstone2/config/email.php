<?php
/**
 * Email Configuration
 * 
 * This file contains the email settings for the application.
 * Using Gmail SMTP with OAuth2 authentication.
 */

// Email Configuration Constants
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tmc.brix.sese@cvsu.edu.ph');
define('SMTP_PASSWORD', 'zvpz csnq rsfd bduv');
define('SMTP_FROM_EMAIL', 'tmc.brix.sese@cvsu.edu.ph');
define('SMTP_FROM_NAME', 'Barangay Biga Management System');
define('SMTP_SECURE', 'tls'); // Use TLS encryption
define('SMTP_AUTH', true);    // Enable SMTP authentication

// Email Templates
define('EMAIL_HEADER', '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .status-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Barangay Biga Management System</h2>
    </div>
    <div class="content">
');

define('EMAIL_FOOTER', '
    </div>
    <div class="footer">
        <p>This is an automated message from Barangay Biga Management System.<br>
        Please do not reply to this email.</p>
        <p>&copy; ' . date('Y') . ' Barangay Biga. All rights reserved.</p>
    </div>
</body>
</html>
');

// Email Template Functions
function get_email_template($type, $data = []) {
    $subject = '';
    $body = '';
    
    switch ($type) {
        case 'registration_confirmation':
            $subject = 'Welcome to Barangay Biga Management System';
            $body = EMAIL_HEADER . '
                <h3>Registration Confirmation</h3>
                <p>Dear ' . htmlspecialchars($data['first_name']) . ' ' . htmlspecialchars($data['last_name']) . ',</p>
                <p>Thank you for registering with the Barangay Biga Management System. Your account has been successfully created.</p>
                <div class="status status-success">
                    <strong>Account Details:</strong><br>
                    Username: ' . htmlspecialchars($data['username']) . '<br>
                    Email: ' . htmlspecialchars($data['email']) . '<br>
                    Role: ' . ucfirst(htmlspecialchars($data['role'])) . '
                    ' . (isset($data['password']) && !empty($data['password']) ? '<br>Password: <strong>' . htmlspecialchars($data['password']) . '</strong>' : '') . '
                </div>
                <p>You can now log in to your account using your username and password.</p>
                <p>If you did not create this account, please contact the barangay office immediately.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'appointment_created':
            $subject = 'Appointment Request Confirmation';
            $body = EMAIL_HEADER . '
                <h3>Appointment Request Received</h3>
                <p>Dear ' . htmlspecialchars($data['resident_name']) . ',</p>
                <p>Your appointment request has been received and is currently pending approval.</p>
                <div class="status status-warning">
                    <strong>Appointment Details:</strong><br>
                    Type: ' . htmlspecialchars($data['appointment_type']) . '<br>
                    Date: ' . htmlspecialchars($data['appointment_date']) . '<br>
                    Time: ' . htmlspecialchars($data['appointment_time']) . '<br>
                    Purpose: ' . htmlspecialchars($data['purpose']) . '
                </div>
                <p>We will notify you once your appointment is approved or if any changes are needed.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'appointment_approved':
            $subject = 'Appointment Approved';
            $body = EMAIL_HEADER . '
                <h3>Appointment Approved</h3>
                <p>Dear ' . htmlspecialchars($data['resident_name']) . ',</p>
                <p>Your appointment request has been approved.</p>
                <div class="status status-success">
                    <strong>Appointment Details:</strong><br>
                    Type: ' . htmlspecialchars($data['appointment_type']) . '<br>
                    Date: ' . htmlspecialchars($data['appointment_date']) . '<br>
                    Time: ' . htmlspecialchars($data['appointment_time']) . '<br>
                    Purpose: ' . htmlspecialchars($data['purpose']) . '
                </div>
                <p>Please arrive 10 minutes before your scheduled time.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'appointment_rejected':
            $subject = 'Appointment Request Update';
            $body = EMAIL_HEADER . '
                <h3>Appointment Request Update</h3>
                <p>Dear ' . htmlspecialchars($data['resident_name']) . ',</p>
                <p>We regret to inform you that your appointment request could not be approved at this time.</p>
                <div class="status status-danger">
                    <strong>Appointment Details:</strong><br>
                    Type: ' . htmlspecialchars($data['appointment_type']) . '<br>
                    Date: ' . htmlspecialchars($data['appointment_date']) . '<br>
                    Time: ' . htmlspecialchars($data['appointment_time']) . '<br>
                    Purpose: ' . htmlspecialchars($data['purpose']) . '
                </div>
                <p>Reason: ' . htmlspecialchars($data['reason']) . '</p>
                <p>Please submit a new appointment request with a different date or time.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'document_request_created':
            $subject = 'Document Request Confirmation';
            $body = EMAIL_HEADER . '
                <h3>Document Request Received</h3>
                <p>Dear ' . htmlspecialchars($data['resident_name']) . ',</p>
                <p>Your document request has been received and is currently being processed.</p>
                <div class="status status-warning">
                    <strong>Request Details:</strong><br>
                    Document Type: ' . htmlspecialchars($data['document_type']) . '<br>
                    Purpose: ' . htmlspecialchars($data['purpose']) . '<br>
                    Cost: ₱' . number_format($data['cost'], 2) . '<br>
                    Pickup Instructions: ' . htmlspecialchars($data['pickup_instructions']) . '
                </div>
                <p>We will notify you once your document is ready for pickup.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'document_ready':
            $subject = 'Document Ready for Pickup';
            $body = EMAIL_HEADER . '
                <h3>Document Ready for Pickup</h3>
                <p>Dear ' . htmlspecialchars($data['resident_name']) . ',</p>
                <p>Your requested document is now ready for pickup.</p>
                <div class="status status-success">
                    <strong>Document Details:</strong><br>
                    Document Type: ' . htmlspecialchars($data['document_type']) . '<br>
                    Purpose: ' . htmlspecialchars($data['purpose']) . '<br>
                    Cost: ₱' . number_format($data['cost'], 2) . '<br>
                    Pickup Instructions: ' . htmlspecialchars($data['pickup_instructions']) . '
                </div>
                <p>Please bring a valid ID when claiming your document.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'healthcare_record_created':
            $subject = 'Healthcare Record Created';
            $body = EMAIL_HEADER . '
                <h3>Healthcare Record Created</h3>
                <p>Dear ' . htmlspecialchars($data['resident_name']) . ',</p>
                <p>A new healthcare record has been created for you.</p>
                <div class="status status-info">
                    <strong>Record Details:</strong><br>
                    Type: ' . htmlspecialchars($data['record_type']) . '<br>
                    Date: ' . htmlspecialchars($data['date']) . '<br>
                    Description: ' . htmlspecialchars($data['description']) . '<br>
                    Status: ' . htmlspecialchars($data['status']) . '
                </div>
                <p>You can view the complete details by logging into your account.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'healthcare_record_updated':
            $subject = 'Healthcare Record Updated';
            $body = EMAIL_HEADER . '
                <h3>Healthcare Record Updated</h3>
                <p>Dear ' . htmlspecialchars($data['resident_name']) . ',</p>
                <p>Your healthcare record has been updated.</p>
                <div class="status status-info">
                    <strong>Record Details:</strong><br>
                    Type: ' . htmlspecialchars($data['record_type']) . '<br>
                    Date: ' . htmlspecialchars($data['date']) . '<br>
                    Description: ' . htmlspecialchars($data['description']) . '<br>
                    New Status: ' . htmlspecialchars($data['status']) . '
                </div>
                <p>You can view the complete details by logging into your account.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'password_reset_code':
            $subject = 'Your Password Reset Code';
            $body = EMAIL_HEADER . '
                <h3>Password Reset Request</h3>
                <p>Dear ' . htmlspecialchars($data['username']) . ',</p>
                <p>We received a request to reset your password for your Barangay Biga MIS account.</p>
                <div class="status status-warning">
                    <strong>Your 6-digit reset code:</strong><br>
                    <span style="font-size:2em;letter-spacing:8px;font-weight:bold;">' . htmlspecialchars($data['reset_code']) . '</span>
                </div>
                <p>This code will expire in 15 minutes. If you did not request a password reset, you can safely ignore this email.</p>
            ' . EMAIL_FOOTER;
            break;
            
        case 'password_reset_notification':
            $subject = 'Your Password Has Been Reset';
            $body = EMAIL_HEADER . '
                <h3>Password Reset Successful</h3>
                <p>Dear ' . htmlspecialchars($data['username']) . ',</p>
                <p>Your password for your Barangay Biga MIS account has been successfully reset.</p>
                <div class="status status-success">
                    <strong>If you did not perform this action, please contact the barangay office immediately.</strong>
                </div>
                <p>You can now log in using your new password.</p>
            ' . EMAIL_FOOTER;
            break;
    }
    
    return [
        'subject' => $subject,
        'body' => $body
    ];
}

// Email Sending Function
function send_email($to, $type, $data = []) {
    require_once __DIR__ . '/../src/PHPMailer.php';
    require_once __DIR__ . '/../src/SMTP.php';
    require_once __DIR__ . '/../src/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $template = get_email_template($type, $data);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $template['subject'];
        $mail->Body = $template['body'];
        $mail->AltBody = strip_tags($template['body']);
        
        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
} 