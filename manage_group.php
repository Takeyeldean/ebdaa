<?php
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";
// username
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if ($group_id == 0) die("Group not found!");

$stmt = $conn->prepare("SELECT * FROM students WHERE group_id = ?");
$stmt->execute([$group_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
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
  </style>
</head>
<body>

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

  <div class="container mx-auto p-8">

    <!-- العنوان -->
    <h1 class="text-4xl font-bold text-white mb-8 text-center">إدارة المجموعة</h1>

    <!-- جدول الطلاب -->
    <div class="bg-white shadow-md rounded-2xl p-6 overflow-x-auto mb-12">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-blue-100 text-blue-800">
            <th class="p-3 text-right"> الطالب</th>
            <th class="p-3 text-center"> الدرجة الحالية</th>
            <th class="p-3 text-center"> تحكم</th>
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
                    <a href="update_degree.php?id=<?= $student['id'] ?>&amount=<?= $inc ?>" class="inline-block bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition">+<?= $inc ?></a>
                  <?php endforeach; ?>

                  <!-- إضافة قيمة مخصصة -->
                  <form action="update_degree.php" method="get" class="inline-block mx-2">
                    <input type="hidden" name="id" value="<?= $student['id'] ?>">
                    <input type="number" name="amount" class="w-20 border rounded-lg px-2 py-1 focus:ring-2 focus:ring-blue-400" placeholder="0">
                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition">إضافة</button>
                  </form>

                  <!-- أزرار خصم درجات -->
                  <?php foreach ([5,3,2,1] as $dec): ?>
                    <a href="update_degree.php?id=<?= $student['id'] ?>&amount=-<?= $dec ?>" class="inline-block bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition">-<?= $dec ?></a>
                  <?php endforeach; ?>
                </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
      
      <form method="post" action="update_group_message.php" class="space-y-4">
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
      
      <form method="post" action="invite_admin.php" class="space-y-4">
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

  <form method="post" action="add.php" class="space-y-5">
    <input type="hidden" name="group_id" value="<?= $group_id ?>">
    
    <div>
      <label class="block mb-1 font-medium text-gray-700">اسم الطالب:</label>
      <input type="text" placeholder="الإسم" name="name" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div>
      <label class="block mb-1 font-medium text-gray-700">اسم المستخدم:</label>
      <input type="username" placeholder="اسم المستخدم" name="username" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div>
      <label class="block mb-1 font-medium text-gray-700">كلمة المرور:</label>
      <input type="password" name="password" placeholder="كلمة المرور" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <div>
      <label class="block mb-1 font-medium text-gray-700">تأكيد كلمة المرور:</label>
      <input type="password" name="confirm_password" placeholder="أعد كتابة كلمة المرور" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" required>
    </div>

    <button type="submit" name="add_student" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition shadow-md">
      إضافة الطالب
    </button>
  </form>
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
</script>

</body>
</html>
