  <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); 
require_once 'includes/db.php';
// email
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
        
        // Get group message and emoji
        $stmt = $conn->prepare("SELECT message, emoji FROM groups WHERE id = ?");
        $stmt->execute([$group_id]);
        $group_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $group_message = $group_data['message'] ?? '';
        $group_emoji = $group_data['emoji'] ?? '🤖';
    } else {
        $students = [];
        $group_message = '';
        $group_emoji = '🤖';
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
        
        // Get group message and emoji
        $stmt = $conn->prepare("SELECT message, emoji FROM groups WHERE id = ?");
        $stmt->execute([$group_id]);
        $group_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $group_message = $group_data['message'] ?? '';
        $group_emoji = $group_data['emoji'] ?? '🤖';
    } else {
        $students = [];
        $group_message = '';
        $group_emoji = '🤖';
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
    $labels[] = $student['name'] . " #" . $ranks[$student['id']] ;
    $data[] = $student['degree'];
    $images[] = $student['profile_image'] ?? 'default.png';
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إبداع - لوحة التحكم 🎯</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Cairo', Arial, sans-serif;
      background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 25%, #3b82f6 50%, #06b6d4 75%, #10b981 100%);
      background-size: 400% 400%;
      animation: gradientShift 12s ease infinite;
      min-height: 100vh;
    }

    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .floating {
      animation: floating 3s ease-in-out infinite;
    }

    @keyframes floating {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }

    .bounce-in {
      animation: bounceIn 0.8s ease-out;
    }

    @keyframes bounceIn {
      0% { transform: scale(0.3); opacity: 0; }
      50% { transform: scale(1.05); }
      70% { transform: scale(0.9); }
      100% { transform: scale(1); opacity: 1; }
    }

    .pulse {
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .nav-glass {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 0 0 25px 25px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }


    .chart-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding-top: 40px; /* Extra space for profile images and degrees below */
    }

    .welcome-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .profile-image {
      border: 4px solid #fff;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease;
    }

    .profile-image:hover {
      transform: scale(1.1);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    }

    .btn-primary {
      background: linear-gradient(45deg, #1e40af, #3b82f6);
      color: white;
      padding: 12px 24px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(30, 64, 175, 0.4);
    }

    .decoration {
      position: absolute;
      pointer-events: none;
      z-index: 1;
    }

    .decoration-icon {
      color: #f59e0b;
      font-size: 2rem;
      animation: rotate 4s linear infinite;
      opacity: 5;
    }
    
    @keyframes rotate {
      0% { transform: rotate(0deg) scale(0.8); opacity: 0.1; }
      25% { transform: rotate(90deg) scale(1); opacity: 0.3; }
      50% { transform: rotate(180deg) scale(0.9); opacity: 0.2; }
      75% { transform: rotate(270deg) scale(1.1); opacity: 0.3; }
      100% { transform: rotate(360deg) scale(0.8); opacity: 0.1; }
    }

    .decoration-1 { top: 5%; left: 5%; }
    .decoration-2 { top: 10%; right: 8%; }
    .decoration-3 { bottom: 15%; left: 10%; }
    .decoration-4 { bottom: 8%; right: 5%; }
    .decoration-5 { top: 20%; left: 15%; }
    .decoration-6 { top: 30%; right: 20%; }
    .decoration-7 { bottom: 25%; left: 5%; }
    .decoration-8 { bottom: 35%; right: 15%; }
    .decoration-9 { top: 40%; left: 8%; }
    .decoration-10 { top: 50%; right: 10%; }
    .decoration-11 { bottom: 45%; left: 20%; }
    .decoration-12 { bottom: 55%; right: 25%; }

    .character-behind {
      pointer-events: none;
    }

    .character-bubble {
      animation: float 3s ease-in-out infinite;
      position: relative;
    }

    .character-bubble::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 20px;
      width: 0;
      height: 0;
      border-left: 10px solid transparent;
      border-right: 10px solid transparent;
      border-top: 10px solid rgba(255, 255, 255, 0.9);
    }

    .character-emoji {
      filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }
  </style>
