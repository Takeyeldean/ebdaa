<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';

// ✅ تحقق أن المستخدم أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

// ✅ تحقق من البيانات
if (!isset($_GET['group_id']) || !isset($_GET['admin_id'])) {
    die("❌ بيانات غير مكتملة");
}

$groupId = (int)$_GET['group_id'];
$adminId = (int)$_GET['admin_id'];

// ✅ تنفيذ الحذف
$stmt = $conn->prepare("DELETE FROM group_admins WHERE group_id = ? AND admin_id = ?");
$stmt->execute([$groupId, $adminId]);

// ✅ رسالة نجاح
$_SESSION['success'] = "✅ تم إزالة الأدمن من المجموعة بنجاح";

// ✅ إعادة التوجيه للصفحة السابقة
header("Location: group_admin_edit.php?group_id=$groupId");
exit;
