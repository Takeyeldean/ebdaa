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
    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, degree, profile_image FROM students WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll();
    } else {
        $students = [];
    }
} else if ($_SESSION['user']['role'] === 'student') {
    $student_id = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT group_id, profile_image FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $group_id = $row['group_id'];
    $profile_image = $row['profile_image'] ?? 'default.png';

    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, degree, profile_image FROM students WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll();
    } else {
        $students = [];
    }
} else {
    echo "❌ غير مسموح لك بالدخول.";
    exit;
}

// ---------------- إضافة الترتيب ----------------
usort($students, function($a, $b) {
    return $b['degree'] <=> $a['degree']; // sort descending
});

$ranks = [];
$rank = 1;
foreach ($students as $s) {
    $ranks[$s['id']] = $rank++;
}

// تجهيز البيانات للرسم
$labels = [];
$data = [];
$images = [];
foreach ($students as $student) {
    $labels[] = $student['name'] . " " . $ranks[$student['id']] ;
    $data[] = $student['degree'];
    $images[] = $student['profile_image'] ?? 'default.png';
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
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body class="bg-gradient-to-b from-yellow-300 via-orange-400 to-orange-600 min-h-screen font-sans">

  <!-- Navbar -->
  <nav class="bg-white shadow-md px-6 py-3 flex justify-between items-center">
    <span class="text-blue-600 font-bold text-2xl">🎓 إبداع </span>
    <div>
      
<?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <a href="admin.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">المجموعات</a>
        <?php endif; ?> 
        <a href="profile.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">حسابي</a>
    </div>
  </nav>

  <div class="container mx-auto p-8">
    <!-- صورة الحساب -->
    <?php if (isset($profile_image)): ?>
      <div class="flex justify-center -mt-2">
        <img src="uploads/<?= htmlspecialchars($profile_image); ?>" 
             alt="Profile Image" 
             class="w-28 h-28 rounded-full border-4 border-white shadow-lg">
      </div>
    <?php endif; ?> 

    <!-- الترحيب -->
    <div class="text-center mt-6 space-y-4">
      <h2 class="text-4xl font-bold text-blue-700">
        أهلاً يا <span class="text-blue-600"><?= htmlspecialchars($_SESSION['user']['name']); ?></span> 👋
      </h2>
      </div>

    <!-- Chart Container -->
    <div class="mt-12 flex justify-center">
      <div class="bg-white shadow rounded-lg p-6 w-full max-w-6xl relative h-[500px]">
        <canvas id="gpaChart"></canvas>
      </div>
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
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6','#14b8a6', '#f97316'
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
          },
          datalabels: {
            anchor: 'end',
            align: 'start',
            color: '#ffffffff',
            font: {
              weight: 'bold',
              size: 14
            },
            formatter: value => value
          }
        }
      },
      plugins: [ChartDataLabels]
    });
  </script>

</body>
</html>
