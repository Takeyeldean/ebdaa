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
// email
$userId = $_SESSION['user']['id'];

// رفع الصورة للطلاب فقط
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
    $fileTmpPath = $_FILES['profile_image']['tmp_name'];
    $fileName = $_FILES['profile_image']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExt, $allowed)) {
        $newFileName = uniqid() . "." . $fileExt;
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

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
        $_SESSION['error'] = "نوع الملف غير مسموح به. استخدم jpg, png, gif فقط.";
    }
} else {
    $_SESSION['error'] = "لم يتم اختيار أي ملف أو حدث خطأ.";
}

header("Location: " . url('profile'));
exit();
