<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";
require_once "includes/url_helper.php";
// username
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

$group_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['group_id']) ? intval($_GET['group_id']) : 0);
if ($group_id == 0) die("Group not found!");

$stmt = $conn->prepare("SELECT * FROM students WHERE group_id = ?");
$stmt->execute([$group_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إبداع</title>
 <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
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

        .nav-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 0 0 25px 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 10000;
        }

        /* Mobile hamburger menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1e40af;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 10001;
        }

        .mobile-menu-btn:hover {
            background: rgba(30, 64, 175, 0.1);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.2);
        }

        .mobile-menu-btn:active {
            transform: scale(0.95);
        }

        .mobile-nav-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 0 0 25px 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            padding: 20px;
            z-index: 9999;
        }

        .mobile-nav-menu.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        .mobile-nav-menu.active .mobile-nav-links .btn-primary {
            animation: fadeInUp 0.4s ease-out;
            animation-fill-mode: both;
        }

        .mobile-nav-menu.active .mobile-nav-links .btn-primary:nth-child(1) { animation-delay: 0.1s; }
        .mobile-nav-menu.active .mobile-nav-links .btn-primary:nth-child(2) { animation-delay: 0.2s; }
        .mobile-nav-menu.active .mobile-nav-links .btn-primary:nth-child(3) { animation-delay: 0.3s; }
        .mobile-nav-menu.active .mobile-nav-links .btn-primary:nth-child(4) { animation-delay: 0.4s; }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-nav-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .mobile-nav-links .btn-primary {
            justify-content: center;
            width: 100%;
            padding: 16px 24px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .mobile-nav-links .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .mobile-nav-links .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
            background: linear-gradient(45deg, #1e3a8a, #2563eb);
        }

        .mobile-nav-links .btn-primary:hover::before {
            left: 100%;
        }

        .mobile-nav-links .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.2);
        }

        /* Enhanced Mobile responsiveness */
        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .container {
                padding: 6px;
            }
            
            .nav-glass {
                padding: 10px 14px;
            }

            /* Significantly smaller text on mobile */
            .text-4xl {
                font-size: 1.5rem !important; /* 24px instead of 36px */
            }

            .text-3xl {
                font-size: 1.125rem !important; /* 18px instead of 30px */
            }

            .text-2xl {
                font-size: 1rem !important; /* 16px instead of 24px */
            }

            .text-xl {
                font-size: 0.9rem !important; /* 14.4px instead of 20px */
            }

            .text-lg {
                font-size: 0.875rem !important; /* 14px instead of 18px */
            }

            /* Card adjustments - much smaller */
            .card {
                padding: 8px !important;
                margin-bottom: 8px !important;
            }

            /* Form elements - compact */
            .form-group {
                margin-bottom: 8px !important;
            }

            .form-group label {
                font-size: 0.8rem !important;
                margin-bottom: 2px !important;
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 8px 10px !important;
                font-size: 0.8rem !important;
            }

            /* Buttons - much smaller (except navigation buttons) */
            .btn-primary:not(.mobile-nav-links .btn-primary) {
                padding: 8px 12px !important;
                font-size: 0.8rem !important;
                margin-bottom: 6px !important;
            }

            .btn-success, .btn-info, .btn-danger {
                padding: 6px 10px !important;
                font-size: 0.75rem !important;
            }

            /* Student cards - very compact */
            .student-card {
                padding: 8px !important;
                margin-bottom: 8px !important;
            }

            /* Message form - compact */
            .message-form {
                padding: 8px !important;
            }

            /* Emoji selector - smaller */
            .emoji-selector {
                padding: 6px !important;
                gap: 2px !important;
                grid-template-columns: repeat(6, 1fr) !important;
            }

            .emoji-btn {
                padding: 4px !important;
                font-size: 1rem !important;
            }

            /* Table responsive - smaller cells */
            table th, table td {
                padding: 6px 4px !important;
                font-size: 0.75rem !important;
            }

            /* Reduce margins and padding globally */
            .mb-8 { margin-bottom: 12px !important; }
            .mb-6 { margin-bottom: 8px !important; }
            .mb-4 { margin-bottom: 6px !important; }
            .mb-3 { margin-bottom: 4px !important; }
            .mb-2 { margin-bottom: 3px !important; }
            .mb-1 { margin-bottom: 2px !important; }

            .p-8 { padding: 10px !important; }
            .p-6 { padding: 8px !important; }
            .p-4 { padding: 6px !important; }
            .p-3 { padding: 4px !important; }
            .p-2 { padding: 3px !important; }

            /* Modal improvements - smaller */
            .modal-content {
                margin: 8px !important;
                padding: 12px !important;
                max-height: 85vh !important;
                overflow-y: auto;
            }

            /* Input fields in modals */
            .modal-content input,
            .modal-content textarea,
            .modal-content select {
                padding: 6px 8px !important;
                font-size: 0.8rem !important;
            }

            /* Smaller icons */
            .fas, .far {
                font-size: 0.9em !important;
            }

            /* Compact spacing for action buttons */
            .space-x-1 > * + * {
                margin-right: 2px !important;
            }

            .space-x-2 > * + * {
                margin-right: 4px !important;
            }

            /* Smaller notification badges */
            .animate-pulse {
                height: 18px !important;
                width: 18px !important;
                font-size: 0.7rem !important;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 4px !important;
            }
            
            .nav-glass {
                padding: 8px 10px !important;
            }

            .text-4xl {
                font-size: 1.5rem !important; /* 24px */
            }

            .text-3xl {
                font-size: 1rem !important; /* 16px */
            }

            .text-2xl {
                font-size: 0.9rem !important; /* 14.4px */
            }

            .card, .student-card, .message-form {
                padding: 6px !important;
            }

            .btn-primary:not(.mobile-nav-links .btn-primary) {
                padding: 6px 10px !important;
                font-size: 0.75rem !important;
            }

            .btn-success, .btn-info, .btn-danger {
                padding: 5px 8px !important;
                font-size: 0.7rem !important;
            }

            .emoji-selector {
                padding: 4px !important;
                gap: 1px !important;
                grid-template-columns: repeat(5, 1fr) !important;
            }

            .emoji-btn {
                padding: 3px !important;
                font-size: 0.9rem !important;
            }

            table th, table td {
                padding: 4px 2px !important;
                font-size: 0.7rem !important;
            }

            /* Very compact form inputs */
            input, textarea, select {
                padding: 5px 6px !important;
                font-size: 0.75rem !important;
            }
        }

        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none;
            }
            
            .mobile-nav-menu {
                display: none !important;
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

     .btn-danger {
       background: linear-gradient(45deg, #ef4444, #dc2626);
       color: white;
       padding: 12px 24px;
       border-radius: 25px;
       text-decoration: none;
       font-weight: 600;
       transition: all 0.3s ease;
       box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
       display: inline-flex;
       align-items: center;
       gap: 8px;
       border: none;
       cursor: pointer;
     }

     .btn-danger:hover {
       transform: translateY(-3px);
       box-shadow: 0 12px 35px rgba(239, 68, 68, 0.4);
     }

     /* Custom Modal Animations */
     #leaveGroupModal, #deleteStudentModal {
       transition: all 0.3s ease-in-out;
     }
     
     #leaveGroupModal.hidden, #deleteStudentModal.hidden {
       opacity: 0;
       visibility: hidden;
     }
     
     #leaveGroupModal:not(.hidden), #deleteStudentModal:not(.hidden) {
       opacity: 1;
       visibility: visible;
     }
     
     #leaveGroupModal .bg-white, #deleteStudentModal .bg-white {
       transform: scale(0.9);
       transition: transform 0.3s ease-in-out;
     }
     
     #leaveGroupModal:not(.hidden) .bg-white, #deleteStudentModal:not(.hidden) .bg-white {
       transform: scale(1);
     }
     
     /* Button hover effects */
     .modal-button {
       transition: all 0.3s ease;
       transform: translateY(0);
     }
     
     .modal-button:hover {
       transform: translateY(-2px);
       box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
     }
  </style>
