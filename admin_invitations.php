<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";
require_once "includes/url_helper.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header("Location: " . url('login'));
    exit;}

$admin_id = $_SESSION['user']['id'];
$admin_username = $_SESSION['user']['username'] ?? '';

// If username is not set, redirect to login
if (empty($admin_username)) {
    session_destroy();
    header("Location: " . url('login'));
    exit;
}

// Handle invitation responses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("Admin invitations POST data: " . print_r($_POST, true));
    if (isset($_POST['accept_invitation'])) {
        $invitation_id = intval($_POST['invitation_id']);
        $group_id = intval($_POST['group_id']);
        
        try {
            // Check if invitation exists and is for this admin
            $stmt = $conn->prepare("SELECT * FROM admin_invitations WHERE id = ? AND invited_username = ? AND status = 'pending'");
            $stmt->execute([$invitation_id, $admin_username]);
            $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invitation) {
                // Check if admin is already in the group
                $stmt = $conn->prepare("SELECT COUNT(*) FROM group_admins WHERE group_id = ? AND admin_id = ?");
                $stmt->execute([$group_id, $admin_id]);
                $already_member = $stmt->fetchColumn();
                
                if ($already_member > 0) {
                    // Admin is already in the group, just update invitation status
                    $stmt = $conn->prepare("UPDATE admin_invitations SET status = 'accepted' WHERE id = ?");
                    $stmt->execute([$invitation_id]);
                    $_SESSION['success'] = "✅ تم قبول الدعوة بنجاح! (كنت عضو في المجموعة بالفعل)";
                } else {
                    // Add admin to group
                    $stmt = $conn->prepare("INSERT INTO group_admins (group_id, admin_id) VALUES (?, ?)");
                    $stmt->execute([$group_id, $admin_id]);
                    
                    // Update invitation status
                    $stmt = $conn->prepare("UPDATE admin_invitations SET status = 'accepted' WHERE id = ?");
                    $stmt->execute([$invitation_id]);
                    
                    $_SESSION['success'] = "✅ تم قبول الدعوة بنجاح!";
                }
            } else {
                $_SESSION['error'] = "❌ الدعوة غير موجودة أو غير صالحة";
            }
        } catch (Exception $e) {
            error_log("Accept invitation error: " . $e->getMessage());
            $_SESSION['error'] = "❌ حدث خطأ في قبول الدعوة: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['decline_invitation'])) {
        $invitation_id = intval($_POST['invitation_id']);
        
        try {
            // Update invitation status
            $stmt = $conn->prepare("UPDATE admin_invitations SET status = 'declined' WHERE id = ? AND invited_username = ?");
            $stmt->execute([$invitation_id, $admin_username]);
            
            $_SESSION['success'] = "✅ تم رفض الدعوة";
        } catch (Exception $e) {
            error_log("Decline invitation error: " . $e->getMessage());
            $_SESSION['error'] = "❌ حدث خطأ في رفض الدعوة";
        }
    }
    
    header("Location: " . url('admin.invitations'));
    exit;
}

// Get pending invitations for this admin
$stmt = $conn->prepare("
    SELECT ai.*, g.name as group_name, a.name as inviter_name 
    FROM admin_invitations ai 
    JOIN groups g ON ai.group_id = g.id 
    JOIN admins a ON ai.inviter_admin_id = a.id 
    WHERE ai.invited_username = ? AND ai.status = 'pending'
    ORDER BY ai.created_at DESC
");
$stmt->execute([$admin_username]);
$invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دعوات الإشراف - إبداع 👥</title>
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
                padding: 8px;
            }
            
            .nav-glass {
                padding: 12px 16px;
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

            /* Card adjustments */
            .card {
                padding: 12px;
                margin-bottom: 12px;
            }

            .card-glass {
                padding: 12px;
                margin-bottom: 12px;
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

            /* Status badges */
            .status-badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 4px;
            }
            
            .nav-glass {
                padding: 8px 12px;
            }

            .text-4xl {
                font-size: 1.25rem; /* 20px */
            }

            .card {
                padding: 8px;
            }

            .card-glass {
                padding: 8px;
            }

            .btn-primary {
                padding: 8px 12px;
                font-size: 0.8rem;
            }

            .status-badge {
                font-size: 0.7rem;
                padding: 3px 6px;
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

        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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

        .btn-success {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
    </style>
</head>
<body>
    
    <nav class="nav-glass px-6 py-4 flex justify-between items-center relative">
        <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            ⚡ إبداع
        </span>

            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="space-x-2 space-x-reverse desktop-nav">
            <a href="<?= url('admin') ?>" class="btn-primary">
                <i class="fas fa-users"></i>
                المجموعات
            </a>
            <a href="<?= url('admin.questions') ?>" class="btn-primary">
                <i class="fas fa-question-circle"></i>
                الأسئلة
            </a>
            <a href="<?= url('admin.invitations') ?>" class="btn-primary relative active">
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

        <div class="mobile-nav-menu" id="mobileNavMenu">
            <div class="mobile-nav-links">
                <a href="<?= url('admin') ?>" class="btn-primary">
                    <i class="fas fa-users"></i>
                    المجموعات
                </a>
                <a href="<?= url('admin.questions') ?>" class="btn-primary">
                    <i class="fas fa-question-circle"></i>
                    الأسئلة
                </a>
                <a href="<?= url('admin.invitations') ?>" class="btn-primary active relative">
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

    <div class="container mx-auto p-8 relative z-10">
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card-glass rounded-2xl p-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">📬 دعوات الإشراف المعلقة</h2>
            
            <?php if (empty($invitations)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-6xl text-gray-400 mb-4"></i>
                    <p class="text-xl text-gray-600">لا توجد دعوات معلقة</p>
                    <p class="text-gray-500 mt-2">ستظهر هنا الدعوات التي يتم إرسالها لك للانضمام إلى مجموعات</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($invitations as $invitation): ?>
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-6 rounded-xl border border-blue-200 hover:shadow-lg transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-4 mb-3">
                                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user-plus text-white text-xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-800">دعوة للانضمام إلى مجموعة "<?= htmlspecialchars($invitation['group_name']) ?>"</h3>
                                            <p class="text-gray-600">دعوة من: <span class="font-semibold"><?= htmlspecialchars($invitation['inviter_name']) ?></span></p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-6 text-sm text-gray-500">
                                        <span><i class="fas fa-calendar-alt"></i> <?= date('Y-m-d H:i', strtotime($invitation['created_at'])) ?></span>
                                        <span><i class="fas fa-users"></i> مجموعة: <?= htmlspecialchars($invitation['group_name']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="flex gap-3">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                        <input type="hidden" name="group_id" value="<?= $invitation['group_id'] ?>">
                                        <button type="submit" name="accept_invitation" class="btn-success">
                                            <i class="fas fa-check"></i> قبول
                                        </button>
                                    </form>
                                    
                                    <form method="post" class="inline">
                                        <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                        <button type="submit" name="decline_invitation" class="btn-danger">
                                            <i class="fas fa-times"></i> رفض
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileNavMenu');
    mobileMenu.classList.toggle('active');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const mobileMenu = document.getElementById('mobileNavMenu');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    
    if (!mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
        mobileMenu.classList.remove('active');
    }
});
</script>
</body>
</html>