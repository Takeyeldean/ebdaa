<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';
// email
// ✅ تحقق أن المستخدم أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

// ✅ تحقق من ID المجموعة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ لم يتم تحديد المجموعة");
}

$groupId = (int)$_GET['id'];

// ✅ تحقق أن المجموعة موجودة
$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    $_SESSION['error'] = "❌ المجموعة غير موجودة";
    header("Location: db_manager.php");
    exit;
}

// ✅ تحقق إذا كان فيها طلاب
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE group_id = ?");
$stmt->execute([$groupId]);
$studentsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

if ($studentsCount > 0) {
    // لو عايز تمنع الحذف لو فيه طلاب
    $_SESSION['error'] = "❌ لا يمكن حذف المجموعة لأنها تحتوي على $studentsCount طالب";
    header("Location: db_manager.php");
    exit;

    // 🔹 أو ممكن تحذف الطلاب كمان (شيل الكود اللي فوق وحط الكود اللي تحت)
    /*
    $stmt = $conn->prepare("DELETE FROM students WHERE group_id = ?");
    $stmt->execute([$groupId]);
    */
}

// ✅ حذف المجموعة
$stmt = $conn->prepare("DELETE FROM groups WHERE id = ?");
$deleted = $stmt->execute([$groupId]);

if ($deleted) {
    $_SESSION['success'] = "✅ تم حذف المجموعة بنجاح";
} else {
    $_SESSION['error'] = "❌ حدث خطأ أثناء الحذف";
}

header("Location: db_manager.php");
exit;