</head>
<body>

  <!-- Navbar -->
    <nav class="nav-glass px-6 py-4 flex justify-between items-center relative">
    
    <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      ⚡ إبداع
    </span>

    <!-- Mobile menu button -->
    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
      <i class="fas fa-bars" id="mobile-menu-icon"></i>
    </button>

    <div class="space-x-2 space-x-reverse desktop-nav">
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
      <a href="<?= url('profile') ?>" class="btn-primary">
        <i class="fas fa-user"></i>
        حسابي
      </a>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav-menu" id="mobile-nav-menu">
        <div class="mobile-nav-links">
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
            <a href="<?= url('profile') ?>" class="btn-primary">
                <i class="fas fa-user"></i>
                حسابي
            </a>
        </div>
    </div>
  </nav>

  <div class="container mx-auto p-8">

    <!-- العنوان -->
    <h1 class="text-4xl font-bold text-white mb-8 text-center">إدارة المجموعة</h1>

    <!-- جدول الطلاب -->
    <div class="bg-white shadow-md rounded-2xl p-6 overflow-x-auto mb-12">
      <div class="table-responsive">
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-blue-100 text-blue-800">
              <th class="p-3 text-right"> الطالب</th>
              <th class="p-3 text-center"> الدرجة الحالية</th>
              <th class="p-3 text-center"> إدارة الدرجات</th>
              <th class="p-3 text-center"> إدارة الطالب</th>
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
                      <a href="<?= url('profile.degree') ?>?id=<?= $student['id'] ?>&amount=<?= $inc ?>" class="inline-block bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition">+<?= $inc ?></a>
                    <?php endforeach; ?>

                    <!-- إضافة قيمة مخصصة -->
                    <form action="<?= url('profile.degree') ?>" method="get" class="inline-block mx-2">
                      <input type="hidden" name="id" value="<?= $student['id'] ?>">
                      <input type="number" name="amount" class="w-20 border rounded-lg px-2 py-1 focus:ring-2 focus:ring-blue-400" placeholder="0">
                      <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition">إضافة</button>
                    </form>

                    <!-- أزرار خصم درجات -->
                    <?php foreach ([5,3,2,1] as $dec): ?>
                      <a href="<?= url('profile.degree') ?>?id=<?= $student['id'] ?>&amount=-<?= $dec ?>" class="inline-block bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition">-<?= $dec ?></a>
                    <?php endforeach; ?>
                  </td>
                  <td class="p-3 text-center space-x-2 space-x-reverse">
                    <!-- Move Student Button -->
                    <button onclick="showMoveStudentModal(<?= $student['id'] ?>, '<?= htmlspecialchars($student['name']) ?>')" class="btn-info">
                      <i class="fas fa-exchange-alt"></i>
                      نقل
                    </button>
                    
                    <!-- Delete Student Button -->
                    <button onclick="confirmDeleteStudent(<?= $student['id'] ?>, '<?= htmlspecialchars($student['name']) ?>')" class="btn-danger">
                      <i class="fas fa-trash"></i>
                      حذف
                    </button>
                  </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if (empty($students)): ?>
        <p class="text-gray-600 mt-3 text-center">⚠️ لا يوجد طلاب في هذه المجموعة.</p>
      <?php endif; ?>
    </div>

    <!-- Group Message Section -->
    <div class="bg-white shadow-md rounded-2xl p-8 mb-8">
      <h2 class="text-2xl font-bold text-blue-800 mb-6">💬 رسالة المجموعة</h2>
      
      <?php
      // Get current group message and emoji
      $stmt = $conn->prepare("SELECT message, emoji FROM groups WHERE id = ?");
      $stmt->execute([$group_id]);
      $group = $stmt->fetch(PDO::FETCH_ASSOC);
      $current_message = $group['message'] ?? '';
      $current_emoji = $group['emoji'] ?? '🤖';
      
      ?>
          
          <form method="post" action="<?= url('admin.group.message', ['id' => $group_id]) ?>" class="space-y-4 message-form">
            <input type="hidden" name="group_id" value="<?= $group_id ?>">
            
            <div>
              <label class="block mb-2 font-medium text-gray-700">اكتب رسالة للمجموعة:</label>
              <textarea name="message" rows="4" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 resize-none" placeholder="اكتب رسالة تحفيزية أو تعليمية للمجموعة..."><?= htmlspecialchars($current_message) ?></textarea>
            </div>
            
            <div>
              <label class="block mb-2 font-medium text-gray-700">اختر إيموجي للشخصية:</label>
              <div class="emoji-selector grid grid-cols-8 gap-2 p-4 border rounded-lg bg-gray-50">
                <?php
                $emojis = ['🤖', '👨‍🏫', '👩‍🏫', '🎓', '⚡', '🔥', '💪', '🎯', '🏆', '⭐', '🚀', '💡', '🎮', '⚽', '🏀', '🎨', '🎵', '📚', '🔬', '🎪', '🎭', '👨‍💻', '👩‍💻', '🧑‍🎓', '👨‍🎓', '👩‍🎓', '🧑‍🏫', '👨‍🔬', '👩‍🔬', '🧑‍💼', '👨‍💼', '👩‍💼'];
                foreach ($emojis as $emoji): ?>
                  <button type="button" class="emoji-btn text-2xl p-2 rounded-lg hover:bg-blue-200 transition <?= $emoji === $current_emoji ? 'bg-blue-300 border-2 border-blue-500' : 'bg-white border border-gray-300' ?>" data-emoji="<?= $emoji ?>">
                    <?= $emoji ?>
                  </button>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="emoji" id="selected_emoji" value="<?= htmlspecialchars($current_emoji) ?>">
            </div>
            
            <div class="flex gap-3">
              <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow-md">
                💾 حفظ الرسالة
              </button>
            
            </div>
          </form>
          
      <?php if (!empty($current_message)): ?>
        <div class="mt-4 p-4 bg-blue-50 rounded-lg border-r-4 border-blue-500">
          <h3 class="font-bold text-blue-800 mb-2">الرسالة الحالية:</h3>
          <p class="text-gray-700"><?= htmlspecialchars($current_message) ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Admin Invitation Section -->
    <div class="bg-white shadow-md rounded-2xl p-8 mb-8">
      <h2 class="text-2xl font-bold text-blue-800 mb-6">👥 دعوة مشرف جديد</h2>
      
      <!-- Success/Error Messages -->
      <?php if (!empty($_SESSION['invite_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          <?= $_SESSION['invite_error']; unset($_SESSION['invite_error']); ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['invite_success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          <?= $_SESSION['invite_success']; unset($_SESSION['invite_success']); ?>
        </div>
      <?php endif; ?>
      
      <form method="post" action="<?= url('admin.group.invite', ['id' => $group_id]) ?>" class="space-y-4">
        <input type="hidden" name="group_id" value="<?= $group_id ?>">
        
        <div>
          <label class="block mb-2 font-medium text-gray-700">اسم المستخدم للمشرف:</label>
          <input type="text" name="admin_username" placeholder="أدخل اسم المستخدم للمشرف" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
        </div>
        
        <button type="submit" name="invite_admin" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition shadow-md">
          📤 إرسال الدعوة
        </button>
      </form>
      
      <!-- Current Group Admins -->
      <?php
      $stmt = $conn->prepare("
        SELECT a.name, a.username 
        FROM admins a 
        JOIN group_admins ga ON a.id = ga.admin_id 
        WHERE ga.group_id = ?
      ");
      $stmt->execute([$group_id]);
      $current_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>
      
      <?php if (!empty($current_admins)): ?>
        <div class="mt-6">
          <h3 class="font-bold text-blue-800 mb-3">المشرفون الحاليون:</h3>
          <div class="space-y-2">
            <?php foreach ($current_admins as $admin): ?>
              <div class="flex items-center justify-between bg-blue-50 p-3 rounded-lg">
                <div class="flex items-center gap-3">
                  <i class="fas fa-user-shield text-blue-600"></i>
                  <span class="font-medium"><?= htmlspecialchars($admin['name']) ?></span>
                  <span class="text-gray-500">(@<?= htmlspecialchars($admin['username']) ?>)</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      
      <!-- Pending Invitations -->
      <?php
      $stmt = $conn->prepare("
        SELECT ai.*, a.name as inviter_name 
        FROM admin_invitations ai 
        JOIN admins a ON ai.inviter_admin_id = a.id 
        WHERE ai.group_id = ? AND ai.status = 'pending'
        ORDER BY ai.created_at DESC
      ");
      $stmt->execute([$group_id]);
      $pending_invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>
      
      <?php if (!empty($pending_invitations)): ?>
        <div class="mt-6">
          <h3 class="font-bold text-orange-800 mb-3">الدعوات المعلقة:</h3>
          <div class="space-y-2">
            <?php foreach ($pending_invitations as $invitation): ?>
              <div class="flex items-center justify-between bg-orange-50 p-3 rounded-lg">
                <div class="flex items-center gap-3">
                  <i class="fas fa-clock text-orange-600"></i>
                  <span class="font-medium">@<?= htmlspecialchars($invitation['invited_username']) ?></span>
                  <span class="text-gray-500">دعوة من: <?= htmlspecialchars($invitation['inviter_name']) ?></span>
                </div>
                <span class="text-sm text-gray-500">
                  <?= date('Y-m-d H:i', strtotime($invitation['created_at'])) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
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

  <form method="post" action="<?= url('admin.add-student') ?>" class="space-y-5">
    <input type="hidden" name="group_id" value="<?= $group_id ?>">
    
    <div class="form-group">
      <label class="block mb-1 font-medium text-gray-700">اسم الطالب:</label>
      <input type="text" placeholder="الإسم" name="name" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div class="form-group">
      <label class="block mb-1 font-medium text-gray-700">اسم المستخدم:</label>
      <input type="username" placeholder="اسم المستخدم" name="username" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div class="form-group">
      <label class="block mb-1 font-medium text-gray-700">كلمة المرور:</label>
      <input type="password" name="password" placeholder="كلمة المرور" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div class="form-group">
      <label class="block mb-1 font-medium text-gray-700">تأكيد كلمة المرور:</label>
      <input type="password" name="confirm_password" placeholder="أعد كتابة كلمة المرور" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <button type="submit" name="add_student" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition shadow-md">
      إضافة الطالب
    </button>
  </form>
</div>

    <!-- Leave Group Section -->
    <div class="p-6">

      <?php
      // Check if there are other admins in this group
      $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM group_admins WHERE group_id = ? AND admin_id != ?");
      $stmt->execute([$group_id, $_SESSION['user']['id']]);
      $other_admins_count = $stmt->fetch()['admin_count'];
      
      // Get group name for display
      $stmt = $conn->prepare("SELECT name FROM groups WHERE id = ?");
      $stmt->execute([$group_id]);
      $group_name = $stmt->fetch()['name'];
      ?>
      
      <div class="flex items-center gap-4">
        <button onclick="confirmLeaveGroup()" class="btn-danger">
          <i class="fas fa-sign-out-alt"></i>
          مغادرة المجموعة "<?= htmlspecialchars($group_name) ?>"
        </button>
      </div>
    </div>

    <!-- Leave Group Confirmation Modal -->
    <div id="leaveGroupModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" onclick="closeLeaveGroupModal()" style="z-index: 9999;">
      <div class="bg-white rounded-2xl p-8 max-w-lg w-full mx-4 modal-content" onclick="event.stopPropagation()">
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">تأكيد مغادرة المجموعة</h3>
          <p id="leaveGroupMessage" class="text-gray-600 mb-8 text-lg leading-relaxed"></p>
          
          <div class="flex gap-4 justify-center">
            <button onclick="closeLeaveGroupModal()" class="modal-button bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-lg">
              <i class="fas fa-times"></i>
              إلغاء
            </button>
            <button onclick="submitLeaveGroup()" class="modal-button bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg">
              <i class="fas fa-sign-out-alt"></i>
              تأكيد المغادرة
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Student Confirmation Modal -->
    <div id="deleteStudentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" onclick="closeDeleteStudentModal()">
      <div class="bg-white rounded-2xl p-8 max-w-lg w-full mx-4 modal-content" onclick="event.stopPropagation()">
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <i class="fas fa-trash-alt text-red-600 text-2xl"></i>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">تأكيد حذف الطالب</h3>
          <p id="deleteStudentMessage" class="text-gray-600 mb-8 text-lg leading-relaxed"></p>
          
          <div class="flex gap-4 justify-center">
            <button onclick="closeDeleteStudentModal()" class="modal-button bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-lg">
              <i class="fas fa-times"></i>
              إلغاء
            </button>
            <button onclick="submitDeleteStudent()" class="modal-button bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg">
              <i class="fas fa-trash-alt"></i>
              تأكيد الحذف
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Move Student Modal -->
    <div id="moveStudentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
      <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 modal-content">
        <h3 class="text-2xl font-bold text-blue-800 mb-6">نقل الطالب</h3>
        
        <form id="moveStudentForm" method="post" action="">
          <input type="hidden" id="moveStudentId" name="student_id">
          <input type="hidden" name="current_group_id" value="<?= $group_id ?>">
          
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">الطالب:</label>
            <p id="moveStudentName" class="text-lg font-semibold text-gray-800"></p>
          </div>
          
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">اختر المجموعة الجديدة:</label>
            <select name="new_group_id" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
              <option value="">اختر المجموعة...</option>
              <?php
              // Get all groups
              $stmt = $conn->prepare("SELECT id, name FROM groups ORDER BY name");
              $stmt->execute();
              $all_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
              foreach ($all_groups as $group): ?>
                <option value="<?= $group['id'] ?>" <?= $group['id'] == $group_id ? 'disabled' : '' ?>>
                  <?= htmlspecialchars($group['name']) ?>
                  <?= $group['id'] == $group_id ? ' (المجموعة الحالية)' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="flex gap-3">
            <button type="submit" class="btn-info flex-1">
              <i class="fas fa-exchange-alt"></i>
              نقل الطالب
            </button>
            <button type="button" onclick="closeMoveStudentModal()" class="btn-danger flex-1">
              <i class="fas fa-times"></i>
              إلغاء
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>

<script>
function clearMessage() {
    if (confirm('هل أنت متأكد من مسح الرسالة؟')) {
        document.querySelector('textarea[name="message"]').value = '';
        // Submit the form to save the empty message
        document.querySelector('form').submit();
    }
}

function clearAll() {
    if (confirm('هل أنت متأكد من مسح الرسالة والإيموجي؟')) {
        document.querySelector('textarea[name="message"]').value = '';
        document.getElementById('selected_emoji').value = '🤖';
        
        // Reset emoji selection visual
        const emojiButtons = document.querySelectorAll('.emoji-btn');
        emojiButtons.forEach(btn => {
            btn.classList.remove('bg-blue-300', 'border-2', 'border-blue-500');
            btn.classList.add('bg-white', 'border', 'border-gray-300');
        });
        
        // Highlight the default emoji
        const defaultBtn = document.querySelector('[data-emoji="🤖"]');
        if (defaultBtn) {
            defaultBtn.classList.remove('bg-white', 'border', 'border-gray-300');
            defaultBtn.classList.add('bg-blue-300', 'border-2', 'border-blue-500');
        }
        
        // Submit the form
        document.querySelector('form').submit();
    }
}

function confirmLeaveGroup() {
    console.log('confirmLeaveGroup function called');
    const groupName = "<?= htmlspecialchars($group_name) ?>";
    const otherAdminsCount = <?= $other_admins_count ?>;
    
    console.log('Group name:', groupName);
    console.log('Other admins count:', otherAdminsCount);
    
    let message = `هل أنت متأكد من مغادرة المجموعة "${groupName}"؟`;
    
    if (otherAdminsCount > 0) {
        message += `<br><br><span class="text-green-600 font-semibold">✅ يوجد ${otherAdminsCount} مشرف آخر في هذه المجموعة</span><br>`;
        message += `<span class="text-gray-700">سيتم إزالتك من المجموعة فقط.</span>`;
    } else {
        message += `<br><br><span class="text-red-600 font-semibold">⚠️ أنت المشرف الوحيد في هذه المجموعة!</span><br><br>`;
        message += `<span class="text-gray-700">سيتم حذف المجموعة بالكامل بما في ذلك:</span><br>`;
        message += `<span class="text-gray-600">• جميع بيانات الطلاب</span><br>`;
        message += `<span class="text-gray-600">• جميع الأسئلة والأجوبة</span><br>`;
        message += `<span class="text-gray-600">• جميع الإحصائيات</span><br><br>`;
        message += `<span class="text-red-600 font-bold">هذا الإجراء لا يمكن التراجع عنه!</span>`;
    }
    
    // Show the custom modal
    document.getElementById('leaveGroupMessage').innerHTML = message;
    document.getElementById('leaveGroupModal').classList.remove('hidden');
}

function closeLeaveGroupModal() {
    document.getElementById('leaveGroupModal').classList.add('hidden');
}

function submitLeaveGroup() {
    console.log('submitLeaveGroup function called');
    // Create a form to submit the leave request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('admin.group.leave', ['id' => $group_id]) ?>';
    
    console.log('Form action:', form.action);
    
    const groupIdInput = document.createElement('input');
    groupIdInput.type = 'hidden';
    groupIdInput.name = 'group_id';
    groupIdInput.value = '<?= $group_id ?>';
    
    console.log('Group ID:', groupIdInput.value);
    
    form.appendChild(groupIdInput);
    document.body.appendChild(form);
    form.submit();
}

function showMoveStudentModal(studentId, studentName) {
    document.getElementById('moveStudentId').value = studentId;
    document.getElementById('moveStudentName').textContent = studentName;
    
    // Set the correct action URL for the form
    const form = document.getElementById('moveStudentForm');
    const baseUrl = '<?= url("admin.group.student.move", ["id" => $group_id, "student_id" => "STUDENT_ID"]) ?>';
    form.action = baseUrl.replace('STUDENT_ID', studentId);
    
    document.getElementById('moveStudentModal').classList.remove('hidden');
}

function closeMoveStudentModal() {
    document.getElementById('moveStudentModal').classList.add('hidden');
    document.getElementById('moveStudentForm').reset();
}

function confirmDeleteStudent(studentId, studentName) {
    const message = `هل أنت متأكد من حذف الطالب "<span class="font-bold text-red-600">${studentName}</span>"؟<br><br>` +
                   `<span class="text-gray-700">سيتم حذف:</span><br>` +
                   `<span class="text-gray-600">• حساب الطالب بالكامل</span><br>` +
                   `<span class="text-gray-600">• جميع الإجابات التي كتبها</span><br>` +
                   `<span class="text-gray-600">• جميع البيانات المرتبطة به</span><br><br>` +
                   `<span class="text-red-600 font-bold">هذا الإجراء لا يمكن التراجع عنه!</span>`;
    
    // Store student data for submission
    window.currentDeleteStudentId = studentId;
    window.currentDeleteStudentName = studentName;
    
    // Show the custom modal
    document.getElementById('deleteStudentMessage').innerHTML = message;
    document.getElementById('deleteStudentModal').classList.remove('hidden');
}

function closeDeleteStudentModal() {
    document.getElementById('deleteStudentModal').classList.add('hidden');
}

function submitDeleteStudent() {
    if (window.currentDeleteStudentId) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        
        // Set the correct action URL using the URL helper
        const baseUrl = '<?= url("admin.group.student.delete", ["id" => $group_id, "student_id" => "STUDENT_ID"]) ?>';
        form.action = baseUrl.replace('STUDENT_ID', window.currentDeleteStudentId);
        
        const studentIdInput = document.createElement('input');
        studentIdInput.type = 'hidden';
        studentIdInput.name = 'student_id';
        studentIdInput.value = window.currentDeleteStudentId;
        
        const groupIdInput = document.createElement('input');
        groupIdInput.type = 'hidden';
        groupIdInput.name = 'group_id';
        groupIdInput.value = '<?= $group_id ?>';
        
        form.appendChild(studentIdInput);
        form.appendChild(groupIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Emoji selector functionality
document.addEventListener('DOMContentLoaded', function() {
    const emojiButtons = document.querySelectorAll('.emoji-btn');
    const selectedEmojiInput = document.getElementById('selected_emoji');
    
    emojiButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            emojiButtons.forEach(btn => {
                btn.classList.remove('bg-blue-300', 'border-2', 'border-blue-500');
                btn.classList.add('bg-white', 'border', 'border-gray-300');
            });
            
            // Add active class to clicked button
            this.classList.remove('bg-white', 'border', 'border-gray-300');
            this.classList.add('bg-blue-300', 'border-2', 'border-blue-500');
            
            // Update hidden input value
            const selectedEmoji = this.getAttribute('data-emoji');
            selectedEmojiInput.value = selectedEmoji;
        });
    });
});

// Mobile navigation functions
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-nav-menu');
    const menuIcon = document.getElementById('mobile-menu-icon');
    
    if (mobileMenu.classList.contains('active')) {
        mobileMenu.classList.remove('active');
        menuIcon.classList.remove('fa-times');
        menuIcon.classList.add('fa-bars');
    } else {
        mobileMenu.classList.add('active');
        menuIcon.classList.remove('fa-bars');
        menuIcon.classList.add('fa-times');
    }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const mobileMenu = document.getElementById('mobile-nav-menu');
    const menuBtn = document.querySelector('.mobile-menu-btn');
    
    if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
        mobileMenu.classList.remove('active');
        document.getElementById('mobile-menu-icon').classList.remove('fa-times');
        document.getElementById('mobile-menu-icon').classList.add('fa-bars');
    }
});
</script>

</body>
</html>