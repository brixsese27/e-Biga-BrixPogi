<?php
/**
 * Helper Functions
 * 
 * This file contains common utility functions used throughout the system
 */

// Include email configuration
require_once __DIR__ . '/../config/email.php';

// Function to format date
function format_date($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

// Function to format time
function format_time($time, $format = 'g:i A') {
    return date($format, strtotime($time));
}

// Function to format datetime
function format_datetime($datetime, $format = 'F j, Y g:i A') {
    return date($format, strtotime($datetime));
}

// Function to format currency
function format_currency($amount) {
    return '₱' . number_format($amount, 2);
}

// Function to get status badge HTML
function get_status_badge($status) {
    $status_lower = strtolower($status);
    $badge_class = 'bg-secondary'; // Default
    switch ($status_lower) {
        case 'pending':
            $badge_class = 'bg-warning text-dark';
            break;
        case 'approved':
            $badge_class = 'bg-success';
            break;
        case 'declined':
        case 'cancelled':
        case 'rejected':
            $badge_class = 'bg-danger';
            break;
        case 'completed': // For appointments
        case 'claimed': // For documents
            $badge_class = 'bg-primary text-white';
            break;
        case 'ready for pickup':
             $badge_class = 'bg-info text-dark';
            break;
        case 'processing': // Keep for backward compatibility if needed
            $badge_class = 'bg-primary';
            break;
    }
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

// Function to validate date
function is_valid_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Function to validate time
function is_valid_time($time) {
    // Accept both 'H:i' and 'H:i:s' formats
    $t1 = DateTime::createFromFormat('H:i', $time);
    if ($t1 && $t1->format('H:i') === $time) {
        return true;
    }
    $t2 = DateTime::createFromFormat('H:i:s', $time);
    return $t2 && $t2->format('H:i:s') === $time;
}

// Function to generate random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// Function to validate email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number
function is_valid_phone($phone) {
    return preg_match('/^[0-9]{11}$/', $phone);
}

// Function to get age from birthdate
function get_age($birthdate) {
    $today = new DateTime();
    $birth = new DateTime($birthdate);
    $age = $today->diff($birth);
    return $age->y;
}

// F  2unction to check if user is senior citizen (60 years or older)
function is_senior_citizen_age($birthdate) {
    return get_age($birthdate) >= 60;
}

// Function to format file size
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Function to validate file upload
function validate_file_upload($file, $allowed_types = ['jpg', 'jpeg', 'png'], $max_size = 5242880) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file parameter';
        return $errors;
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'File is too large';
            break;
        case UPLOAD_ERR_PARTIAL:
            $errors[] = 'File was only partially uploaded';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'No file was uploaded';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $errors[] = 'Missing temporary folder';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $errors[] = 'Failed to write file to disk';
            break;
        case UPLOAD_ERR_EXTENSION:
            $errors[] = 'A PHP extension stopped the file upload';
            break;
        default:
            $errors[] = 'Unknown upload error';
            break;
    }
    
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds limit of ' . format_file_size($max_size);
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_type = $finfo->file($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_types)) {
        $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types);
    }
    
    return $errors;
}

// Function to handle file upload
function handle_file_upload($file, $destination, $allowed_types = ['jpg', 'jpeg', 'png'], $max_size = 5242880) {
    $errors = validate_file_upload($file, $allowed_types, $max_size);
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    $filename = generate_random_string() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filepath = $destination . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'errors' => ['Failed to move uploaded file']];
    }
    
    return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
}

// Function to get document type options
function get_document_types() {
    return [
        'Barangay Residency',
        'Barangay Certificate of Singleness',
        'Barangay Certificate of Cohabitation',
        'Barangay Certificate of Solo Parent',
        'Barangay Certificate of Indigency for Financial and Medical Assistance',
        'Barangay Certificate of Indigency for Scholarship',
        'Barangay Certificate of Indigency for Burial',
        'Issuance of Barangay Certificate of No Objection',
        'Issuance of Barangay Business Clearance',
        'Issuance of Barangay Business Cessation',
        'Filing of Katarungang Pambarangay Case (KP Form 7 - Sumbong)',
        'Complainant of Katarungang Pambarangay Case',
        'Issuance of Barangay Protection Order'
    ];
}

// Function to get appointment type options
function get_appointment_types() {
    return [
        'official' => 'Meeting with Official'
    ];
}

// Function to get announcement type options
function get_announcement_types() {
    return [
        'health' => 'Health Announcement',
        'general' => 'General Announcement',
        'event' => 'Event Announcement'
    ];
}

// Function to truncate text
function truncate_text($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

// Function to generate pagination links
function generate_pagination($current_page, $total_pages, $url_pattern) {
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $prev_disabled = $current_page <= 1 ? 'disabled' : '';
    $html .= "<li class='page-item {$prev_disabled}'>";
    $html .= "<a class='page-link' href='" . sprintf($url_pattern, $current_page - 1) . "' aria-label='Previous'>";
    $html .= "<span aria-hidden='true'>&laquo;</span></a></li>";
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= "<li class='page-item'><a class='page-link' href='" . sprintf($url_pattern, 1) . "'>1</a></li>";
        if ($start > 2) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $current_page ? 'active' : '';
        $html .= "<li class='page-item {$active}'>";
        $html .= "<a class='page-link' href='" . sprintf($url_pattern, $i) . "'>{$i}</a></li>";
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        $html .= "<li class='page-item'><a class='page-link' href='" . sprintf($url_pattern, $total_pages) . "'>{$total_pages}</a></li>";
    }
    
    // Next button
    $next_disabled = $current_page >= $total_pages ? 'disabled' : '';
    $html .= "<li class='page-item {$next_disabled}'>";
    $html .= "<a class='page-link' href='" . sprintf($url_pattern, $current_page + 1) . "' aria-label='Next'>";
    $html .= "<span aria-hidden='true'>&raquo;</span></a></li>";
    
    $html .= '</ul></nav>';
    
    return $html;
}

// Function to send SMS (mock implementation)
function send_sms($to, $message) {
    // In a real implementation, this would use an SMS gateway
    // For now, we'll just log the SMS
    error_log("SMS to: {$to}");
    error_log("Message: {$message}");
    return true;
}

// Function to get human-readable time ago
function time_ago($datetime, $full = false) {
    $timezone = new DateTimeZone('Asia/Manila');
    $now = new DateTime('now', $timezone);
    // If the datetime from DB is in UTC, convert it to Asia/Manila
    $ago = new DateTime($datetime);
    if ($ago->getTimezone()->getName() !== 'Asia/Manila') {
        $ago->setTimezone($timezone);
    }
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function format_document_type($type) {
    return ucwords(str_replace('_', ' ', htmlspecialchars($type)));
}

function calculate_document_cost($document_type, $is_senior_citizen = false) {
    $costs = [
        'barangay_clearance' => 50.00,
        'indigency_certificate' => 0.00,
        'residency_certificate' => 75.00,
        'business_permit' => 500.00,
        'certificate_of_good_moral' => 100.00,
        'other' => 20.00,
    ];

    $cost = $costs[$document_type] ?? 0.00;

    if ($is_senior_citizen) {
        // 20% discount for senior citizens
        $cost *= 0.80;
    }

    return $cost;
} 