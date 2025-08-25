<?php
session_start(); 
error_reporting(E_ALL);
require_once 'includes/db.php';
// username
// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];
$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);

        // ✅ تحقق من التكرار
        if ($role == 'student') {
            // Check username across all students
            $stmt = $conn->prepare("SELECT id FROM students WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->rowCount() > 0) {
                $error = "⚠️ البريد الإلكتروني مستخدم من قبل.";
            } else {
                // Check name only inside the same group
                $stmt = $conn->prepare("SELECT id FROM students WHERE name = ? AND group_id = (SELECT group_id FROM students WHERE id = ?) AND id != ?");
                $stmt->execute([$name, $userId, $userId]);
                if ($stmt->rowCount() > 0) {
                    $error = "⚠️ الاسم مستخدم بالفعل داخل .";
                }
            }
        } else {
            // For admins: both name and username must be unique globally
            $stmt = $conn->prepare("SELECT id FROM admins WHERE (name = ? OR username = ?) AND id != ?");
            $stmt->execute([$name, $username, $userId]);
            if ($stmt->rowCount() > 0) {
                $error = "⚠️ الاسم أو البريد الإلكتروني مستخدم من قبل.";
            }
        }

        // ✅ لو مفيش خطأ يعمل التحديث
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

// ✅ لو فيه خطأ أو نجاح يفضل يعرضه في profile.php

if ($error) {
    $_SESSION['error'] = $error;
} elseif ($success) {
    $_SESSION['success'] = $success;
}


header("Location: profile.php");
exit;
