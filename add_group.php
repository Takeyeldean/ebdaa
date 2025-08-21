<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php';

// ✅ تأكد أن المستخدم أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $group_name = trim($_POST['group_name']);
    $admin_id = $_SESSION['user']['id']; // ID الأدمن الحالي

    if (!empty($group_name)) {
        try {
            // ✅ 1. إدخال الجروب في جدول groups
            $stmt = $conn->prepare("INSERT INTO groups (name) VALUES (?)");
            $stmt->execute([$group_name]);

            // ✅ 2. جلب ID الجروب الجديد
            $group_id = $conn->lastInsertId();

            // ✅ 3. ربط الأدمن مع الجروب الجديد
            $stmt2 = $conn->prepare("INSERT INTO group_admins (group_id, admin_id) VALUES (?, ?)");
            $stmt2->execute([$group_id, $admin_id]);

            $success = "✅ تم إضافة المجموعة بنجاح!";
        } catch (PDOException $e) {
            $error = "⚠️ خطأ: " . $e->getMessage();
        }
    } else {
        $error = "⚠️ الرجاء إدخال اسم المجموعة.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إضافة مجموعة</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="bg-white shadow-lg rounded-xl p-6 w-full max-w-md">
    <h2 class="text-2xl font-bold text-blue-700 mb-4">إضافة مجموعة جديدة</h2>

    <?php if ($success): ?>
      <div class="bg-green-100 text-green-700 p-3 rounded mb-3"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-3"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label for="group_name" class="block mb-1 text-gray-700 font-medium">اسم المجموعة:</label>
        <input type="text" id="group_name" name="group_name" 
               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" 
               required>
      </div>

      <button type="submit" 
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition w-full">
         إضافة
      </button>
    </form>

    <div class="mt-4 text-center">
      <a href="admin.php" class="bg-white text-blue-700 border border-blue-700 font-semibold px-4 py-2 rounded-lg hover:bg-blue-50 transition">العودة إلى لوحة التحكم</a>
    </div>
  </div>

</body>
</html>
