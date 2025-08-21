<?php
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if ($group_id == 0) die("Group not found!");

$stmt = $conn->prepare("SELECT * FROM students WHERE group_id = ?");
$stmt->execute([$group_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إدارة المجموعة</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

  <!-- Navbar -->
  <nav class="bg-white shadow-md px-6 py-3 flex justify-between items-center">
    <span class="text-blue-600 font-bold text-2xl">🎓 إبداع - إدارة المجموعة</span>
    
    <div>
        <a href="admin.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">المجموعات</a>
        <a href="profile.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">حسابي</a>

    </div>

  
</nav>

  <div class="container mx-auto p-8">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">إدارة درجات المجموعة</h1>

    <!-- جدول الطلاب -->
    <div class="bg-white shadow rounded-lg p-4 overflow-x-auto mb-8">
      <table class="w-full min-w-max border-collapse">
        <thead>
          <tr class="bg-gray-200 text-gray-700">
            <th class="p-3 text-right">الطالب</th>
            <th class="p-3 text-center">الدرجة الحالية</th>
            <th class="p-3 text-center">تحكم</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student): ?>
            <tr class="border-b hover:bg-gray-300 transition">
                <td class="p-3"><?= htmlspecialchars($student['name']) ?></td>
                <td class="p-3 text-center font-bold"><?= $student['degree'] ?></td>
                <td class="p-3 text-center space-x-1">
                  <!-- أزرار إضافة درجات -->
                  <?php foreach ([5,3,2,1] as $inc): ?>
                    <a href="update_degree.php?id=<?= $student['id'] ?>&amount=<?= $inc ?>" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-800 transition">+<?= $inc ?></a>
                  <?php endforeach; ?>

                  <!-- إضافة قيمة مخصصة -->
                  <form action="update_degree.php" method="get" class="inline-block">
                    <input type="hidden" name="id" value="<?= $student['id'] ?>">
                    <input type="number" name="amount" class="w-16 border rounded px-2 py-1" placeholder="0">
                    <button type="submit" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 transition">اضافة</button>
                  </form>

                  <!-- أزرار خصم درجات -->
                  <?php foreach ([5,3,2,1] as $dec): ?>
                    <a href="update_degree.php?id=<?= $student['id'] ?>&amount=-<?= $dec ?>" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-800 transition">-<?= $dec ?></a>
                  <?php endforeach; ?>
                </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (empty($students)): ?>
        <p class="text-gray-600 mt-3">لا يوجد طلاب في هذه المجموعة.</p>
      <?php endif; ?>
    </div>

    <!-- إضافة طالب جديد -->
    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-2xl font-bold mb-4">➕ إضافة طالب جديد</h2>

      <!-- رسائل التنبيه -->
      <?php if (!empty($_SESSION['error'])): ?>
        <p class="text-red-600 mb-3"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
      <?php endif; ?>
      <?php if (!empty($_SESSION['success'])): ?>
        <p class="text-green-600 mb-3"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
      <?php endif; ?>

      <form method="post" action="add.php" class="space-y-4">
         <input type="hidden" name="group_id" value="<?= $group_id ?>">
        <div>
          <label class="block mb-1 font-medium">اسم الطالب:</label>
          <input type="text" placeholder="الإسم" name="name" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
          <label class="block mb-1 font-medium">البريد الإلكتروني:</label>
          <input type="email" placeholder="البريد الإلكتروني" name="email" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
          <label class="block mb-1 font-medium">كلمة المرور:</label>
          <input type="text" name="password" placeholder="كلمة المرور" class="w-full border rounded px-3 py-2" required>
        </div>
        <button type="submit" name="add_student" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">إضافة الطالب</button>
      </form>
    </div>

  </div>
</body>
</html>