</head>
<body>

  <!-- Decorative elements for boys -->
  <div class="decoration decoration-1">
    <span class="decoration-icon">⚡</span>
  </div>
  <div class="decoration decoration-2">
    <span class="decoration-icon">🔥</span>
  </div>
  <div class="decoration decoration-3">
    <span class="decoration-icon">⚽</span>
  </div>
  <div class="decoration decoration-4">
    <span class="decoration-icon">🎮</span>
  </div>
  <div class="decoration decoration-5">
    <span class="decoration-icon">🏆</span>
  </div>
  <div class="decoration decoration-6">
    <span class="decoration-icon">🚀</span>
  </div>
  <div class="decoration decoration-7">
    <span class="decoration-icon">💪</span>
  </div>
  <div class="decoration decoration-8">
    <span class="decoration-icon">🎯</span>
  </div>
  <div class="decoration decoration-9">
    <span class="decoration-icon">🏅</span>
  </div>
  <div class="decoration decoration-10">
    <span class="decoration-icon">⭐</span>
  </div>
  <div class="decoration decoration-11">
    <span class="decoration-icon">🏈</span>
  </div>
  <div class="decoration decoration-12">
    <span class="decoration-icon">🎲</span>
  </div>

  <!-- Navbar -->
  <nav class="nav-glass px-6 py-4 flex justify-between items-center">
    <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      ⚡ إبداع
    </span>
    <div class="space-x-2 space-x-reverse">
      <a href="profile.php" class="btn-primary">
        <i class="fas fa-user"></i>
        حسابي
      </a>
    </div>
  </nav>

  <div class="container mx-auto p-8 relative z-10">
    <!-- صورة الحساب -->
    <?php if (isset($profile_image)): ?>
      <div class="flex justify-center -mt-4">
        <img src="uploads/<?= htmlspecialchars($profile_image); ?>" 
             alt="Profile Image" 
             class="w-36 h-36 rounded-full profile-image floating">
      </div>
    <?php endif; ?> 

    <!-- الترحيب -->
    <?php if ($_SESSION['user']['role'] === 'student'): ?>
      <div class="welcome-card text-center mt-8 p-8 bounce-in">
        <h2 class="text-5xl font-bold mb-4" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
          أهلاً وسهلاً يا <span class="text-6xl"><?= htmlspecialchars($_SESSION['user']['name']); ?></span>! ⚡
        </h2>
        <p class="text-xl text-gray-600 mb-4">دعنا نرى من الأبطال في مجموعتك! 🏆</p>
        <div class="flex justify-center space-x-4 space-x-reverse">
          <span class="text-2xl">⚡</span>
          <span class="text-2xl">🔥</span>
          <span class="text-2xl">⚽</span>
          <span class="text-2xl">🏆</span>
          <span class="text-2xl">🎮</span>
        </div>
      </div>
    <?php endif; ?> 

    <!-- Chart Container -->
    <div class="mt-12 flex justify-center relative">
      <!-- Character behind chart -->
      <div class="character-behind absolute left-8 top-0 z-0">
        <div class="character-bubble bg-white bg-opacity-90 rounded-2xl p-4 shadow-lg mb-4 max-w-xs">
          <p class="text-sm font-bold text-blue-600 text-center mb-1">عمو <?= htmlspecialchars($group_emoji) ?></p>
          <?php if (!empty($group_message)): ?>
            <p class="text-sm text-gray-800 text-center"><?= htmlspecialchars($group_message) ?></p>
          <?php else: ?>
            <p class="text-lg font-bold text-gray-800 text-center">أنا أتابع تقدمكم! 🔥</p>
            <p class="text-sm text-gray-600 text-center">استمروا في التميز! ⚡</p>
          <?php endif; ?>
        </div>
        <div class="character-emoji text-8xl"><?= htmlspecialchars($group_emoji) ?></div>
      </div>
      
    
     </div>
      <div class="chart-container p-8 w-full max-w-6xl relative h-[500px] floating z-10 ml-24">
        <canvas id="gpaChart"></canvas>
      </div>
  </div>  
