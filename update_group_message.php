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
    
    if ($group_id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE groups SET message = ? WHERE id = ?");
            $stmt->execute([$message, $group_id]);
            
            if ($message) {
                $_SESSION['success'] = "تم حفظ الرسالة بنجاح!";
            } else {
                $_SESSION['success'] = "تم مسح الرسالة بنجاح!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في حفظ الرسالة: " . $e->getMessage();
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
