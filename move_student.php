<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";
require_once "includes/url_helper.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error'] = "❌ غير مسموح لك بالدخول";
    header("Location: " . url('login'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id']) && isset($_POST['new_group_id'])) {
    $student_id = intval($_POST['student_id']);
    $new_group_id = intval($_POST['new_group_id']);
    $current_group_id = intval($_POST['current_group_id']);
    $admin_id = $_SESSION['user']['id'];
    
    try {
        // Check if the admin has permission to manage this student's current group
        $stmt = $conn->prepare("SELECT COUNT(*) FROM group_admins WHERE group_id = ? AND admin_id = ?");
        $stmt->execute([$current_group_id, $admin_id]);
        $can_manage_current = $stmt->fetchColumn();
        
        if (!$can_manage_current) {
            $_SESSION['error'] = "❌ ليس لديك صلاحية لإدارة هذه المجموعة";
            header("Location: " . url('admin'));
            exit;
        }
        
        
        
        // Check if student exists and is in the current group
        $stmt = $conn->prepare("SELECT name FROM students WHERE id = ? AND group_id = ?");
        $stmt->execute([$student_id, $current_group_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            $_SESSION['error'] = "❌ الطالب غير موجود في هذه المجموعة";
            header("Location: " . adminGroupUrl($current_group_id));
            exit;
        }
        
        // Check if trying to move to the same group
        if ($new_group_id == $current_group_id) {
            $_SESSION['error'] = "❌ لا يمكن نقل الطالب إلى نفس المجموعة";
            header("Location: " . adminGroupUrl($current_group_id));
            exit;
        }
        
        // Check if target group exists
        $stmt = $conn->prepare("SELECT name FROM groups WHERE id = ?");
        $stmt->execute([$new_group_id]);
        $target_group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target_group) {
            $_SESSION['error'] = "❌ المجموعة الهدف غير موجودة";
            header("Location: " . adminGroupUrl($current_group_id));
            exit;
        }
        
        // Check if there's a name conflict in the target group
        $stmt = $conn->prepare("SELECT id FROM students WHERE name = ? AND group_id = ?");
        $stmt->execute([$student['name'], $new_group_id]);
        $name_conflict = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($name_conflict) {
            // There's a name conflict, redirect to name change page
            $_SESSION['move_student_data'] = [
                'student_id' => $student_id,
                'student_name' => $student['name'],
                'current_group_id' => $current_group_id,
                'new_group_id' => $new_group_id,
                'target_group_name' => $target_group['name']
            ];
            header("Location: " . studentActionUrl($current_group_id, $student_id, 'rename'));
            exit;
        }
        
        // Move the student to the new group
        $stmt = $conn->prepare("UPDATE students SET group_id = ? WHERE id = ?");
        $stmt->execute([$new_group_id, $student_id]);
        
        // Update numStudt for both groups
        // Decrease count for current group
        $stmt = $conn->prepare("UPDATE groups SET numStudt = numStudt - 1 WHERE id = ?");
        $stmt->execute([$current_group_id]);
        
        // Increase count for target group
        $stmt = $conn->prepare("UPDATE groups SET numStudt = numStudt + 1 WHERE id = ?");
        $stmt->execute([$new_group_id]);
        
        $_SESSION['success'] = "✅ تم نقل الطالب '{$student['name']}' إلى مجموعة '{$target_group['name']}' بنجاح";
        error_log("Student {$student_id} ({$student['name']}) moved from group {$current_group_id} to group {$new_group_id} by admin {$admin_id}");
        
    } catch (Exception $e) {
        error_log("Move student error: " . $e->getMessage());
        $_SESSION['error'] = "❌ حدث خطأ أثناء نقل الطالب. يرجى المحاولة مرة أخرى.";
    }
    
    header("Location: " . adminGroupUrl($current_group_id));
    exit;
}

// If not POST request, redirect to admin page
header("Location: " . url('admin'));
exit;
?>