<script>
  const ctx = document.getElementById('gpaChart').getContext('2d');

  const labels = <?= json_encode($labels) ?>; // أسماء الطلاب
  const data = <?= json_encode($data) ?>;     // درجات الطلاب

  // 🥇🥈🥉 الميداليات والرموز التعبيرية للأولاد
  const medalEmojis = ["🥇", "🥈", "🥉"];
  const topTitles = ["البطل الذهبي", "البطل الفضي", "البطل البرونزي"];
 const funEmojis = ["⚡", "🔥", "⚽", "🏆", "🎮", "🚀", "💪", "🎯", "🏅", "⭐"];

  // ألوان متدرجة لكل الأعمدة
  function createGradient(color1, color2) {
    const g = ctx.createLinearGradient(0, 0, 0, 400);
    g.addColorStop(0, color1);
    g.addColorStop(1, color2);
    return g;
  }

  // أول 3 مميزين (ألوان ذهبية وفضية وبرونزية)
  const specialGradients = [
    createGradient("#ffe761ff", "#ff8c00ff"), // ذهبي لامع
    createGradient("#C0C0C0", "#434343ff"), // فضي أنيق
    createGradient("#cf8c49ff", "#b86415ff")  // برونزي كلاسيكي
  ];

  // باقي الأعمدة (ألوان زاهية ومبهجة للأولاد)
  const funColors = [
    ["#3B82F6", "#1E40AF"], // أزرق قوي
    ["#10B981", "#059669"], // أخضر قوي
    ["#F59E0B", "#D97706"], // برتقالي ذهبي
    ["#EF4444", "#DC2626"], // أحمر قوي
    ["#8B5CF6", "#7C3AED"], // بنفسجي قوي
    ["#06B6D4", "#0891B2"], // سماوي قوي
    ["#84CC16", "#65A30D"], // أخضر ليموني
    ["#F97316", "#EA580C"], // برتقالي محترق
    ["#6366F1", "#4F46E5"], // أزرق بنفسجي
    ["#14B8A6", "#0D9488"]  // تركوازي قوي
  ];

  const barColors = data.map((_, i) => {
    if (i < 3) return specialGradients[i]; // أول 3 مميزين
    const colorPair = funColors[(i - 3) % funColors.length];
    return createGradient(colorPair[0], colorPair[1]);
  });

  // Create images array for profile pictures
  const images = <?= json_encode($images) ?>;
  
  // Preload images
  const loadedImages = [];
  const imagePromises = images && images.length > 0 ? images.map((imageName, index) => {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        loadedImages[index] = img;
        resolve(img);
      };
      img.onerror = () => {
        // If image fails to load, create a default avatar
        const canvas = document.createElement('canvas');
        canvas.width = 60;
        canvas.height = 60;
        const ctx2 = canvas.getContext('2d');
        
        // Create a gradient background
        const gradient = ctx2.createLinearGradient(0, 0, 60, 60);
        gradient.addColorStop(0, '#3b82f6');
        gradient.addColorStop(1, '#1e40af');
        ctx2.fillStyle = gradient;
        ctx2.fillRect(0, 0, 60, 60);
        
        // Add initials or emoji
        ctx2.fillStyle = 'white';
        ctx2.font = 'bold 24px Arial';
        ctx2.textAlign = 'center';
        ctx2.textBaseline = 'middle';
        ctx2.fillText('👤', 30, 30);
        
        loadedImages[index] = canvas;
        resolve(canvas);
      };
      img.src = `uploads/${imageName}`;
    });
  }) : [];

  // Animation frame for fire effect
  let animationFrame = 0;
  
  // Custom plugin to draw profile images and fire effects on bars
  const profileImagePlugin = {
    id: 'profileImages',
    afterDatasetsDraw: (chart) => {
      const { ctx, data, chartArea } = chart;
      const meta = chart.getDatasetMeta(0);
      
        meta.data.forEach((bar, index) => {
          // Draw profile images
          if (loadedImages[index]) {
            const x = bar.x;
            const y = bar.y - 15; // Position above the bar
            const imageSize = 80; // Size of the profile image
            
            // Draw white circle background
            ctx.save();
            ctx.beginPath();
            ctx.arc(x, y, imageSize/2 + 4, 0, 2 * Math.PI);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            
            // Draw blue border
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 3;
            ctx.stroke();
            
            // Draw the profile image
            ctx.beginPath();
            ctx.arc(x, y, imageSize/2, 0, 2 * Math.PI);
            ctx.clip();
            
            const img = loadedImages[index];
            const imgX = x - imageSize/2;
            const imgY = y - imageSize/2;
            
            if (img instanceof HTMLImageElement) {
              ctx.drawImage(img, imgX, imgY, imageSize, imageSize);
            } else {
              // Draw canvas fallback
              ctx.drawImage(img, imgX, imgY, imageSize, imageSize);
            }
            
            ctx.restore();
          }
        });
    }
  };

  // Wait for all images to load before creating chart
  const loadImages = imagePromises.length > 0 ? Promise.all(imagePromises) : Promise.resolve();
  
  loadImages.then(() => {
    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels.map((name, i) => {
          if (i < 3) {
            return medalEmojis[i] + " " + topTitles[i] + " - " + name;  
          }
        //  const emoji = funEmojis[(i - 3) % funEmojis.length];
          return name;
        }),
        datasets: [{
          label: 'درجات الأبطال',
          data: data,
          backgroundColor: barColors,
          borderRadius: 20,
          borderWidth: 3,
          borderColor: '#ffffff',
          hoverBorderWidth: 5,
          hoverBorderColor: '#FFD700'
        }]
      },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 2500,
        easing: 'easeOutBounce',
        delay: (context) => {
          return context.dataIndex * 200;
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      },
      scales: {
        y: { 
          beginAtZero: true,
          grid: {
            color: 'rgba(255, 255, 255, 0.3)',
            lineWidth: 2
          },
          ticks: {
            color: '#666',
            font: { size: 14, weight: 'bold' },
            callback: function(value) {
              return value + ' نقطة';
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            color: '#333',
            font: function(context) {
              if (context.index < 3) {
                return { size: 16, weight: 'bold' }; 
              }
              return { size: 14, weight: 'bold' };   
            },
            maxRotation: 45,
            minRotation: 0
          }
        }
      },
      plugins: {
        title: {
          display: true,
          text: '⚡ سباق الأبطال - من سيفوز بالمركز الأول؟ ⚡',
          font: { size: 28, weight: 'bold', family: 'Cairo' },
          color: '#1E40AF',
          padding: 20
        },
        legend: { 
          display: false 
        },  
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: '#FFD700',
          borderWidth: 2,
          cornerRadius: 10,
          titleFont: { size: 16, weight: 'bold' },
          bodyFont: { size: 14, weight: 'bold' },
          callbacks: {
            title: function(context) {
              const index = context[0].dataIndex;
              if (index < 3) {
                return medalEmojis[index] + " " + topTitles[index];
              }
              return  context[0].label;
            },
            label: function(context) {
              return  context.parsed.y + ' نقطة';
            }
          }
        },
        datalabels: {
          display: false
        }
      }
    },
    plugins: [ChartDataLabels, profileImagePlugin]
  });
  
  });
</script>


</body>
</html>