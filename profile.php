<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php';
// username
// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

// Fetch current user info
$table = ($role === 'student') ? 'students' : 'admins';
if ($role == 'student')
    $stmt = $conn->prepare("SELECT name, username, profile_image FROM $table WHERE id = ?");
else
    $stmt = $conn->prepare("SELECT name, username FROM $table WHERE id = ?");

$stmt->execute([$userId]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حسابي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .profile-container {
            position: relative;
            width: 160px;
            height: 160px;
            margin: 0 auto 1.5rem auto;
        }
        .profile-container img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #2563eb;
        }
        .upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #2563eb;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            font-size: 20px;
        }
        .upload-btn input[type="file"] {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

  <nav class="bg-white shadow-lg px-6 py-3 flex justify-between items-center">
    <span class="text-blue-700 font-bold text-3xl">🎓 إبداع</span>
    <div class="space-x-2 space-x-reverse">
      <div class="flex items-center space-x-4">
        <?php if ($role === 'student'): ?>
            <a href="dashboard.php" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-800 transition flex items-center gap-2">الدرجات</a>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
            <a href="admin.php" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-800 transition flex items-center gap-2">المجموعات</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

<div class="container mx-auto p-8">

    <!-- Profile Image with Upload Button -->
    <?php if ($role === 'student'): ?>
    <div class="profile-container">
        <?php if (!empty($user['profile_image'])): ?>
            <img src="uploads/<?= htmlspecialchars($user['profile_image']) ?>" alt="صورة الملف الشخصي">
        <?php else: ?>
            <img src="uploads/default.png" alt="صورة الملف الشخصي">
        <?php endif; ?>
        <form action="upload_image.php" method="POST" enctype="multipart/form-data" class="upload-btn">
            <label>
                <input type="file" name="profile_image" accept="image/*" onchange="this.form.submit()">
                ✎
            </label>
        </form>
    </div>
    <?php endif; ?>

    <h2 class="text-3xl font-bold text-blue-700 mb-6">حسابي</h2>

    <!-- ✅ رسائل النجاح أو الخطأ -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Update Name & username -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">تحديث المعلومات</h3>
        <form method="POST" action="update_info.php">
            <input type="hidden" name="update_info">
            <div class="mb-4">
                <label class="block mb-1 font-semibold">الاسم</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">اسم المستخدم</label>
                <input type="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="w-full p-2 border rounded">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">تحديث</button>
        </form>
    </div>

    <!-- Update Password -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">تغيير كلمة المرور</h3>
        <form method="POST" action="update_info.php">
            <input type="hidden" name="update_pass">
            <div class="mb-4">
                <label class="block mb-1 font-semibold">كلمة المرور الحالية</label>
                <input type="password" name="current_password" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">كلمة المرور الجديدة</label>
                <input type="password" name="new_password" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="confirm_password" class="w-full p-2 border rounded">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">تغيير كلمة المرور</button>
        </form>
    </div>

    <!-- Logout Button at the Bottom -->
    <div class="flex justify-end mt-6">
        <a href="logout.php" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 transition">تسجيل الخروج</a>
    </div>

</div>

</body>
</html>
