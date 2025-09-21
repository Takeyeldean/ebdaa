<?php
// Optimized Dashboard - Performance Enhanced Version
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); 
require_once 'includes/db.php';

// Security check
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Optimized database queries with single query approach
$user_role = $_SESSION['user']['role'];
$user_id = $_SESSION['user']['id'];

if ($user_role === 'admin') {
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    
    if ($group_id > 0) {
        // Single optimized query with JOIN
        $stmt = $conn->prepare("
            SELECT s.id, s.name, s.degree, s.profile_image, g.message, g.emoji
            FROM students s 
            LEFT JOIN groups g ON s.group_id = g.id 
            WHERE s.group_id = ? 
            ORDER BY s.degree DESC
        ");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $group_message = $students[0]['message'] ?? '';
        $group_emoji = $students[0]['emoji'] ?? '🤖';
    } else {
        $students = [];
        $group_message = '';
        $group_emoji = '🤖';
    }
} else if ($user_role === 'student') {
    // Single optimized query for student
    $stmt = $conn->prepare("
        SELECT s.id, s.name, s.degree, s.profile_image, s.group_id, g.message, g.emoji
        FROM students s 
        LEFT JOIN groups g ON s.group_id = g.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$user_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student_data) {
        $group_id = $student_data['group_id'];
        $profile_image = $student_data['profile_image'] ?? 'default.png';
        $group_message = $student_data['message'] ?? '';
        $group_emoji = $student_data['emoji'] ?? '🤖';
        
        // Get all students in the same group
        $stmt = $conn->prepare("
            SELECT id, name, degree, profile_image 
            FROM students 
            WHERE group_id = ? 
            ORDER BY degree DESC
        ");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $students = [];
        $group_message = '';
        $group_emoji = '🤖';
    }
} else {
    echo "❌ غير مسموح لك بالدخول.";
    exit;
}

// Optimized ranking calculation
$ranks = [];
$rank = 1;
$current_degree = null;

foreach ($students as $index => $student) {
    if ($current_degree !== null && $student['degree'] < $current_degree) {
        $rank = $index + 1;
    }
    $ranks[$student['id']] = $rank;
    $current_degree = $student['degree'];
}

// Prepare chart data efficiently
$chart_data = array_map(function($student) use ($ranks) {
    return [
        'label' => $student['name'] . " #" . $ranks[$student['id']],
        'degree' => $student['degree'],
        'image' => $student['profile_image'] ?? 'default.png'
    ];
}, $students);

// Get notification count efficiently
$notification_count = 0;
if ($user_role === 'student') {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE student_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $notification_count = $result['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إبداع - لوحة التحكم 🎯</title>
  
  <!-- Preload critical resources -->
  <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" as="style">
  <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
  
  <!-- Critical CSS inline for faster rendering -->
  <style>
    *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Cairo',Arial,sans-serif;background:linear-gradient(135deg,#1e3a8a 0%,#1e40af 25%,#3b82f6 50%,#06b6d4 75%,#10b981 100%);background-size:400% 400%;animation:gradientShift 8s ease infinite;min-height:100vh;overflow-x:hidden}@keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}.nav-glass{background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);border-radius:20px;box-shadow:0 8px 32px rgba(0,0,0,0.1)}.btn-primary{background:linear-gradient(135deg,#3b82f6,#1d4ed8);border:none;border-radius:25px;color:white;padding:12px 24px;font-weight:600;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(59,130,246,0.3)}.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(59,130,246,0.4)}.chart-container{position:relative;background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);border-radius:20px;padding:20px;margin:20px 0}.character{position:absolute;right:20px;top:50%;transform:translateY(-50%);font-size:4rem;z-index:1;animation:floating 3s ease-in-out infinite}@keyframes floating{0%,100%{transform:translateY(0px)}50%{transform:translateY(-20px)}}.notification-badge{position:absolute;top:-8px;right:-8px;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border-radius:50%;width:20px;height:20px;font-size:12px;display:flex;align-items:center;justify-content:center;font-weight:bold;animation:pulse 2s infinite}@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}.performance-optimized{will-change:transform;transform:translateZ(0)}.lazy-load{opacity:0;transition:opacity 0.3s}.lazy-load.loaded{opacity:1}
  </style>
  
  <!-- External resources -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  
  <!-- Load optimized CSS asynchronously -->
  <link rel="preload" href="/assets/css/optimized.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="/assets/css/optimized.css"></noscript>
</head>
<body>
  <!-- Navigation -->
  <nav class="nav-glass p-4 m-4 performance-optimized">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4 space-x-reverse">
        <h1 class="text-2xl font-bold text-white text-shadow">إبداع 🎯</h1>
        <?php if ($user_role === 'admin'): ?>
          <a href="admin.php" class="btn-primary hover-lift">المجموعات</a>
          <a href="admin_questions.php" class="btn-primary hover-lift relative">
            الأسئلة
            <?php if ($notification_count > 0): ?>
              <span class="notification-badge"><?= $notification_count ?></span>
            <?php endif; ?>
          </a>
          <a href="admin_invitations.php" class="btn-primary hover-lift">الدعوات</a>
        <?php else: ?>
          <a href="dashboard.php" class="btn-primary hover-lift">الترتيب</a>
          <a href="student_questions.php" class="btn-primary hover-lift relative">
            الأسئلة
            <?php if ($notification_count > 0): ?>
              <span class="notification-badge"><?= $notification_count ?></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>
        <a href="profile.php" class="btn-primary hover-lift">حسابي الشخصي</a>
        <a href="logout.php" class="btn-primary hover-lift">تسجيل الخروج</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container mx-auto px-4 py-8">
    <!-- Character Message -->
    <?php if (!empty($group_message)): ?>
      <div class="glass-card p-6 mb-8 text-center performance-optimized">
        <div class="text-6xl mb-4"><?= htmlspecialchars($group_emoji) ?></div>
        <p class="text-white text-xl font-semibold text-shadow"><?= htmlspecialchars($group_message) ?></p>
      </div>
    <?php endif; ?>

    <!-- Chart Container -->
    <div class="chart-container performance-optimized">
      <h2 class="text-3xl font-bold text-white text-center mb-8 text-shadow">ترتيب الطلاب 🏆</h2>
      
      <!-- Character -->
      <div class="character"><?= htmlspecialchars($group_emoji) ?></div>
      
      <canvas id="studentsChart" width="800" height="400"></canvas>
    </div>
  </div>

  <!-- Load optimized JavaScript asynchronously -->
  <script>
    // Inline critical JavaScript for immediate execution
    const chartData = <?= json_encode($chart_data) ?>;
    const notificationCount = <?= $notification_count ?>;
    
    // Performance monitoring
    const startTime = performance.now();
    
    // Chart configuration with performance optimizations
    const chartConfig = {
      type: 'bar',
      data: {
        labels: chartData.map(item => item.label),
        datasets: [{
          label: 'الدرجات',
          data: chartData.map(item => item.degree),
          backgroundColor: chartData.map((_, index) => {
            const colors = [
              'rgba(59, 130, 246, 0.8)',
              'rgba(16, 185, 129, 0.8)',
              'rgba(245, 158, 11, 0.8)',
              'rgba(239, 68, 68, 0.8)',
              'rgba(139, 92, 246, 0.8)'
            ];
            return colors[index % colors.length];
          }),
          borderColor: chartData.map((_, index) => {
            const colors = [
              'rgba(59, 130, 246, 1)',
              'rgba(16, 185, 129, 1)',
              'rgba(245, 158, 11, 1)',
              'rgba(239, 68, 68, 1)',
              'rgba(139, 92, 246, 1)'
            ];
            return colors[index % colors.length];
          }),
          borderWidth: 2,
          borderRadius: 10,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          datalabels: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            },
            ticks: {
              color: 'white',
              font: {
                family: 'Cairo',
                size: 14,
                weight: 'bold'
              }
            }
          },
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: 'white',
              font: {
                family: 'Cairo',
                size: 12,
                weight: 'bold'
              },
              maxRotation: 0,
              callback: function(value, index) {
                const label = this.getLabelForValue(value);
                return label.length > 15 ? label.substring(0, 15) + '...' : label;
              }
            }
          }
        },
        animation: {
          duration: 1000,
          easing: 'easeInOutQuart'
        },
        interaction: {
          intersect: false,
          mode: 'index'
        }
      },
      plugins: [{
        id: 'customPlugin',
        afterDraw: function(chart) {
          const ctx = chart.ctx;
          const datasets = chart.data.datasets;
          
          datasets.forEach((dataset, datasetIndex) => {
            const meta = chart.getDatasetMeta(datasetIndex);
            
            meta.data.forEach((bar, index) => {
              const imageUrl = chartData[index].image;
              const img = new Image();
              
              img.onload = function() {
                const x = bar.x - bar.width / 2;
                const y = bar.y - 10;
                const size = 30;
                
                ctx.save();
                ctx.beginPath();
                ctx.arc(x + size/2, y + size/2, size/2, 0, 2 * Math.PI);
                ctx.clip();
                ctx.drawImage(img, x, y, size, size);
                ctx.restore();
              };
              
              img.src = 'uploads/' + imageUrl;
            });
          });
        }
      }]
    };
    
    // Initialize chart when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
      const ctx = document.getElementById('studentsChart').getContext('2d');
      const chart = new Chart(ctx, chartConfig);
      
      // Performance monitoring
      const endTime = performance.now();
      console.log(`Chart rendered in ${(endTime - startTime).toFixed(2)}ms`);
      
      // Add performance optimization
      chart.canvas.style.willChange = 'transform';
      chart.canvas.style.transform = 'translateZ(0)';
    });
  </script>
  
  <!-- Load optimized JavaScript asynchronously -->
  <script src="/assets/js/optimized.js" async></script>
  
  <!-- Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
          .then(function(registration) {
            console.log('SW registered: ', registration);
          })
          .catch(function(registrationError) {
            console.log('SW registration failed: ', registrationError);
          });
      });
    }
  </script>
</body>
</html>
