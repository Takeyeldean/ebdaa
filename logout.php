<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// مسح كل بيانات الـ session
session_unset();

// إنهاء الـ session
session_destroy();

// إعادة التوجيه لصفحة تسجيل الدخول
header("Location: login");
exit();
?>
