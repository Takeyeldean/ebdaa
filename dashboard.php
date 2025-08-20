<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); // ✅ أضف هذا السطر هنا
require_once 'includes/db.php';


if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

if ($_SESSION['user']['role'] === 'admin') {
    // ✅ الأدمن: يشوف كل الطلاب
     $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

    if ($group_id > 0) {
        // ✅ الأدمن: يشوف بس طلاب الجروب اللي ضغط عليه
        $stmt = $conn->prepare("SELECT s.name, s.degree 
                                FROM students s 
                                WHERE s.group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll();
    } else {
        $students = [];
    }
} 
else if ($_SESSION['user']['role'] === 'student') {
    // ✅ الطالب: نجيب group_id الخاص بيه
    $student_id = $_SESSION['user']['id'];

    $stmt = $conn->prepare("SELECT group_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $group_id = $stmt->fetchColumn();

    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT s.name, s.degree 
                                FROM students s 
                                WHERE s.group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll();
    } else {
        $students = [];
    }
} 
else {
    echo "❌ غير مسموح لك بالدخول.";
    exit;
}

// الحصول على group_id من الرابط



// تجهيز البيانات للجافاسكريبت
$labels = [];
$data = [];

foreach ($students as $student) {
    $labels[] = $student['name'];
    $data[]   = $student['degree'];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Degrees Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background: linear-gradient(to bottom, #fde047, #fb923c, #f97316);
    }
  </style>
</head>
<body class="bg-gray-100">

  <!-- Navbar -->
  <nav class="bg-white shadow-md px-6 py-3 flex justify-between items-center">
      <div class="flex space-x-6">
  <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
    <a href="admin.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
      لوحة التحكم
    </a>
  <?php endif; ?>
</div>

      <div class="flex items-center space-x-2">
        <span class="text-blue-600 font-bold text-3xl">🎓 إبداع</span>
      </div>
  </nav>

  <!-- Page Content -->
  <div class="p-8">
    <!-- <div class="flex justify-center mt-20">
      <h1 class="text-6xl font-bold text-blue-700"> <?php echo htmlspecialchars($_SESSION['user']['name']); ?> أهلا يا, 👋</h1>
      <h1 class="text-6xl font-bold text-blue-700">الدرجات</h1>
    </div> -->

    <div class="flex flex-col items-center mt-20 space-y-6">
  <!-- الترحيب -->
  <h1 class="text-6xl font-bold text-blue-700">
    <span class="text-blue-600">
      <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
    </span>
  ,أهلاً يا 
  </h1>

  <!-- العنوان -->
  <h2 class="text-6xl font-bold text-blue-700">
     الدرجات
  </h2>
</div>

    <!-- GPA Chart -->
    <div class="flex justify-center mt-8">
      <div class="bg-white shadow rounded-lg p-7 w-[1550px] flex justify-center" style="background: linear-gradient(90deg, #fff7ad, #ffa9f9);">
        <div class="h-[650px] w-[1400px] flex justify-center p-4">
          <canvas id="gpaChart" class="w-full h-full"></canvas>
        </div>
      </div>
    </div>

    <a href="logout.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg mt-6 inline-block">Logout</a>
  </div>

  <!-- Chart.js Script -->
  <script>
    const gpaCtx = document.getElementById('gpaChart').getContext('2d');

    new Chart(gpaCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
          label: 'عدد الدرجات',
          data: <?= json_encode($data) ?>,
          backgroundColor: [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'
          ],
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>
</body>
</html>
