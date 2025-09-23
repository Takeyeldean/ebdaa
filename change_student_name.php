<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";
require_once "includes/url_helper.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error'] = "❌ غير مسموح لك بالدخول";
    header("Location: " . url('login'));
    exit;
}

// Check if we have move student data
if (!isset($_SESSION['move_student_data'])) {
    $_SESSION['error'] = "❌ لا توجد بيانات نقل طالب";
    header("Location: " . url('admin'));
    exit;
}

$move_data = $_SESSION['move_student_data'];
$student_id = $move_data['student_id'];
$student_name = $move_data['student_name'];
$current_group_id = $move_data['current_group_id'];
$new_group_id = $move_data['new_group_id'];
$target_group_name = $move_data['target_group_name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_name'])) {
    $new_name = trim($_POST['new_name']);
    
    if (empty($new_name)) {
        $_SESSION['error'] = "❌ يرجى إدخال اسم جديد";
    } else {
        try {
            // Check if the new name is available in the target group
            $stmt = $conn->prepare("SELECT id FROM students WHERE name = ? AND group_id = ?");
            $stmt->execute([$new_name, $new_group_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "❌ الاسم الجديد مستخدم بالفعل في المجموعة ";
            } else {
                // Update student name and move to new group
                $conn->beginTransaction();
                
                try {
                    // Update student name and group
                    $stmt = $conn->prepare("UPDATE students SET name = ?, group_id = ? WHERE id = ?");
                    $stmt->execute([$new_name, $new_group_id, $student_id]);
                    
                    // Update numStudt for both groups
                    // Decrease count for current group
                    $stmt = $conn->prepare("UPDATE groups SET numStudt = numStudt - 1 WHERE id = ?");
                    $stmt->execute([$current_group_id]);
                    
                    // Increase count for target group
                    $stmt = $conn->prepare("UPDATE groups SET numStudt = numStudt + 1 WHERE id = ?");
                    $stmt->execute([$new_group_id]);
                    
                    $conn->commit();
                    
                    // Clear the move data from session
                    unset($_SESSION['move_student_data']);
                    
                    $_SESSION['success'] = "✅ تم نقل الطالب وتغيير اسمه إلى '{$new_name}' في مجموعة '{$target_group_name}' بنجاح";
                    header("Location: " . adminGroupUrl($current_group_id));
                    exit;
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
            }
        } catch (Exception $e) {
            error_log("Change student name error: " . $e->getMessage());
            $_SESSION['error'] = "❌ حدث خطأ أثناء تغيير الاسم. يرجى المحاولة مرة أخرى.";
        }
    }
}

// Get current group name for display
$stmt = $conn->prepare("SELECT name FROM groups WHERE id = ?");
$stmt->execute([$current_group_id]);
$current_group_name = $stmt->fetch()['name'];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغيير اسم الطالب - إبداع</title>
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="nav-glass px-6 py-4 flex justify-between items-center">
        <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            ⚡ إبداع
        </span>
        
        <div class="space-x-2 space-x-reverse">
            <a href="<?= url('admin') ?>" class="btn-primary">
              <i class="fas fa-users"></i>
              المجموعات
            </a>
            <a href="<?= url('admin.questions') ?>" class="btn-primary">
              <i class="fas fa-question-circle"></i>
              الأسئلة
            </a>
            <a href="<?= url('admin.invitations') ?>" class="btn-primary">
              <i class="fas fa-envelope"></i>
              الدعوات
            </a>
            <a href="<?= url('profile') ?>" class="btn-primary">
              <i class="fas fa-user"></i>
              حسابي
            </a>
        </div>
    </nav>

    <div class="container mx-auto p-8 relative z-10">
        <div class="max-w-2xl mx-auto">
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

            <!-- Name Conflict Warning -->
            <div class="bg-orange-100 border border-orange-300 rounded-2xl p-8 mb-8">
                <div class="flex items-center gap-4 mb-6">
                    <i class="fas fa-exclamation-triangle text-4xl text-orange-600"></i>
                    <div>
                        <h2 class="text-2xl font-bold text-orange-800">⚠️ تعارض في الأسماء</h2>
                        <p class="text-orange-700">يوجد طالب آخر بنفس الاسم في المجموعة </p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-6 mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">تفاصيل النقل:</h3>
                    <div class="space-y-2 text-gray-700">
                        <p><strong>الطالب:</strong> <?= htmlspecialchars($student_name) ?></p>
                        <p><strong>من المجموعة:</strong> <?= htmlspecialchars($current_group_name) ?></p>
                        <p><strong>إلى المجموعة:</strong> <?= htmlspecialchars($target_group_name) ?></p>
                    </div>
                </div>
            </div>

            <!-- Change Name Form -->
            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <h3 class="text-2xl font-bold text-blue-800 mb-6">تغيير اسم الطالب</h3>
                
                <form method="post" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الاسم الحالي:</label>
                        <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($student_name) ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الاسم الجديد:</label>
                        <input type="text" name="new_name" value="<?= htmlspecialchars($student_name) ?>" 
                               class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-400 text-lg" 
                               placeholder="أدخل الاسم الجديد" required>
                        <p class="text-sm text-gray-500 mt-1">سيتم نقل الطالب بالاسم الجديد إلى المجموعة </p>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="btn-primary flex-1">
                            <i class="fas fa-check"></i>
                            تأكيد النقل مع الاسم الجديد
                        </button>
                        <a href="<?= adminGroupUrl($current_group_id) ?>" class="btn-danger flex-1 text-center">
                            <i class="fas fa-times"></i>
                            إلغاء النقل
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
