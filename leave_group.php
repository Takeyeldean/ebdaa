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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'])) {
    $group_id = intval($_POST['group_id']);
    $admin_id = $_SESSION['user']['id'];
    
    try {
        // Check if the admin is actually in this group
        $stmt = $conn->prepare("SELECT COUNT(*) FROM group_admins WHERE group_id = ? AND admin_id = ?");
        $stmt->execute([$group_id, $admin_id]);
        $is_admin = $stmt->fetchColumn();
        
        if (!$is_admin) {
            $_SESSION['error'] = "❌ لست مشرفاً في هذه المجموعة";
            header("Location: " . url('admin'));
            exit;
        }
        
        // Check if there are other admins in this group
        $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM group_admins WHERE group_id = ? AND admin_id != ?");
        $stmt->execute([$group_id, $admin_id]);
        $other_admins_count = $stmt->fetch()['admin_count'];
        
        // Get group name for logging
        $stmt = $conn->prepare("SELECT name FROM groups WHERE id = ?");
        $stmt->execute([$group_id]);
        $group_name = $stmt->fetch()['name'];
        
        if ($other_admins_count > 0) {
            // There are other admins, just remove this admin
            $stmt = $conn->prepare("DELETE FROM group_admins WHERE group_id = ? AND admin_id = ?");
            $stmt->execute([$group_id, $admin_id]);
            
            $_SESSION['success'] = "✅ تم مغادرة المجموعة '{$group_name}' بنجاح";
            error_log("Admin {$admin_id} left group {$group_id} ({$group_name}) - other admins remain");
        } else {
            // This is the last admin, delete the entire group and all related data
            $conn->beginTransaction();
            
            try {
                // Delete all notifications related to this group's questions
                $stmt = $conn->prepare("
                    DELETE n FROM notifications n 
                    JOIN questions q ON n.question_id = q.id 
                    WHERE q.group_id = ?
                ");
                $stmt->execute([$group_id]);
                
                // Delete all answers related to this group's questions
                $stmt = $conn->prepare("
                    DELETE a FROM answers a 
                    JOIN questions q ON a.question_id = q.id 
                    WHERE q.group_id = ?
                ");
                $stmt->execute([$group_id]);
                
                // Delete all questions for this group
                $stmt = $conn->prepare("DELETE FROM questions WHERE group_id = ?");
                $stmt->execute([$group_id]);
                
                // Delete all students in this group
                $stmt = $conn->prepare("DELETE FROM students WHERE group_id = ?");
                $stmt->execute([$group_id]);
                
                // Delete all group admin relationships
                $stmt = $conn->prepare("DELETE FROM group_admins WHERE group_id = ?");
                $stmt->execute([$group_id]);
                
                // Delete all admin invitations for this group
                $stmt = $conn->prepare("DELETE FROM admin_invitations WHERE group_id = ?");
                $stmt->execute([$group_id]);
                
                // Finally, delete the group itself
                $stmt = $conn->prepare("DELETE FROM groups WHERE id = ?");
                $stmt->execute([$group_id]);
                
                $conn->commit();
                
                $_SESSION['success'] = "⚠️ تم حذف المجموعة '{$group_name}' بالكامل مع جميع البيانات المرتبطة بها";
                error_log("Group {$group_id} ({$group_name}) completely deleted by admin {$admin_id} - last admin left");
                
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        }
        
    } catch (Exception $e) {
        error_log("Leave group error: " . $e->getMessage());
        $_SESSION['error'] = "❌ حدث خطأ أثناء مغادرة المجموعة. يرجى المحاولة مرة أخرى.";
    }
    
    header("Location: " . url('admin'));
    exit;
}

// If not POST request, redirect to admin page
header("Location: " . url('admin'));
exit;
?>
