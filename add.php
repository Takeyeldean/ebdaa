<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php';

// تأكد أن اللي فاتح Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
if ($group_id <= 0) die("❌ المجموعة غير موجودة");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // التحقق من الحقول الفارغة
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "⚠️ كل الحقول مطلوبة.";
        header("Location: manage_group.php?group_id=$group_id");
        exit;
    }

    // التحقق من تطابق كلمة المرور مع التأكيد
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "⚠️ كلمة المرور وتأكيدها غير متطابقين.";
        header("Location: manage_group.php?group_id=$group_id");
        exit;
    }

    try {
        // تحقق هل الإيميل موجود قبل كده
        $checkEmail = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $checkEmail->execute([$email]);

        if ($checkEmail->rowCount() > 0) {
            $_SESSION['error'] = "⚠️ البريد الإلكتروني مستخدم من قبل.";
            header("Location: manage_group.php?group_id=$group_id");
            exit;
        }

        // تحقق هل الاسم موجود قبل كده
        $checkName = $conn->prepare("SELECT id FROM students WHERE name = ?");
        $checkName->execute([$name]);

        if ($checkName->rowCount() > 0) {
            $_SESSION['error'] = "⚠️ الاسم مستخدم من قبل. اختر اسم آخر.";
            header("Location: manage_group.php?group_id=$group_id");
            exit;
        }

        // إضافة مستخدم جديد (role = student)
        $stmt = $conn->prepare("INSERT INTO students (name, email, password, group_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $group_id]);

        // تحديث عدد الطلاب في المجموعة +1
        $update = $conn->prepare("UPDATE groups SET numStudt = numStudt + 1 WHERE id = ?");
        $update->execute([$group_id]);

        $_SESSION['success'] = "✅ تم إضافة الطالب بنجاح";
        header("Location: manage_group.php?group_id=$group_id");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
        header("Location: manage_group.php?group_id=$group_id");
        exit;
    }
}
