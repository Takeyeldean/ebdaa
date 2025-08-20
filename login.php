<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);   
session_start();
require_once "includes/db.php"; // الاتصال بقاعدة البيانات (PDO)

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = $_POST['role'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = :email");
    } else {
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = :email");
    }

    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
   // if ($user && password_verify($password, $user['password'])) {
        if ($user && $password === $user['password']){

        $_SESSION['user'] = [
            "id" => $user['id'],
            "name" => $user['name'],
            "role" => $role
        ];
         $redirect = ($_SESSION['user']['role'] === 'admin') ? 'admin.php' : 'dashboard.php';
    header("Location: $redirect");
        exit();
    } else {
        echo "<script>alert('Invalid email or password!'); window.history.back();</script>";
    }
}
?>
