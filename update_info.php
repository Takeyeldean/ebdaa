    <?php
    session_start(); 
error_reporting(E_ALL);
session_start();
require_once 'includes/db.php';

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
        $email = trim($_POST['email']);
        if($role == 'student')
        $stmt = $conn->prepare("UPDATE students SET name = ?, email = ? WHERE id = ?");
        else
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");

        if ($stmt->execute([$name, $email, $userId])) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $success = "تم تحديث المعلومات بنجاح!";
        } else {
            $error = "حدث خطأ أثناء تحديث البيانات.";
        }
    }

   if (isset($_POST['update_pass'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Fetch current password (plain text)
            if($role == 'studen')
    $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
            else 
    $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");

    $stmt->execute([$userId]);
    $currentPassword = $stmt->fetchColumn();

    if ($current !== $currentPassword) {
        $error = "كلمة المرور الحالية غير صحيحة.";
    } elseif ($new !== $confirm) {
        $error = "كلمة المرور الجديدة غير متطابقة.";
    } else {
        // Update plain text password directly
        if($role == 'student')
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
        else
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");

        if ($stmt->execute([$new, $userId])) {
            $success = "تم تحديث كلمة المرور بنجاح!";
        } else {
            $error = "حدث خطأ أثناء تحديث كلمة المرور.";
        }
    }
}

}
// Fetch current user info
        if($role == 'student')
$stmt = $conn->prepare("SELECT name, email FROM students WHERE id = ?");
        else
            $stmt = $conn->prepare("SELECT name, email FROM admins WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

header("Location: profile.php");
