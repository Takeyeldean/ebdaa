  <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
require_once 'includes/db.php';
require_once 'includes/url_helper.php';
// email
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$role = $_SESSION['user']['role'];  

if ($_SESSION['user']['role'] === 'admin') {
    $group_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['group_id']) ? intval($_GET['group_id']) : 0);
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
    
    if (!$row) {
        echo "❌ لم يتم العثور على بيانات الطالب.";
        exit;
    }
    
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
  <!-- Preload critical resources for faster loading -->
  <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" as="style">
  <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  
  <!-- Load optimized CSS asynchronously -->
  <link rel="preload" href="/assets/css/optimized.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="/assets/css/optimized.css"></noscript>
  <style>

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

     body {
       font-family: 'Cairo', Arial, sans-serif;
       background: #000011;
       background-image: 
         radial-gradient(ellipse at 20% 20%, rgba(138, 43, 226, 0.4) 0%, transparent 50%),
         radial-gradient(ellipse at 80% 80%, rgba(255, 20, 147, 0.3) 0%, transparent 50%),
         radial-gradient(ellipse at 40% 60%, rgba(0, 191, 255, 0.2) 0%, transparent 50%),
         radial-gradient(ellipse at 60% 30%, rgba(255, 105, 180, 0.25) 0%, transparent 50%),
         radial-gradient(ellipse at 10% 70%, rgba(138, 43, 226, 0.2) 0%, transparent 50%);
       background-size: 100% 100%, 100% 100%, 100% 100%, 100% 100%, 100% 100%;
       animation: galaxyShift 20s ease-in-out infinite;
       min-height: 100vh;
       overflow-x: hidden;
       position: relative;
     }

     @keyframes galaxyShift {
       0%, 100% { 
         background-position: 0% 0%, 100% 100%, 50% 50%, 25% 75%, 75% 25%;
       }
       25% { 
         background-position: 25% 25%, 75% 75%, 75% 25%, 50% 50%, 25% 75%;
       }
       50% { 
         background-position: 50% 50%, 50% 50%, 25% 75%, 75% 25%, 50% 50%;
       }
       75% { 
         background-position: 75% 75%, 25% 25%, 50% 50%, 25% 75%, 75% 25%;
       }
     }

     /* Galaxy Stars Background */
     body::before {
       content: '';
       position: fixed;
       top: 0;
       left: 0;
       width: 100%;
       height: 100%;
       background-image: 
         radial-gradient(2px 2px at 20px 30px, #eee, transparent),
         radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 90px 40px, #fff, transparent),
         radial-gradient(1px 1px at 130px 80px, rgba(255,255,255,0.6), transparent),
         radial-gradient(2px 2px at 160px 30px, #eee, transparent),
         radial-gradient(1px 1px at 200px 60px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 250px 20px, #fff, transparent),
         radial-gradient(1px 1px at 300px 90px, rgba(255,255,255,0.6), transparent),
         radial-gradient(2px 2px at 350px 50px, #eee, transparent),
         radial-gradient(1px 1px at 400px 10px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 450px 70px, #fff, transparent),
         radial-gradient(1px 1px at 500px 30px, rgba(255,255,255,0.6), transparent),
         radial-gradient(2px 2px at 550px 80px, #eee, transparent),
         radial-gradient(1px 1px at 600px 40px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 650px 10px, #fff, transparent),
         radial-gradient(1px 1px at 700px 60px, rgba(255,255,255,0.6), transparent),
         radial-gradient(2px 2px at 750px 20px, #eee, transparent),
         radial-gradient(1px 1px at 800px 90px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 850px 50px, #fff, transparent),
         radial-gradient(1px 1px at 900px 10px, rgba(255,255,255,0.6), transparent);
       background-repeat: repeat;
       background-size: 200px 100px, 300px 150px, 400px 200px, 500px 250px, 600px 300px, 700px 350px, 800px 400px, 900px 450px, 1000px 500px, 1100px 550px, 1200px 600px, 1300px 650px, 1400px 700px, 1500px 750px, 1600px 800px, 1700px 850px, 1800px 900px, 1900px 950px, 2000px 1000px, 2100px 1050px;
       animation: twinkle 4s ease-in-out infinite alternate;
       pointer-events: none;
       z-index: 1;
     }

     @keyframes twinkle {
       0% { opacity: 0.3; }
       100% { opacity: 1; }
     }

     /* Galaxy Nebula Effects */
     body::after {
       content: '';
       position: fixed;
       top: 0;
       left: 0;
       width: 100%;
       height: 100%;
       background-image: 
         radial-gradient(ellipse 400px 200px at 10% 20%, rgba(138, 43, 226, 0.1) 0%, transparent 70%),
         radial-gradient(ellipse 300px 150px at 90% 80%, rgba(255, 20, 147, 0.08) 0%, transparent 70%),
         radial-gradient(ellipse 500px 250px at 30% 70%, rgba(0, 191, 255, 0.06) 0%, transparent 70%),
         radial-gradient(ellipse 350px 175px at 70% 30%, rgba(255, 105, 180, 0.07) 0%, transparent 70%);
       background-size: 100% 100%;
       animation: nebulaFloat 15s ease-in-out infinite;
       pointer-events: none;
       z-index: 2;
     }

     @keyframes nebulaFloat {
       0%, 100% { 
         transform: translate(0, 0) rotate(0deg);
         opacity: 0.6;
       }
       25% { 
         transform: translate(-20px, -10px) rotate(1deg);
         opacity: 0.8;
       }
       50% { 
         transform: translate(10px, -20px) rotate(-1deg);
         opacity: 0.7;
       }
       75% { 
         transform: translate(-10px, 15px) rotate(0.5deg);
         opacity: 0.9;
       }
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
       background: rgba(0, 0, 0, 0.8);
       backdrop-filter: blur(20px);
       border: 2px solid transparent;
       border-radius: 20px;
       position: relative;
       overflow: hidden;
       padding-top: 40px; /* Extra space for profile images and degrees below */
     }

     .chart-container::before {
       content: '';
       position: absolute;
       top: 0;
       left: 0;
       right: 0;
       bottom: 0;
       border-radius: 20px;
       padding: 2px;
       background: linear-gradient(45deg, #ff006e, #00f5ff, #8338ec, #3a86ff, #06ffa5);
       background-size: 400% 400%;
       animation: gradientBorder 3s ease infinite;
       -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
       -webkit-mask-composite: exclude;
       mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
       mask-composite: exclude;
     }

     @keyframes gradientBorder {
       0%, 100% { background-position: 0% 50%; }
       50% { background-position: 100% 50%; }
     }

     .welcome-card {
       background: rgba(0, 0, 0, 0.8);
       backdrop-filter: blur(15px);
       border: 2px solid #ff006e;
       border-radius: 20px;
       box-shadow: 0 0 30px rgba(255, 0, 110, 0.3);
       position: relative;
       overflow: hidden;
     }

     .welcome-card::before {
       content: '';
       position: absolute;
       top: -50%;
       left: -50%;
       width: 200%;
       height: 200%;
       background: linear-gradient(45deg, transparent, rgba(255, 0, 110, 0.1), transparent);
       animation: welcomeShine 3s infinite;
     }

     @keyframes welcomeShine {
       0% { transform: rotate(0deg); }
       100% { transform: rotate(360deg); }
     }

     .profile-image {
       border: 4px solid #00ffff;
       box-shadow: 
         0 0 20px rgba(0, 255, 255, 0.5),
         inset 0 0 20px rgba(0, 255, 255, 0.1);
       animation: profilePulse 2s infinite;
       transition: all 0.3s ease;
     }

     .profile-image:hover {
       transform: scale(1.1);
       box-shadow: 
         0 0 30px rgba(0, 255, 255, 0.8),
         inset 0 0 30px rgba(0, 255, 255, 0.2);
     }

     @keyframes profilePulse {
       0%, 100% { 
         box-shadow: 
           0 0 20px rgba(0, 255, 255, 0.5),
           inset 0 0 20px rgba(0, 255, 255, 0.1);
       }
       50% { 
         box-shadow: 
           0 0 30px rgba(0, 255, 255, 0.8),
           inset 0 0 30px rgba(0, 255, 255, 0.2);
       }
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

        .btn-primary.active {
            background: linear-gradient(45deg, #10b981, #059669);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-primary.active:hover {
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.4);
        }

    .decoration {
      position: absolute;
      pointer-events: none;
      z-index: 1;
    }

     .decoration-icon {
       color: #fff;
       font-size: 2rem;
       animation: galaxyFloat 6s ease-in-out infinite;
       opacity: 0.7;
       text-shadow: 0 0 10px currentColor;
     }
    
     @keyframes galaxyFloat {
       0%, 100% { 
         transform: translateY(0px) rotate(0deg) scale(0.8); 
         opacity: 0.3; 
       }
       25% { 
         transform: translateY(-15px) rotate(90deg) scale(1.1); 
         opacity: 0.8; 
       }
       50% { 
         transform: translateY(-10px) rotate(180deg) scale(0.9); 
         opacity: 0.6; 
       }
       75% { 
         transform: translateY(-20px) rotate(270deg) scale(1.2); 
         opacity: 0.9; 
       }
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

   <!-- Galaxy Floating Particles -->
   <div id="particles-container"></div>
   
   <!-- Galaxy Decorative elements -->
   <div class="decoration decoration-1">
     <span class="decoration-icon">⭐</span>
   </div>
   <div class="decoration decoration-2">
     <span class="decoration-icon">🌟</span>
   </div>
   <div class="decoration decoration-3">
     <span class="decoration-icon">✨</span>
   </div>
   <div class="decoration decoration-4">
     <span class="decoration-icon">💫</span>
   </div>
   <div class="decoration decoration-5">
     <span class="decoration-icon">🌠</span>
   </div>
   <div class="decoration decoration-6">
     <span class="decoration-icon">🚀</span>
   </div>
   <div class="decoration decoration-7">
     <span class="decoration-icon">🛸</span>
   </div>
   <div class="decoration decoration-8">
     <span class="decoration-icon">🌌</span>
   </div>
   <div class="decoration decoration-9">
     <span class="decoration-icon">🔮</span>
   </div>
   <div class="decoration decoration-10">
     <span class="decoration-icon">💎</span>
   </div>
   <div class="decoration decoration-11">
     <span class="decoration-icon">⚡</span>
   </div>
   <div class="decoration decoration-12">
     <span class="decoration-icon">🔥</span>
   </div>

  <!-- Navbar -->
  <nav class="nav-glass px-6 py-4 flex justify-between items-center">
    <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      ⚡ إبداع
    </span>
    <div class="space-x-2 space-x-reverse">
        <?php if ($role === 'student'): ?>
            <a href="<?= url('dashboard') ?>" class="btn-primary active">
              <i class="fas fa-chart-bar"></i>
              الترتيب
            </a>
            <a href="<?= url('questions') ?>" class="btn-primary relative">
              <i class="fas fa-question-circle"></i>
              الأسئلة
              <?php
              // Get unread notifications count
              $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE student_id = ? AND is_read = 0");
              $stmt->execute([$_SESSION['user']['id']]);
              $notification_count = $stmt->fetch()['count'];
              if ($notification_count > 0): ?>
                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center animate-pulse">
                  <?= $notification_count ?>
                </span>
              <?php endif; ?>
            </a>
            <a href="<?= url('profile') ?>" class="btn-primary">
              <i class="fas fa-user"></i>
              حسابي
            </a>
        <?php endif; ?> 

        <?php if ($role === 'admin'): ?>
            <a href="<?= url('admin') ?>" class="btn-primary active">
              <i class="fas fa-users"></i>
              المجموعات
            </a>
            <a href="<?= url('admin.questions') ?>" class="btn-primary">
              <i class="fas fa-question-circle"></i>
              الأسئلة
            </a>
            <a href="<?= url('admin.invitations') ?>" class="btn-primary relative">
              <i class="fas fa-envelope"></i>
              الدعوات
              <?php
              // Get pending invitations count
              $admin_username = $_SESSION['user']['username'] ?? '';
              if (!empty($admin_username)) {
                  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_invitations WHERE invited_username = ? AND status = 'pending'");
                  $stmt->execute([$admin_username]);
                  $invitation_count = $stmt->fetch()['count'];
              } else {
                  $invitation_count = 0;
              }
              if ($invitation_count > 0): ?>
                <span class="absolute -top-2 -right-2 bg-orange-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center animate-pulse">
                  <?= $invitation_count ?>
                </span>
              <?php endif; ?>
            </a>
            <a href="profile" class="btn-primary">
              <i class="fas fa-user"></i>
              حسابي
            </a>
        <?php endif; ?>
    </div>
  </nav>

  <div class="container mx-auto p-8 relative z-10">
    <!-- صورة الحساب -->
    <?php if (isset($profile_image)): ?>
      <div class="flex justify-center -mt-4">
        <img src="/ebdaa/uploads/<?= htmlspecialchars($profile_image); ?>" 
             alt="Profile Image" 
             class="w-36 h-36 rounded-full profile-image floating">
      </div>
    <?php endif; ?> 

    <!-- الترحيب -->
    <?php if ($_SESSION['user']['role'] === 'student'): ?>
       <div class="welcome-card text-center mt-8 p-8 bounce-in relative z-10">
         <h2 class="text-5xl font-bold mb-4" style="color: #ff006e; text-shadow: 0 0 20px rgba(255, 0, 110, 0.5);">
           مرحباً أيها البطل <span class="text-6xl" style="color: #00ffff; text-shadow: 0 0 20px rgba(0, 255, 255, 0.5);"><?= htmlspecialchars($_SESSION['user']['name']); ?></span>! ⚡
         </h2>
         <p class="text-xl mb-4" style="color: #8338ec;">دعنا نرى من الأبطال في مجموعتك! 🏆</p>
         <div class="flex justify-center space-x-4 space-x-reverse text-3xl">
           <span style="color: #00ffff; filter: drop-shadow(0 0 10px currentColor);">⚡</span>
           <span style="color: #ff006e; filter: drop-shadow(0 0 10px currentColor);">🔥</span>
           <span style="color: #8338ec; filter: drop-shadow(0 0 10px currentColor);">🎮</span>
           <span style="color: #06ffa5; filter: drop-shadow(0 0 10px currentColor);">🏆</span>
           <span style="color: #3a86ff; filter: drop-shadow(0 0 10px currentColor);">🚀</span>
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
   // Create floating galaxy particles
   function createGalaxyParticles() {
     const container = document.getElementById('particles-container');
     for (let i = 0; i < 80; i++) {
       const particle = document.createElement('div');
       particle.className = 'particle';
       particle.style.position = 'fixed';
       particle.style.width = Math.random() * 4 + 1 + 'px';
       particle.style.height = particle.style.width;
       particle.style.background = `hsl(${Math.random() * 60 + 200}, 70%, 70%)`;
       particle.style.borderRadius = '50%';
       particle.style.left = Math.random() * 100 + '%';
       particle.style.top = Math.random() * 100 + '%';
       particle.style.pointerEvents = 'none';
       particle.style.zIndex = '1';
       particle.style.boxShadow = `0 0 ${Math.random() * 10 + 5}px currentColor`;
       particle.style.animation = `galaxyParticle ${Math.random() * 10 + 10}s linear infinite`;
       particle.style.animationDelay = Math.random() * 10 + 's';
       
       container.appendChild(particle);
     }
   }

   // Add galaxy particle animation
   const style = document.createElement('style');
   style.textContent = `
     @keyframes galaxyParticle {
       0% {
         transform: translateY(100vh) translateX(0px) rotate(0deg);
         opacity: 0;
       }
       10% {
         opacity: 1;
       }
       90% {
         opacity: 1;
       }
       100% {
         transform: translateY(-100px) translateX(${Math.random() * 200 - 100}px) rotate(360deg);
         opacity: 0;
       }
     }
   `;
   document.head.appendChild(style);

   // Initialize particles
   createGalaxyParticles();

   // Performance monitoring
   const startTime = performance.now();
  
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

   // Gaming Colors for Epic Look
   const gamingColors = [
     { start: '#ff006e', end: '#8338ec' }, // Pink to Purple
     { start: '#00f5ff', end: '#0099ff' }, // Cyan to Blue  
     { start: '#06ffa5', end: '#00cc88' }, // Green to Teal
     { start: '#ffbe0b', end: '#fb8500' }, // Yellow to Orange
     { start: '#8338ec', end: '#3a86ff' }, // Purple to Blue
     { start: '#ff006e', end: '#ff4081' }, // Pink variations
     { start: '#00ffff', end: '#40e0d0' }, // Cyan variations
     { start: '#32cd32', end: '#00ff7f' }, // Green variations
     { start: '#ff1493', end: '#ff69b4' }, // Pink variations
     { start: '#1e90ff', end: '#00bfff' }  // Blue variations
   ];

   // Create epic gradients
   function createEpicGradient(colorPair, index) {
     const gradient = ctx.createLinearGradient(0, 0, 0, 400);
     gradient.addColorStop(0, colorPair.start);
     gradient.addColorStop(0.5, colorPair.end);
     gradient.addColorStop(1, colorPair.start + '80'); // Add transparency
     return gradient;
   }

  const barColors = data.map((_, i) => {
    const colorPair = gamingColors[i % gamingColors.length];
    return createEpicGradient(colorPair, i);
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
      img.src = `/ebdaa/uploads/${imageName}`;
    });
  }) : [];

  // Animation frame for fire effect
  let animationFrame = 0;
  
   // Enhanced Profile Images Plugin with Gaming Effects
   const profileImagePlugin = {
     id: 'profileImages',
     afterDatasetsDraw: (chart) => {
       const { ctx, data, chartArea } = chart;
       const meta = chart.getDatasetMeta(0);
       
       meta.data.forEach((bar, index) => {
         if (loadedImages[index]) {
           const x = bar.x;
           const y = bar.y - 20; // Position above the bar
           const imageSize = 70; // Size of the profile image
           
           // Gaming glow effect
           ctx.save();
           ctx.shadowColor = gamingColors[index % gamingColors.length].start;
           ctx.shadowBlur = 20;
           
           // Draw neon border
           ctx.beginPath();
           ctx.arc(x, y, imageSize/2 + 6, 0, 2 * Math.PI);
           ctx.strokeStyle = gamingColors[index % gamingColors.length].start;
           ctx.lineWidth = 4;
           ctx.stroke();
           
           // Draw inner glow
           ctx.beginPath();
           ctx.arc(x, y, imageSize/2 + 2, 0, 2 * Math.PI);
           ctx.strokeStyle = '#ffffff';
           ctx.lineWidth = 2;
           ctx.stroke();
           
           // Clip and draw image
           ctx.beginPath();
           ctx.arc(x, y, imageSize/2, 0, 2 * Math.PI);
           ctx.clip();
           
           const img = loadedImages[index];
           const imgX = x - imageSize/2;
           const imgY = y - imageSize/2;
           ctx.drawImage(img, imgX, imgY, imageSize, imageSize);
           
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
           const medals = ['🥇', '🥈', '🥉'];
           if (i < 3) {
             return medals[i] + ' ' + name;
           }
           return '⚡ ' + name;
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
               color: 'rgba(0, 255, 255, 0.2)',
               lineWidth: 2,
               drawBorder: true,
               borderColor: 'rgba(0, 255, 255, 0.5)'
             },
             ticks: {
               color: '#00ffff',
               font: { size: 16, weight: 'bold' },
               callback: function(value) {
                 return value + ' ⚡';
               },
               padding: 10
             }
           },
           x: {
             grid: { 
               display: false 
             },
             ticks: {
               color: '#ffffff',
               font: function(context) {
                 return { 
                   size: context.index < 3 ? 18 : 14, 
                   weight: 'bold'
                 };
               },
               maxRotation: 45,
               minRotation: 0,
               padding: 15
             }
           }
         },
      plugins: {
         title: {
           display: true,
           text: '⚡ سباق الأبطال الملحمي - من سيفوز بالمركز الأول؟ 🏆',
           font: { size: 32, weight: 'bold', family: 'Cairo' },
           color: '#00ffff',
           padding: 30
         },
        legend: { 
          display: false 
        },  
         tooltip: {
           backgroundColor: 'rgba(0, 0, 0, 0.9)',
           titleColor: '#00ffff',
           bodyColor: '#ffffff',
           borderColor: '#ff006e',
           borderWidth: 3,
           cornerRadius: 15,
           titleFont: { size: 18, weight: 'bold' },
           bodyFont: { size: 16, weight: 'bold' },
           callbacks: {
             title: function(context) {
               const index = context[0].dataIndex;
               const titles = ['🥇 البطل الذهبي', '🥈 البطل الفضي', '🥉 البطل البرونزي'];
               if (index < 3) {
                 return titles[index];
               }
               return '⚡ ' + context[0].label;
             },
             label: function(context) {
               return '💪 النقاط: ' + context.parsed.y + ' ⚡';
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
  
  // Performance optimization
  chart.canvas.style.willChange = 'transform';
  chart.canvas.style.transform = 'translateZ(0)';
  
  // Performance monitoring
  const endTime = performance.now();
  console.log(`Chart rendered in ${(endTime - startTime).toFixed(2)}ms`);
  
  });
</script>

<!-- Load optimized JavaScript -->
<script src="/assets/js/simple-optimized.js"></script>

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