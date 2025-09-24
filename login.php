<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);   
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once "includes/db.php"; // الاتصال بقاعدة البيانات (PDO)
require_once "includes/url_helper.php";
// username
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Rate limiting - prevent brute force attacks
    $ip = $_SERVER['REMOTE_ADDR'];
    $rate_limit_key = "login_attempts_" . $ip;
    $max_attempts = 5;
    $lockout_time = 300; // 5 minutes
    
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = ['count' => 0, 'last_attempt' => time()];
    }
    
    $attempts = $_SESSION[$rate_limit_key];
    
    // Reset attempts if lockout time has passed
    if (time() - $attempts['last_attempt'] > $lockout_time) {
        $_SESSION[$rate_limit_key] = ['count' => 0, 'last_attempt' => time()];
    }
    
    // Check if too many attempts
    if ($attempts['count'] >= $max_attempts) {
        $_SESSION['error'] = "تم تجاوز عدد المحاولات المسموح. يرجى المحاولة مرة أخرى بعد 5 دقائق";
        header("Location: " . url('login'));
        exit();
    }
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "طلب غير صحيح. يرجى المحاولة مرة أخرى";
        header("Location: " . url('login'));
        exit();
    }
    
    // Input validation and sanitization
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate inputs
    if (empty($role) || empty($username) || empty($password)) {
        $_SESSION['error'] = "يرجى ملء جميع الحقول المطلوبة";
        header("Location: " . url('login'));
        exit();
    }
    
    // Validate role
    if (!in_array($role, ['admin', 'student'])) {
        $_SESSION['error'] = "نوع المستخدم غير صحيح";
        header("Location: " . url('login'));
        exit();
    }
    
    // Sanitize username (alphanumeric and underscore only)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $_SESSION['error'] = "اسم المستخدم يحتوي على أحرف غير مسموحة";
        header("Location: " . url('login'));
        exit();
    }

    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username");
    } else {
        $stmt = $conn->prepare("SELECT * FROM students WHERE username = :username");
    }

    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user && $password === $user['password']) {

        $_SESSION['user'] = [
            "id" => $user['id'],
            "name" => $user['name'],
            "username" => $user['username'],
            "role" => $role     
        ];
         $redirect = ($_SESSION['user']['role'] === 'admin') ? url('admin') : url('dashboard');
    header("Location: " . $redirect);
        exit();
    } else {
        // Increment failed attempts
        $_SESSION[$rate_limit_key]['count']++;
        $_SESSION[$rate_limit_key]['last_attempt'] = time();
        
        // Provide specific error messages
        if (!$user) {
            $_SESSION['error'] = "اسم المستخدم غير موجود";
        } else {
            $_SESSION['error'] = "كلمة المرور غير صحيحة";
        }
        
        header("Location: " . url('login'));
        exit();
    }
}
?>
