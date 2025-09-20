<?php
$host = "localhost";      // اسم السيرفر (عادةً localhost)
$dbname = "ebdaa";    // اسم قاعدة البيانات
$username = "root";       // يوزر قاعدة البيانات (افتراضي في XAMPP = root)
$password = "";           // الباسورد (افتراضي في XAMPP = فاضي)

// الاتصال باستخدام PDO
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // تفعيل وضع الأخطاء
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // استخدام associative arrays بدل من الأرقام
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

