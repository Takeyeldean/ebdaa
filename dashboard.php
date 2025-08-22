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
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body class="bg-gradient-to-b from-blue-100 via-blue-200 to-blue-300 min-h-screen font-sans">

  <!-- Navbar -->
  <nav class="bg-white shadow-lg px-6 py-3 flex justify-between items-center">
    <span class="text-blue-700 font-bold text-3xl">🎓 إبداع</span>
    <div class="space-x-2 space-x-reverse">
      
<a href="profile.php" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-800 transition flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z"/>
    </svg>
    حسابي
</a>
    </div>
  </nav>

  <div class="container mx-auto p-8">
    <!-- صورة الحساب -->
    <?php if (isset($profile_image)): ?>
      <div class="flex justify-center -mt-4">
        <img src="uploads/<?= htmlspecialchars($profile_image); ?>" 
             alt="Profile Image" 
             class="w-28 h-28 rounded-full border-4 border-blue-600 shadow-xl bg-white">
      </div>
    <?php endif; ?> 

    <!-- الترحيب -->
        <?php if ($_SESSION['user']['role'] === 'student'): ?>
          <div class="text-center mt-6 space-y-4">
            <h2 class="text-4xl font-bold text-blue-900">
              أهلاً يا <span class="text-indigo-700"><?= htmlspecialchars($_SESSION['user']['name']); ?></span> 👋
            </h2>
          </div>
          <?php endif; ?> 

    <!-- Chart Container -->
    <div class="mt-12 flex justify-center">
      <div class="bg-white shadow-xl rounded-2xl p-6 w-full max-w-6xl relative h-[500px]">
        <canvas id="gpaChart"></canvas>
      </div>
    </div>
  </div>
  <!-- Chart.js Script -->
<script>
  const ctx = document.getElementById('gpaChart').getContext('2d');

  const labels = <?= json_encode($labels) ?>; // أسماء الطلاب
  const data = <?= json_encode($data) ?>;     // درجات الطلاب

  // 🥇🥈🥉 الميداليات
  const medalEmojis = ["🥇", "🥈", "🥉"];
  const topTitles = ["البطل الذهبي", "البطل الفضي", "البطل البرونزي"];

  // نعمل Gradient لأول 3 فقط
  const gradients = [
    ctx.createLinearGradient(0, 0, 0, 400), // ذهبي
    ctx.createLinearGradient(0, 0, 0, 400), // فضي
    ctx.createLinearGradient(0, 0, 0, 400)  // برونزي
  ];

  // 🟡 ذهبي
  gradients[0].addColorStop(0, "#fde047"); 
  gradients[0].addColorStop(1, "#facc15");

  // ⚪ فضي
  gradients[1].addColorStop(0, "#d1d5db"); 
  gradients[1].addColorStop(1, "#9ca3af");

  // 🟠 برونزي
  gradients[2].addColorStop(0, "#fbbf24"); 
  gradients[2].addColorStop(1, "#b45309");

  // باقي الألوان العادية
  const normalColors = ['#22c55e', '#3b82f6', '#f97316', '#a855f7', '#ef4444'];

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels.map((name, i) => {
        if (i < 3) {
          return medalEmojis[i] + " " + topTitles[i] + " - " + name;  
          // مثال: 🥇 البطل الذهبي - أحمد
        }
        return name;
      }),
      datasets: [{
        label: 'درجات الطلاب',
        data: data,
        backgroundColor: data.map((_, i) => {
          if (i < 3) return gradients[i]; // أول 3 بـ Gradient
          return normalColors[(i - 3) % normalColors.length]; // الباقي ألوان ثابتة
        }),
        borderRadius: 14
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 1500,
        easing: 'easeOutBounce'
      },
     scales: {
  y: { beginAtZero: true },
  x: {
    ticks: {
      callback: function(value, index, ticks) {
        if (index < 3) {
          return labels[index] + " 🔥"; // نخليهم مميزين
        }
        return labels[index];
      },
      font: function(context) {
        if (context.index < 3) {
          return { size: 18, weight: 'bold' }; // أول 3 أكبر
        }
        return { size: 12, weight: 'bold' };   // الباقي عادي
      }
    }
  }
},

      plugins: {
        title: {
          display: true,
          text: '🏆 سباق الأبطال',
          font: { size: 26, weight: 'bold' },
          color: '#1f2937'
        },
        legend: { display: false },
        datalabels: {
          anchor: 'end',
          align: 'start',
          color: '#ffffff',
          font: { weight: 'bold', size: 16 },
          formatter: (value) => value // ❌ مفيش إيموجي جوه البار
        }
      }
    },
    plugins: [ChartDataLabels]
  });
</script>

</body>
</html>