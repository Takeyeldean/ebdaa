<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php';
// username
// تأكد أن اللي فاتح Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
if ($group_id <= 0) die("❌ المجموعة غير موجودة");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // التحقق من الحقول الفارغة
    if (empty($name) || empty($username) || empty($password) || empty($confirm_password)) {
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

    // التحقق من صحة اسم المستخدم (إنجليزي فقط)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $_SESSION['error'] = "❌ اسم المستخدم يجب أن يحتوي على أحرف إنجليزية وأرقام فقط (مثال: ahmed123 أو student_01)";
        header("Location: manage_group.php?group_id=$group_id");
        exit;
    }

    try {
        // تحقق هل الإيميل موجود قبل كده
        $checkusername = $conn->prepare("SELECT id FROM students WHERE username = ?");
        $checkusername->execute([$username]);

        if ($checkusername->rowCount() > 0) {
            $_SESSION['error'] = "⚠️ البريد الإلكتروني مستخدم من قبل.";
            header("Location: manage_group.php?group_id=$group_id");
            exit;
        }

        // تحقق هل الاسم موجود في نفس المجموعة
        $checkName = $conn->prepare("SELECT id FROM students WHERE name = ? AND group_id = ?");
        $checkName->execute([$name, $group_id]);

        if ($checkName->rowCount() > 0) {
            $_SESSION['error'] = "⚠️ الاسم مستخدم من قبل في هذه المجموعة. اختر اسم آخر.";
            header("Location: manage_group.php?group_id=$group_id");
            exit;
        }

        // إضافة مستخدم جديد (role = student)
        $stmt = $conn->prepare("INSERT INTO students (name, username, password, group_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $username, $password, $group_id]);

        // تحديث عدد الطلاب في المجموعة +1
        $update = $conn->prepare("UPDATE groups SET numStudt = numStudt + 1 WHERE id = ?");
        $update->execute([$group_id]);

        $_SESSION['success'] = "✅ تم إضافة الطالب بنجاح";
        header("Location: manage_group.php?group_id=$group_id");
        exit;

    } catch (PDOException $e) {
        // Handle specific database errors with user-friendly messages
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'Username must contain only English letters and numbers') !== false) {
            $_SESSION['error'] = "❌ اسم المستخدم يجب أن يحتوي على أحرف إنجليزية وأرقام فقط (مثال: ahmed123 أو student_01)";
        } elseif (strpos($error_message, 'Duplicate entry') !== false) {
            $_SESSION['error'] = "❌ اسم المستخدم مستخدم من قبل. اختر اسم مستخدم آخر.";
        } else {
            $_SESSION['error'] = "❌ حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.";
        }
        
        header("Location: manage_group.php?group_id=$group_id");
        exit;
    }
}
