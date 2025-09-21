<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);   
session_start();
require_once "includes/db.php"; // الاتصال بقاعدة البيانات (PDO)
// username
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = $_POST['role'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username");
    } else {
        $stmt = $conn->prepare("SELECT * FROM students WHERE username = :username");
    }

    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
   // if ($user && password_verify($password, $user['password'])) {
        if ($user && $password === $user['password']){

        $_SESSION['user'] = [
            "id" => $user['id'],
            "name" => $user['name'],
            "username" => $user['username'],
            "role" => $role     
        ];
         $redirect = ($_SESSION['user']['role'] === 'admin') ? 'admin.php' : 'dashboard.php';
    header("Location: $redirect");
        exit();
    } else {
        echo "<script>alert('Invalid username or password!'); window.history.back();</script>";
    }
}
?>
