<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';

// ✅ تأكد ان المستخدم مسجل دخول وأدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

// ✅ تأكد ان في student_id مبعوت
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ لم يتم تحديد الطالب");
}

$studentId = (int)$_GET['id'];

// ✅ جلب بيانات الطالب (عشان نمسح صورته ونعرف group_id)
$stmt = $conn->prepare("SELECT profile_image, group_id FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("❌ الطالب غير موجود");
}

// ✅ حذف الطالب
$stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
$deleted = $stmt->execute([$studentId]);

if ($deleted) {
    // ✅ مسح الصورة من السيرفر (لو مش default.jpeg)
    if (!empty($student['profile_image']) && $student['profile_image'] !== 'default.jpeg') {
        $filePath = __DIR__ . '/../uploads/' . $student['profile_image'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // ✅ تقليل عدد الطلاب في الجدول groups
    if (!empty($student['group_id'])) {
        $stmt = $conn->prepare("UPDATE groups SET numstudt = numstudt - 1 WHERE id = ? AND numstudt > 0");
        $stmt->execute([$student['group_id']]);
    }

    $_SESSION['success'] = "✅ تم حذف الطالب وتحديث عدد الطلاب في الجروب";
} else {
    $_SESSION['error'] = "❌ حدث خطأ أثناء الحذف";
}

// ✅ رجوع للوحة التحكم
header("Location: db_manager.php");
exit;
