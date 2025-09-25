<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/url_helper.php';
// username
// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

// Fetch current user info
$table = ($role === 'student') ? 'students' : 'admins';
if ($role == 'student')
    $stmt = $conn->prepare("SELECT name, username, profile_image FROM $table WHERE id = ?");
else
    $stmt = $conn->prepare("SELECT name, username FROM $table WHERE id = ?");

$stmt->execute([$userId]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حسابي - إبداع 👤</title>
    <script src="https://cdn.tailwindcss.com"></script>
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


        .profile-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem auto;
        }

        .profile-container img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid #fff;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .profile-container img:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .upload-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 4px solid white;
            font-size: 24px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .upload-btn input[type="file"] {
            display: none;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.15);
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
            border: none;
            cursor: pointer;
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
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(239, 68, 68, 0.4);
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            outline: none;
            font-size: 16px;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 16px;
        }

        .success-message {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .error-message {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            color: white;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        .decoration {
            position: absolute;
            pointer-events: none;
            z-index: 1;
        }

        .decoration-icon {
            color: #f59e0b;
            font-size: 1.5rem;
            animation: twinkle 2s ease-in-out infinite;
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

  <nav class="nav-glass px-6 py-4 flex justify-between items-center relative">

    <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      ⚡ إبداع
    </span>

    <div class="space-x-2 space-x-reverse desktop-nav">

        <?php if ($role === 'student'): ?>
            <a href="<?= url('dashboard') ?>" class="btn-primary">
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
            <a href="<?= url('profile') ?>" class="btn-primary active">
              <i class="fas fa-user"></i>
              حسابي
            </a>
        <?php endif; ?> 

        <?php if ($role === 'admin'): ?>
            <a href="<?= url('admin') ?>" class="btn-primary">
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
            <a href="<?= url('profile') ?>" class="btn-primary active">
              <i class="fas fa-user"></i>
              حسابي
            </a>
        <?php endif; ?>

     
    </div>
 <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
        <i class="fas fa-bars" id="mobile-menu-icon"></i>
    </button>
    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav-menu" id="mobile-nav-menu">
        <div class="mobile-nav-links">
            <?php if ($role === 'student'): ?>
                <a href="<?= url('dashboard') ?>" class="btn-primary">
                    <i class="fas fa-chart-bar"></i>
                    الترتيب
                </a>
                <a href="<?= url('questions') ?>" class="btn-primary relative">
                    <i class="fas fa-question-circle"></i>
                    الأسئلة
                    <?php if ($notification_count > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center animate-pulse">
                            <?= $notification_count ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('profile') ?>" class="btn-primary active">
                    <i class="fas fa-user"></i>
                    حسابي
                </a>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <a href="<?= url('admin') ?>" class="btn-primary">
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
                    <?php if ($invitation_count > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-orange-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center animate-pulse">
                            <?= $invitation_count ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('profile') ?>" class="btn-primary active">
                    <i class="fas fa-user"></i>
                    حسابي
                </a>
            <?php endif; ?>
        </div>
    </div>
  </nav>

<div class="container mx-auto p-8 relative z-10">

    <!-- Profile Image with Upload Button -->
    <?php if ($role === 'student'): ?>
    <div class="profile-container floating">
        <?php if (!empty($user['profile_image'])): ?>
            <img src="/ebdaa/uploads/<?= htmlspecialchars($user['profile_image']) ?>" alt="صورة الملف الشخصي">
        <?php else: ?>
            <img src="/ebdaa/uploads/default.png" alt="صورة الملف الشخصي">
        <?php endif; ?>
        <form action="<?= url('profile.image') ?>" method="POST" enctype="multipart/form-data" class="upload-btn">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <label>
                <input type="file" name="profile_image" accept="image/*" onchange="this.form.submit()">
                <i class="fas fa-camera"></i>
            </label>
        </form>
    </div>
    <?php endif; ?>

    <!-- Welcome Section -->
    <div class="text-center mb-8 bounce-in">
        <h2 class="text-5xl font-bold mb-4 text-white">
            حسابي الشخصي ⚡
        </h2>
        <p class="text-xl text-white">دعنا نحدث معلوماتك! 🚀</p>
    </div>

    <!-- ✅ رسائل النجاح أو الخطأ -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Update Name & username -->
    <div class="card p-8 mb-8 floating">
        <div class="text-center mb-6">
            <div class="text-4xl mb-4">⚡</div>
            <h3 class="text-2xl font-bold text-gray-800">تحديث المعلومات الشخصية</h3>
            <p class="text-gray-600">دعنا نحدث اسمك واسم المستخدم</p>
        </div>
        
        <form method="POST" action="<?= url('profile.update') ?>">
            <input type="hidden" name="update_info">
            <div class="mb-6">
                <label class="form-label">
                    <i class="fas fa-user"></i>
                    الاسم الكامل
                </label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="form-input" placeholder="أدخل اسمك الكامل">
            </div>
            <div class="mb-6">
                <label class="form-label">
                    <i class="fas fa-at"></i>
                    اسم المستخدم
                </label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-input" placeholder="أدخل اسم المستخدم">
            </div>
            <button type="submit" class="btn-primary w-full">
                <i class="fas fa-save"></i>
                حفظ التغييرات
            </button>
        </form>
    </div>

    <!-- Update Password -->
    <div class="card p-8 mb-8 floating">
        <div class="text-center mb-6">
            <div class="text-4xl mb-4">🛡️</div>
            <h3 class="text-2xl font-bold text-gray-800">تغيير كلمة المرور</h3>
            <p class="text-gray-600">لحماية حسابك، تأكد من استخدام كلمة مرور قوية</p>
        </div>
        
        <form method="POST" action="<?= url('profile.update') ?>">
            <input type="hidden" name="update_pass">
            <div class="mb-6">
                <label class="form-label">
                    <i class="fas fa-lock"></i>
                    كلمة المرور الحالية
                </label>
                <input type="password" name="current_password" class="form-input" placeholder="أدخل كلمة المرور الحالية">
            </div>
            <div class="mb-6">
                <label class="form-label">
                    <i class="fas fa-key"></i>
                    كلمة المرور الجديدة
                </label>
                <input type="password" name="new_password" class="form-input" placeholder="أدخل كلمة المرور الجديدة">
            </div>
            <div class="mb-6">
                <label class="form-label">
                    <i class="fas fa-check-circle"></i>
                    تأكيد كلمة المرور الجديدة
                </label>
                <input type="password" name="confirm_password" class="form-input" placeholder="أعد إدخال كلمة المرور الجديدة">
            </div>
            <button type="submit" class="btn-primary w-full">
                <i class="fas fa-shield-alt"></i>
                تحديث كلمة المرور
            </button>
        </form>
    </div>

    <!-- Logout Button -->
    <div class="text-center">
        <a href="<?= url('logout') ?>" class="btn-danger">
            <i class="fas fa-sign-out-alt"></i>
            تسجيل الخروج
        </a>
    </div>

</div>

<script>
    
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

<style>
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .container {
                padding: 8px;
            }
            
            .nav-glass {
                padding: 12px 16px;
            }
            
            .profile-container {
                width: 120px;
                height: 120px;
            }
            
            .card {
                padding: 12px;
                margin-bottom: 12px;
            }

            /* Make text smaller on mobile */
            .text-4xl {
                font-size: 1.5rem; /* 24px instead of 36px */
            }

            .text-3xl {
                font-size: 1.25rem; /* 20px instead of 30px */
            }

            .text-2xl {
                font-size: 1.125rem; /* 18px instead of 24px */
            }

            .text-xl {
                font-size: 1rem; /* 16px instead of 20px */
            }

            /* Form elements */
            .form-group {
                margin-bottom: 12px;
            }

            .form-group label {
                font-size: 0.875rem;
                margin-bottom: 4px;
            }

            .form-group input,
            .form-group textarea {
                padding: 10px 12px;
                font-size: 0.875rem;
            }

            /* Buttons */
            .btn-primary {
                padding: 10px 16px;
                font-size: 0.875rem;
                margin-bottom: 8px;
            }

            /* Reduce margins and padding globally */
            .mb-8 { margin-bottom: 16px; }
            .mb-6 { margin-bottom: 12px; }
            .mb-4 { margin-bottom: 8px; }
            .mb-3 { margin-bottom: 6px; }
            .mb-2 { margin-bottom: 4px; }
            .mb-1 { margin-bottom: 2px; }

            .p-6 { padding: 12px; }
            .p-4 { padding: 8px; }
            .p-3 { padding: 6px; }
            .p-2 { padding: 4px; }
        }

        @media (max-width: 480px) {
            .container {
                padding: 4px;
            }
            
            .nav-glass {
                padding: 8px 12px;
            }

            .profile-container {
                width: 100px;
                height: 100px;
            }

            .card {
                padding: 8px;
            }

            .text-4xl {
                font-size: 1.25rem; /* 20px */
            }

            .btn-primary {
                padding: 8px 12px;
                font-size: 0.8rem;
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
</style>

</body>
</html>
