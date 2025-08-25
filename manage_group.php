<?php
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";
// username
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
  <title>إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-b from-blue-50 to-blue-100 min-h-screen font-sans">

  <!-- Navbar -->
  <nav class="bg-white shadow-lg px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <span class="text-blue-700 font-bold text-3xl">🎓 إبداع</span>
    
    <div class="flex items-center gap-3">
        <a href="admin.php" class="bg-white text-blue-700 border border-blue-700 font-semibold px-4 py-2 rounded-lg hover:bg-blue-50 transition"> المجموعات</a>
<a href="profile.php" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-800 transition flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z"/>
    </svg>
    حسابي
</a> 
   </div>
  </nav>

  <div class="container mx-auto p-8">

    <!-- العنوان -->
    <h1 class="text-4xl font-bold text-blue-800 mb-8 text-center">إدارة المجموعة</h1>

    <!-- جدول الطلاب -->
    <div class="bg-white shadow-md rounded-2xl p-6 overflow-x-auto mb-12">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-blue-100 text-blue-800">
            <th class="p-3 text-right"> الطالب</th>
            <th class="p-3 text-center"> الدرجة الحالية</th>
            <th class="p-3 text-center"> تحكم</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student): ?>
            <tr class="border-b hover:bg-blue-50 transition">
                <td class="p-3 font-medium text-gray-700"><?= htmlspecialchars($student['name']) ?></td>
                <td class="p-3 text-center font-bold text-blue-700"><?= $student['degree'] ?></td>
                <td class="p-3 text-center space-x-1 space-x-reverse">
                  <!-- أزرار إضافة درجات -->
                  <?php foreach ([5,3,2,1] as $inc): ?>
                    <a href="update_degree.php?id=<?= $student['id'] ?>&amount=<?= $inc ?>" class="inline-block bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition">+<?= $inc ?></a>
                  <?php endforeach; ?>

                  <!-- إضافة قيمة مخصصة -->
                  <form action="update_degree.php" method="get" class="inline-block mx-2">
                    <input type="hidden" name="id" value="<?= $student['id'] ?>">
                    <input type="number" name="amount" class="w-20 border rounded-lg px-2 py-1 focus:ring-2 focus:ring-blue-400" placeholder="0">
                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition">إضافة</button>
                  </form>

                  <!-- أزرار خصم درجات -->
                  <?php foreach ([5,3,2,1] as $dec): ?>
                    <a href="update_degree.php?id=<?= $student['id'] ?>&amount=-<?= $dec ?>" class="inline-block bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition">-<?= $dec ?></a>
                  <?php endforeach; ?>
                </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (empty($students)): ?>
        <p class="text-gray-600 mt-3 text-center">⚠️ لا يوجد طلاب في هذه المجموعة.</p>
      <?php endif; ?>
    </div>

      <!-- إضافة طالب جديد -->
      <div class="bg-white shadow-md rounded-2xl p-8">
  <h2 class="text-2xl font-bold text-blue-800 mb-6">➕ إضافة طالب جديد</h2>

  <!-- رسائل التنبيه -->
  <?php if (!empty($_SESSION['error'])): ?>
    <p class="text-red-600 mb-4 font-semibold"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
  <?php endif; ?>
  <?php if (!empty($_SESSION['success'])): ?>
    <p class="text-green-600 mb-4 font-semibold"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
  <?php endif; ?>

  <form method="post" action="add.php" class="space-y-5">
    <input type="hidden" name="group_id" value="<?= $group_id ?>">
    
    <div>
      <label class="block mb-1 font-medium text-gray-700">اسم الطالب:</label>
      <input type="text" placeholder="الإسم" name="name" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div>
      <label class="block mb-1 font-medium text-gray-700">اسم المستخدم:</label>
      <input type="username" placeholder="البريد الإلكتروني" name="username" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div>
      <label class="block mb-1 font-medium text-gray-700">كلمة المرور:</label>
      <input type="password" name="password" placeholder="كلمة المرور" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div>
      <label class="block mb-1 font-medium text-gray-700">تأكيد كلمة المرور:</label>
      <input type="password" name="confirm_password" placeholder="أعد كتابة كلمة المرور" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <button type="submit" name="add_student" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition shadow-md">
      إضافة الطالب
    </button>
  </form>
</div>


  </div>
</body>
</html>
