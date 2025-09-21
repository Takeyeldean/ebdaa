<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['invite_error'] = "❌ غير مسموح لك بالدخول";
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_admin'])) {
    $group_id = intval($_POST['group_id']);
    $admin_username = trim($_POST['admin_username']);
    $inviter_admin_id = $_SESSION['user']['id'];
    
    // Validate input
    if (empty($admin_username)) {
        $_SESSION['invite_error'] = "❌ يرجى إدخال اسم المستخدم";
        header("Location: manage_group.php?group_id=" . $group_id);
        exit;
    }
    
    try {
        // Check if the invited admin exists
        $stmt = $conn->prepare("SELECT id, name FROM admins WHERE username = ?");
        $stmt->execute([$admin_username]);
        $invited_admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invited_admin) {
            $_SESSION['invite_error'] = "❌ لا يوجد مشرف بهذا اسم المستخدم: " . htmlspecialchars($admin_username);
            header("Location: manage_group.php?group_id=" . $group_id);
            exit;
        }
        
        // Check if the admin is already in this group
        $stmt = $conn->prepare("SELECT COUNT(*) FROM group_admins WHERE group_id = ? AND admin_id = ?");
        $stmt->execute([$group_id, $invited_admin['id']]);
        $already_admin = $stmt->fetchColumn();
        
        if ($already_admin > 0) {
            $_SESSION['invite_error'] = "❌ هذا المشرف موجود بالفعل في المجموعة";
            header("Location: manage_group.php?group_id=" . $group_id);
            exit;
        }
        
        // Check if there's already a pending invitation
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_invitations WHERE group_id = ? AND invited_username = ? AND status = 'pending'");
        $stmt->execute([$group_id, $admin_username]);
        $pending_invitation = $stmt->fetchColumn();
        
        if ($pending_invitation > 0) {
            $_SESSION['invite_error'] = "❌ يوجد دعوة معلقة بالفعل لهذا المشرف";
            header("Location: manage_group.php?group_id=" . $group_id);
            exit;
        }
        
        // Check if the inviter has permission to invite to this group
        $stmt = $conn->prepare("SELECT COUNT(*) FROM group_admins WHERE group_id = ? AND admin_id = ?");
        $stmt->execute([$group_id, $inviter_admin_id]);
        $can_invite = $stmt->fetchColumn();
        
        if ($can_invite == 0) {
            $_SESSION['invite_error'] = "❌ ليس لديك صلاحية لدعوة مشرفين لهذه المجموعة";
            header("Location: admin.php");
            exit;
        }
        
        // Create the invitation
        $stmt = $conn->prepare("INSERT INTO admin_invitations (group_id, inviter_admin_id, invited_username, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$group_id, $inviter_admin_id, $admin_username]);
        
        $_SESSION['invite_success'] = "✅ تم إرسال الدعوة بنجاح إلى " . htmlspecialchars($invited_admin['name']) . " (@$admin_username)";
        
    } catch (Exception $e) {
        error_log("Admin invitation error: " . $e->getMessage());
        $_SESSION['invite_error'] = "❌ حدث خطأ في إرسال الدعوة. يرجى المحاولة مرة أخرى.";
    }
    
    header("Location: manage_group.php?group_id=" . $group_id);
    exit;
}

// If not POST request, redirect to admin page
header("Location: admin.php");
exit;
?>
