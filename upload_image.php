<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php';
require_once 'includes/url_helper.php';

if (!isset($_SESSION['user'])) {
    header("Location: " . url('login'));
    exit();
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['error'] = "طلب غير صحيح. يرجى المحاولة مرة أخرى";
    header("Location: " . url('profile'));
    exit();
}
// email
$userId = $_SESSION['user']['id'];

// رفع الصورة للطلاب فقط
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
    $fileTmpPath = $_FILES['profile_image']['tmp_name'];
    $fileName = $_FILES['profile_image']['name'];
    $fileSize = $_FILES['profile_image']['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Security validations
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $uploadDir = 'uploads/';
    
    // Validate file size
    if ($fileSize > $maxFileSize) {
        $_SESSION['error'] = "حجم الملف كبير جداً. الحد الأقصى 5 ميجابايت";
        header("Location: " . url('profile'));
        exit();
    }
    
    // Validate file extension
    if (!in_array($fileExt, $allowed)) {
        $_SESSION['error'] = "نوع الملف غير مسموح به. استخدم jpg, png, gif فقط.";
        header("Location: " . url('profile'));
        exit();
    }
    
    // Validate file content (check MIME type)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $allowedMimes)) {
        $_SESSION['error'] = "نوع الملف غير صحيح. يرجى رفع صورة صحيحة.";
        header("Location: " . url('profile'));
        exit();
    }
    
    // Generate secure filename
    $newFileName = bin2hex(random_bytes(16)) . "." . $fileExt;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true); // More secure permissions
    }

    $destPath = $uploadDir . $newFileName;

        // جلب الصورة القديمة
        $stmt = $conn->prepare("SELECT profile_image FROM students WHERE id = ?");
        $stmt->execute([$userId]);
        $oldImage = $stmt->fetchColumn();

    // رفع الجديدة
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        // حذف القديمة لو موجودة وليست الصورة الافتراضية
        if ($oldImage && $oldImage !== "default.jpeg" && file_exists($uploadDir . $oldImage)) {
            unlink($uploadDir . $oldImage);
        }

        // تحديث قاعدة البيانات
        $stmt = $conn->prepare("UPDATE students SET profile_image = ? WHERE id = ?");
        $stmt->execute([$newFileName, $userId]);

        $_SESSION['success'] = "تم رفع الصورة بنجاح!";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء رفع الصورة.";
    }
} else {
    $_SESSION['error'] = "لم يتم اختيار أي ملف أو حدث خطأ.";
}

header("Location: " . url('profile'));
exit();
