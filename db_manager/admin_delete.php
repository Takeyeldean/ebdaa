<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';

// ✅ لازم يكون Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

// ✅ تحقق من ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ لم يتم تحديد الأدمن");
}

$adminId = (int)$_GET['id'];

// ✅ منع الأدمن من حذف نفسه
if ($adminId == $_SESSION['user']['id']) {
    $_SESSION['error'] = "❌ لا يمكنك حذف نفسك";
    header("Location: db_manager.php");
    exit;
}

// ✅ تحقق أن الأدمن موجود
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $_SESSION['error'] = "❌ الأدمن غير موجود";
    header("Location: db_manager.php");
    exit;
}

// ✅ تنفيذ الحذف
$stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
$deleted = $stmt->execute([$adminId]);

if ($deleted) {
    $_SESSION['success'] = "✅ تم حذف الأدمن بنجاح";
} else {
    $_SESSION['error'] = "❌ حدث خطأ أثناء الحذف";
}

header("Location: db_manager.php");
exit;
