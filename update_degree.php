<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "includes/db.php";
// email
// جلب بيانات الطالب والدرجة
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;

if ($id > 0 && $amount != 0) {
    // تحديث الدرجة
    $stmt = $conn->prepare("UPDATE students SET degree = degree + ? WHERE id = ?");
    $stmt->execute([$amount, $id]);
}

// رجوع للصفحة السابقة
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
