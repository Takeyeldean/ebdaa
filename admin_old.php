<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "includes/db.php";
// email
// التأكد أن الأدمن مسجل دخول
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['user']['id']; 

// البحث
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
  
// جلب الجروبات اللي هذا الأدمن مشرف عليها
$sql = "
    SELECT g.id, g.name, g.numStudt
    FROM groups g
    INNER JOIN group_admins ga ON g.id = ga.group_id
    WHERE ga.admin_id = :admin_id
";

if ($search !== "") {
    $sql .= " AND g.name LIKE :search";
}

$stmt = $conn->prepare($sql);
$stmt->bindParam(":admin_id", $admin_id, PDO::PARAM_INT);

if ($search !== "") {
    $likeSearch = "%$search%";
    $stmt->bindParam(":search", $likeSearch, PDO::PARAM_STR);
}

$stmt->execute();
$groups = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إبداع - لوحة الإدارة 🎯</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="assets/css/beautiful-design.css" rel="stylesheet">
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

    .nav-glass {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 0 0 25px 25px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .welcome-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .group-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .group-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
      background-size: 200% 100%;
      animation: gradientShift 3s ease infinite;
    }

    .group-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
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

    .btn-success {
      background: linear-gradient(45deg, #4CAF50, #45a049);
      color: white;
      padding: 10px 20px;
      border-radius: 20px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(76, 175, 80, 0.4);
    }

    .btn-info {
      background: linear-gradient(45deg, #2196F3, #1976D2);
      color: white;
      padding: 10px 20px;
      border-radius: 20px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 6px 20px rgba(33, 150, 243, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-info:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(33, 150, 243, 0.4);
    }

    .add-card {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      border: 2px dashed #667eea;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 200px;
    }

    .add-card:hover {
      transform: translateY(-5px);
      border-color: #764ba2;
      background: rgba(255, 255, 255, 0.9);
    }

    .search-box {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(15px);
      border-radius: 25px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .search-input {
      background: transparent;
      border: none;
      outline: none;
      padding: 15px 20px;
      font-size: 16px;
      font-family: 'Cairo', sans-serif;
      width: 100%;
    }

    .search-input::placeholder {
      color: #666;
    }

    .search-btn {
      background: linear-gradient(45deg, #1e40af, #3b82f6);
      color: white;
      border: none;
      padding: 15px 25px;
      border-radius: 24px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 6px 20px rgba(30, 64, 175, 0.3);
    }

    .search-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(30, 64, 175, 0.4);
    }

    .decoration {
      position: absolute;
      pointer-events: none;
      z-index: 1;
    }

    .decoration-icon {
      color: #f59e0b;
      font-size: 1.5rem;
      animation: twinkle 1s ease-in-out infinite;
    }

    @keyframes twinkle {
      0%, 100% { opacity: 0.3; transform: scale(1); }
      50% { opacity: 1; transform: scale(1.2); }
    }

    .decoration-1 { top: 5%; left: 5%; }
    .decoration-2 { top: 10%; right: 8%; }
    .decoration-3 { bottom: 15%; left: 10%; }
    .decoration-4 { bottom: 8%; right: 5%; }
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

  <!-- Navbar -->
  <nav class="nav-glass px-6 py-4 flex justify-between items-center">
    
    <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      ⚡ إبداع
    </span>

    <div class="space-x-2 space-x-reverse">
      <a href="admin.php" class="btn-primary active">
        <i class="fas fa-users"></i>
        المجموعات
      </a>
      <a href="admin_questions.php" class="btn-primary">
        <i class="fas fa-question-circle"></i>
        الأسئلة
      </a>
      <a href="admin_invitations.php" class="btn-primary relative">
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
      <a href="profile.php" class="btn-primary">
        <i class="fas fa-user"></i>
        حسابي
      </a>
    </div>
  </nav>

  <div class="container mx-auto mt-8 px-4 relative z-10">

    <!-- Welcome Card -->
    <div class="welcome-card text-center p-8 mb-8 bounce-in">
      <h1 class="text-5xl font-bold mb-4" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
        أهلاً وسهلاً يا <span class="text-6xl"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>! ⚡
      </h1>
      <p class="text-xl text-gray-600 mb-4">دعنا ندير مجموعاتك بطريقة ممتعة! 🚀</p>
      <div class="flex justify-center space-x-4 space-x-reverse">
        <span class="text-2xl">👨‍🏫</span>
        <span class="text-2xl">⚡</span>
        <span class="text-2xl">🎯</span>
        <span class="text-2xl">🏆</span>
        <span class="text-2xl">🔥</span>
      </div>
    </div>

    <!-- Search Box -->
    <form method="get" class="mb-8">
      <div class="search-box flex items-center">
        <i class="fas fa-search text-gray-400 mx-4"></i>
        <input type="text" name="search" placeholder="🔍 ابحث عن المجموعة..." 
               value="<?php echo htmlspecialchars($search); ?>" 
               class="search-input">
        <button type="submit" class="search-btn">
          <i class="fas fa-search"></i>
          بحث
        </button>
      </div>
    </form>

    <h2 class="text-3xl mb-6 font-bold text-center" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      مجموعاتك المميزة ⚡
    </h2>
<?php if ($groups): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($groups as $index => $group): ?>
        <div class="group-card p-6 floating" style="animation-delay: <?= $index * 0.2 ?>s;">
            <div class="text-center mb-4">
                <div class="text-4xl mb-2">⚡</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($group['name']); ?></h3>
                <p class="text-lg text-gray-600">عدد الطلاب: <span class="font-bold text-blue-600"><?php echo $group['numStudt']; ?></span></p>
            </div>
            
            <div class="flex flex-col gap-3">
                <a href="manage_group.php?group_id=<?= $group['id'] ?>" class="btn-success">
                    <i class="fas fa-cogs"></i>
                    إدارة المجموعة
                </a>
                <a href="dashboard.php?group_id=<?= $group['id'] ?>" class="btn-info">
                    <i class="fas fa-chart-bar"></i>
                    عرض الدرجات
                </a>
            </div>
        </div>
    <?php endforeach; ?>

        <!-- زر إضافة مجموعة -->
        <div class="add-card floating">
            <div class="text-center">
                <div class="text-6xl mb-4">⚡</div>
                <h3 class="text-xl font-bold text-gray-700 mb-4">إضافة مجموعة جديدة</h3>
                <a href="add_group.php" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    إضافة مجموعة
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="text-center">
        <div class="welcome-card p-12">
            <div class="text-8xl mb-6">⚡</div>
            <h3 class="text-3xl font-bold text-gray-700 mb-4">لا توجد مجموعات بعد!</h3>
            <p class="text-xl text-gray-600 mb-8">دعنا نبدأ بإنشاء مجموعتك الأولى! 🚀</p>
            <a href="add_group.php" class="btn-primary text-xl px-8 py-4">
                <i class="fas fa-plus"></i>
                إنشاء مجموعة جديدة
            </a>
        </div>
    </div>
<?php endif; ?>


  </div>

</body>
</html>
