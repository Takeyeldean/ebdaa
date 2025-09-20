<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error'] = "غير مسموح لك بالدخول";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $emoji = isset($_POST['emoji']) ? trim($_POST['emoji']) : '🤖';
    
    
    if ($group_id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE groups SET message = ?, emoji = ? WHERE id = ?");
            $result = $stmt->execute([$message, $emoji, $group_id]);
            
            if ($result) {
                if ($message) {
                    $_SESSION['success'] = "تم حفظ الرسالة والإيموجي بنجاح!";
                } else {
                    $_SESSION['success'] = "تم مسح الرسالة وحفظ الإيموجي بنجاح!";
                }
            } else {
                $_SESSION['error'] = "فشل في تحديث البيانات";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في حفظ البيانات: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف المجموعة غير صحيح";
    }
    
    // Redirect back to manage_group.php
    header("Location: manage_group.php?group_id=" . $group_id);
    exit();
} else {
    header("Location: admin.php");
    exit();
}
?>
