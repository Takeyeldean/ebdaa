<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); 
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

if ($_SESSION['user']['role'] === 'admin') {
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    $students = ($group_id > 0) ? $conn->prepare("SELECT name, degree FROM students WHERE group_id = ?") : [];
    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT name, degree FROM students WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll();
    } else {
        $students = [];
    }
} else if ($_SESSION['user']['role'] === 'student') {
    $student_id = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT group_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $group_id = $stmt->fetchColumn();
    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT name, degree FROM students WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll();
    } else {
        $students = [];
    }
} else {
    echo "❌ غير مسموح لك بالدخول.";
    exit;
}

$labels = [];
$data = [];
foreach ($students as $student) {
    $labels[] = $student['name'];
    $data[] = $student['degree'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة درجات الطلاب</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-b from-yellow-300 via-orange-400 to-orange-600 min-h-screen font-sans">

  <!-- Navbar -->
 <nav class="bg-white shadow-md px-6 py-3 flex justify-between items-center">
  <!-- الشعار -->
  <div class="flex items-center space-x-2">
    <span class="text-blue-600 font-bold text-3xl">🎓 إبداع</span>
  </div>

  <!-- الروابط -->
  <div class="flex space-x-8"> <!-- زودنا المسافة هنا -->
    <!-- رابط لوحة التحكم للأدمن فقط -->
    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
      <a href="admin.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        لوحة التحكم
      </a>
    <?php endif; ?>

    <!-- رابط حسابي -->
      <!-- <a href="profile.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        حسابي
      </a> -->
  </div>
</nav>



  <div class="container mx-auto p-8">
    <!-- الترحيب -->
    <div class="text-center mt-20 space-y-4">
      <h2 class="text-4xl font-bold text-blue-700">
        أهلاً يا <span class="text-blue-600"><?= htmlspecialchars($_SESSION['user']['name']); ?></span> 👋
      </h2>
      <h1 class="text-5xl font-bold text-blue-700">الدرجات</h1>
    </div>

    <!-- Chart Container -->
    <div class="mt-12 flex justify-center">
      <div class="bg-white shadow rounded-lg p-6 w-full max-w-6xl">
        <canvas id="gpaChart" class="w-full h-96"></canvas>
      </div>
    </div>

    <!-- Logout -->
    <div class="text-center mt-8">
      <a href="logout.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">تسجيل الخروج</a>
    </div>
  </div>

  <!-- Chart.js Script -->
  <script>
    const ctx = document.getElementById('gpaChart').getContext('2d');
    new Chart(ctx, {
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
        },
        plugins: {
          legend: {
            display: true,
            position: 'top'
          }
        }
      }
    });
  </script>

</body>
</html>
