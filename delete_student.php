<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error'] = "❌ غير مسموح لك بالدخول";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    $group_id = intval($_POST['group_id']);
    $admin_id = $_SESSION['user']['id'];
    
    try {
        // Check if the admin has permission to manage this group
        $stmt = $conn->prepare("SELECT COUNT(*) FROM group_admins WHERE group_id = ? AND admin_id = ?");
        $stmt->execute([$group_id, $admin_id]);
        $can_manage = $stmt->fetchColumn();
        
        if (!$can_manage) {
            $_SESSION['error'] = "❌ ليس لديك صلاحية لإدارة هذه المجموعة";
            header("Location: admin.php");
            exit;
        }
        
        // Check if student exists and is in this group
        $stmt = $conn->prepare("SELECT name FROM students WHERE id = ? AND group_id = ?");
        $stmt->execute([$student_id, $group_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            $_SESSION['error'] = "❌ الطالب غير موجود في هذه المجموعة";
            header("Location: manage_group.php?group_id=" . $group_id);
            exit;
        }
        
        // Begin transaction for data deletion
        $conn->beginTransaction();
        
        try {
            // Delete all notifications related to this student's answers
            $stmt = $conn->prepare("
                DELETE n FROM notifications n 
                JOIN answers a ON n.question_id = a.question_id 
                WHERE a.student_id = ?
            ");
            $stmt->execute([$student_id]);
            
            // Delete all answers by this student
            $stmt = $conn->prepare("DELETE FROM answers WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            // Delete the student account
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            
            // Decrease numStudt count for the group
            $stmt = $conn->prepare("UPDATE groups SET numStudt = numStudt - 1 WHERE id = ?");
            $stmt->execute([$group_id]);
            
            $conn->commit();
            
            $_SESSION['success'] = "⚠️ تم حذف الطالب '{$student['name']}' وجميع بياناته المرتبطة بنجاح";
            error_log("Student {$student_id} ({$student['name']}) completely deleted from group {$group_id} by admin {$admin_id}");
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Delete student error: " . $e->getMessage());
        $_SESSION['error'] = "❌ حدث خطأ أثناء حذف الطالب. يرجى المحاولة مرة أخرى.";
    }
    
    header("Location: manage_group.php?group_id=" . $group_id);
    exit;
}

// If not POST request, redirect to admin page
header("Location: admin.php");
exit;
?>
