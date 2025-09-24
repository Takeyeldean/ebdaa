<?php
/**
 * Security Configuration and Helper Functions
 * This file contains security-related functions and configurations
 */

// Security headers
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Strict Transport Security (HTTPS only)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self';");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Input sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate username format
function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

// Validate password strength
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
}

// Generate secure random token
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Rate limiting function
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $now = time();
    $rateLimit = $_SESSION['rate_limits'][$key] ?? ['count' => 0, 'reset_time' => $now + $timeWindow];
    
    // Reset if time window has passed
    if ($now > $rateLimit['reset_time']) {
        $rateLimit = ['count' => 0, 'reset_time' => $now + $timeWindow];
    }
    
    // Check if limit exceeded
    if ($rateLimit['count'] >= $maxAttempts) {
        return false;
    }
    
    // Increment counter
    $rateLimit['count']++;
    $_SESSION['rate_limits'][$key] = $rateLimit;
    
    return true;
}

// CSRF token validation
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// File upload security
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) {
    $errors = [];
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = "حجم الملف كبير جداً";
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        $errors[] = "نوع الملف غير مسموح";
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
        $errors[] = "نوع الملف غير صحيح";
    }
    
    return $errors;
}

// SQL injection prevention - ensure all queries use prepared statements
function logSecurityEvent($event, $details = '') {
    $logEntry = date('Y-m-d H:i:s') . " - " . $event . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    error_log($logEntry, 3, 'security.log');
}

// Session security
function secureSession() {
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Set secure session cookie parameters
    if (ini_get('session.cookie_httponly') !== '1') {
        ini_set('session.cookie_httponly', '1');
    }
    
    if (ini_get('session.cookie_secure') !== '1' && isset($_SERVER['HTTPS'])) {
        ini_set('session.cookie_secure', '1');
    }
    
    if (ini_get('session.use_strict_mode') !== '1') {
        ini_set('session.use_strict_mode', '1');
    }
}
?>
