<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

$admin_id = $_SESSION['user']['id'];
$admin_username = $_SESSION['user']['username'] ?? '';

// If username is not set, redirect to login
if (empty($admin_username)) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle invitation responses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_invitation'])) {
        $invitation_id = intval($_POST['invitation_id']);
        $group_id = intval($_POST['group_id']);
        
        try {
            // Check if invitation exists and is for this admin
            $stmt = $conn->prepare("SELECT * FROM admin_invitations WHERE id = ? AND invited_username = ? AND status = 'pending'");
            $stmt->execute([$invitation_id, $admin_username]);
            $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invitation) {
                // Add admin to group
                $stmt = $conn->prepare("INSERT INTO group_admins (group_id, admin_id) VALUES (?, ?)");
                $stmt->execute([$group_id, $admin_id]);
                
                // Update invitation status
                $stmt = $conn->prepare("UPDATE admin_invitations SET status = 'accepted' WHERE id = ?");
                $stmt->execute([$invitation_id]);
                
                $_SESSION['success'] = "✅ تم قبول الدعوة بنجاح!";
            } else {
                $_SESSION['error'] = "❌ الدعوة غير موجودة أو غير صالحة";
            }
        } catch (Exception $e) {
            error_log("Accept invitation error: " . $e->getMessage());
            $_SESSION['error'] = "❌ حدث خطأ في قبول الدعوة";
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
    
    header("Location: admin_invitations.php");
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
    <!-- Navbar -->
  
  <!-- Navbar -->
  <nav class="nav-glass px-6 py-4 flex justify-between items-center">
    
    <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      ⚡ إبداع
    </span>

    <div class="space-x-2 space-x-reverse">
      <a href="admin.php" class="btn-primary">
        <i class="fas fa-users"></i>
        المجموعات
      </a>
      <a href="admin_questions.php" class="btn-primary">
        <i class="fas fa-question-circle"></i>
        الأسئلة
      </a>
      <a href="admin_invitations.php" class="btn-primary relative active">
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

    <div class="container mx-auto p-8 relative z-10">
        <!-- Success/Error Messages -->
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

        <!-- Invitations List -->
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
</body>
</html>
