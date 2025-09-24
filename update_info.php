<?php
session_start(); 
error_reporting(E_ALL);
require_once 'includes/db.php';
require_once 'includes/url_helper.php';

// ✅ لو المستخدم مش مسجل دخول
if (!isset($_SESSION['user'])) {
    header("Location: " . url('login'));
    exit();
}

$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];
$success = $error = "";

// ✅ التحقق من المدخلات
function isEnglishUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $username);
}

// ✅ لو تم إرسال فورم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- تحديث البيانات الشخصية ---
    if (isset($_POST['update_info'])) {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);

        // تحقق أن اليوزرنيم إنجليزي فقط
        if (!isEnglishUsername($username)) {
            $error = "❌ اسم المستخدم يجب أن يحتوي على أحرف إنجليزية وأرقام فقط (مثال: ahmed123 أو student_01)";
        } else {
            if ($role == 'student') {
                // ✅ تحقق من username مكرر
                $stmt = $conn->prepare("SELECT id FROM students WHERE username = ? AND id != ?");
                $stmt->execute([$username, $userId]);
                if ($stmt->rowCount() > 0) {
                    $error = "⚠️ اسم المستخدم مستخدم بالفعل.";
                } else {
                    // ✅ تحقق من الاسم داخل نفس المجموعة
                    $stmt = $conn->prepare("SELECT id FROM students WHERE name = ? AND group_id = (SELECT group_id FROM students WHERE id = ?) AND id != ?");
                    $stmt->execute([$name, $userId, $userId]);
                    if ($stmt->rowCount() > 0) {
                        $error = "⚠️ الاسم مستخدم بالفعل داخل نفس المجموعة.";
                    }
                }
            } else {
                // ✅ الأدمن: الاسم و اليوزرنيم لازم يكونوا فريدين
                $stmt = $conn->prepare("SELECT id FROM admins WHERE (name = ? OR username = ?) AND id != ?");
                $stmt->execute([$name, $username, $userId]);
                if ($stmt->rowCount() > 0) {
                    $error = "⚠️ الاسم أو اسم المستخدم مستخدم من قبل.";
                }
            }
        }

        // ✅ لو مفيش أخطاء → تحديث
        if (empty($error)) {
            if($role == 'student')
                $stmt = $conn->prepare("UPDATE students SET name = ?, username = ? WHERE id = ?");
            else
                $stmt = $conn->prepare("UPDATE admins SET name = ?, username = ? WHERE id = ?");

            if ($stmt->execute([$name, $username, $userId])) {
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['username'] = $username;
                $success = "✅ تم تحديث المعلومات بنجاح!";
            } else {
                $error = "❌ حدث خطأ أثناء تحديث البيانات.";
            }
        }
    }

    // --- تحديث كلمة المرور ---
    if (isset($_POST['update_pass'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if($role == 'student')
            $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
        else 
            $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");

        $stmt->execute([$userId]);
        $currentPassword = $stmt->fetchColumn();

        // ⚠️ لو لسه بتخزن plain text
        if ($current !== $currentPassword) {
            $error = "❌ كلمة المرور الحالية غير صحيحة.";
        } elseif ($new !== $confirm) {
            $error = "⚠️ كلمة المرور الجديدة غير متطابقة.";
        } else {
            if($role == 'student')
                $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
            else
                $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");

            if ($stmt->execute([$new, $userId])) {
                $success = "✅ تم تحديث كلمة المرور بنجاح!";
            } else {
                $error = "❌ حدث خطأ أثناء تحديث كلمة المرور.";
            }
        }
    }
}

// ✅ إعادة التوجيه مع رسائل
if ($error) {
    $_SESSION['error'] = $error;
} elseif ($success) {
    $_SESSION['success'] = $success;
}
header("Location: " . url('profile'));
exit;
    