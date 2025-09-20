<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['user']['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_question'])) {
    $group_id = intval($_POST['group_id']);
    $question_text = trim($_POST['question_text']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    if ($group_id > 0 && !empty($question_text)) {
        try {
            // Verify that the admin has access to this group
            $stmt = $conn->prepare("SELECT 1 FROM group_admins WHERE group_id = ? AND admin_id = ?");
            $stmt->execute([$group_id, $admin_id]);
            
            if ($stmt->fetch()) {
                $stmt = $conn->prepare("INSERT INTO questions (group_id, admin_id, question_text, is_public) VALUES (?, ?, ?, ?)");
                $stmt->execute([$group_id, $admin_id, $question_text, $is_public]);
                $question_id = $conn->lastInsertId();
                
                // Create notifications for all students in this group
                $stmt = $conn->prepare("INSERT INTO notifications (student_id, question_id) SELECT id, ? FROM students WHERE group_id = ?");
                $stmt->execute([$question_id, $group_id]);
                
                $_SESSION['success'] = "تم إنشاء السؤال بنجاح!";
            } else {
                $_SESSION['error'] = "ليس لديك صلاحية للوصول إلى هذه المجموعة";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في إنشاء السؤال: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "يرجى ملء جميع الحقول المطلوبة";
    }
}

// Handle question update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_question'])) {
    $question_id = intval($_POST['question_id']);
    $question_text = trim($_POST['question_text']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    if ($question_id > 0 && !empty($question_text)) {
        try {
            // Verify the question belongs to this admin
            $stmt = $conn->prepare("SELECT id FROM questions WHERE id = ? AND admin_id = ?");
            $stmt->execute([$question_id, $admin_id]);
            
            if ($stmt->fetch()) {
                $stmt = $conn->prepare("UPDATE questions SET question_text = ?, is_public = ? WHERE id = ? AND admin_id = ?");
                $stmt->execute([$question_text, $is_public, $question_id, $admin_id]);
                $_SESSION['success'] = "تم تحديث السؤال بنجاح!";
            } else {
                $_SESSION['error'] = "غير مسموح لك بتعديل هذا السؤال";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في تحديث السؤال: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "يرجى كتابة نص السؤال";
    }
}

// Handle question deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question'])) {
    $question_id = intval($_POST['question_id']);
    
    if ($question_id > 0) {
        try {
            // Verify the question belongs to this admin
            $stmt = $conn->prepare("SELECT id FROM questions WHERE id = ? AND admin_id = ?");
            $stmt->execute([$question_id, $admin_id]);
            
            if ($stmt->fetch()) {
                $stmt = $conn->prepare("DELETE FROM questions WHERE id = ? AND admin_id = ?");
                $stmt->execute([$question_id, $admin_id]);
                $_SESSION['success'] = "تم حذف السؤال بنجاح!";
            } else {
                $_SESSION['error'] = "غير مسموح لك بحذف هذا السؤال";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في حذف السؤال: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف السؤال غير صحيح";
    }
}

// Get groups that this admin has access to
$groups = $conn->prepare("
    SELECT g.* 
    FROM groups g 
    INNER JOIN group_admins ga ON g.id = ga.group_id 
    WHERE ga.admin_id = ? 
    ORDER BY g.name
");
$groups->execute([$admin_id]);
$groups = $groups->fetchAll();

// Get questions from groups that this admin has access to
$questions_by_group = $conn->prepare("
    SELECT q.*, g.name as group_name, g.id as group_id, a.name as admin_name 
    FROM questions q 
    JOIN groups g ON q.group_id = g.id 
    JOIN admins a ON q.admin_id = a.id 
    JOIN group_admins ga ON g.id = ga.group_id 
    WHERE ga.admin_id = ?
    ORDER BY g.name ASC, q.created_at DESC
");
$questions_by_group->execute([$admin_id]);
$questions_by_group = $questions_by_group->fetchAll();

// Group questions by group
$grouped_questions = [];
foreach ($questions_by_group as $question) {
    $grouped_questions[$question['group_id']][] = $question;
}

// Get all question IDs for answers
$all_question_ids = array_column($questions_by_group, 'id');
$answers_by_question = [];

if (!empty($all_question_ids)) {
    $placeholders = str_repeat('?,', count($all_question_ids) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT a.*, s.name as student_name, s.id as student_id
        FROM answers a 
        JOIN students s ON a.student_id = s.id 
        WHERE a.question_id IN ($placeholders)
        ORDER BY a.created_at DESC
    ");
    $stmt->execute($all_question_ids);
    $all_answers = $stmt->fetchAll();
    
    // Group answers by question_id
    foreach ($all_answers as $answer) {
        $answers_by_question[$answer['question_id']][] = $answer;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأسئلة - إبداع ❓</title>
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

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
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

        .group-section {
            margin-bottom: 3rem;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .group-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .group-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .group-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(-20px, 20px);
        }

        .group-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .group-stats {
            display: flex;
            align-items: center;
            gap: 2rem;
            font-size: 0.875rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-item i {
            font-size: 1rem;
        }

        .questions-grid {
            display: grid;
            gap: 1.5rem;
        }

        .no-questions {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .no-questions i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="nav-glass px-6 py-4 flex justify-between items-center">
        <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            ❓ إدارة الأسئلة
        </span>
        <div class="space-x-2 space-x-reverse">
            <a href="admin.php" class="btn-primary">
                <i class="fas fa-arrow-right"></i>
                العودة للمجموعات
            </a>
        </div>
    </nav>

    <div class="container mx-auto p-8 relative z-10">
        <!-- Success/Error Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Create Question Form -->
        <div class="card p-8 mb-8">
            <h2 class="text-3xl font-bold text-blue-800 mb-6 text-center">
                <i class="fas fa-plus-circle"></i>
                إنشاء سؤال جديد
            </h2>

            <form method="post" class="space-y-6">
                <div>
                    <label class="block text-lg font-semibold text-gray-700 mb-2">
                        <i class="fas fa-users"></i>
                        اختر المجموعة:
                    </label>
                    <select name="group_id" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg">
                        <option value="">-- اختر المجموعة --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-lg font-semibold text-gray-700 mb-2">
                        <i class="fas fa-question-circle"></i>
                        نص السؤال:
                    </label>
                    <textarea name="question_text" rows="4" required 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg resize-none"
                        placeholder="اكتب سؤالك هنا..."></textarea>
                </div>

                <div class="flex items-center space-x-4 space-x-reverse">
                    <input type="checkbox" name="is_public" id="is_public" value="1" checked class="w-5 h-5 text-blue-600">
                    <label for="is_public" class="text-lg font-semibold text-gray-700">
                        <i class="fas fa-globe"></i>
                        إجابات عامة (يمكن للطلاب رؤية إجابات بعضهم البعض)
                    </label>
                </div>

                <div class="text-center">
                    <button type="submit" name="create_question" class="btn-primary text-lg px-8 py-3">
                        <i class="fas fa-paper-plane"></i>
                        إرسال السؤال
                    </button>
                </div>
            </form>
        </div>

        <!-- Questions by Group -->
        <?php if (empty($grouped_questions)): ?>
            <div class="card p-8 text-center">
                <i class="fas fa-inbox text-6xl text-gray-400 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">لا توجد أسئلة بعد</h3>
                <p class="text-gray-500">ابدأ بإنشاء سؤال جديد للمجموعات</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_questions as $group_id => $questions): ?>
                <div class="group-section">
                    <!-- Group Header -->
                    <div class="group-header">
                        <h2 class="group-title">
                            <i class="fas fa-users"></i>
                            <?= htmlspecialchars($questions[0]['group_name']) ?>
                        </h2>
                        <div class="group-stats">
                            <div class="stat-item">
                                <i class="fas fa-question-circle"></i>
                                <span><?= count($questions) ?> سؤال</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-comments"></i>
                                <span><?= array_sum(array_map(function($q) use ($answers_by_question) { return isset($answers_by_question[$q['id']]) ? count($answers_by_question[$q['id']]) : 0; }, $questions)) ?> إجابة</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-clock"></i>
                                <span>آخر سؤال: <?= date('Y-m-d', strtotime($questions[0]['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Grid -->
                    <div class="questions-grid">
                        <?php foreach ($questions as $question): ?>
                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-lg font-bold text-gray-800" id="question-text-<?= $question['id'] ?>">
                                        <?= htmlspecialchars($question['question_text']) ?>
                                    </h3>
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        <span class="text-sm text-gray-500">
                                            <?= date('Y-m-d H:i', strtotime($question['created_at'])) ?>
                                        </span>
                                        <div class="flex space-x-1 space-x-reverse">
                                            <!-- View Answers Button -->
                                            <button onclick="toggleAnswers(<?= $question['id'] ?>)" 
                                                    class="text-green-600 hover:text-green-800 text-sm" 
                                                    title="عرض إجابات الطلاب">
                                                <i class="fas fa-comments"></i>
                                                <span class="text-xs">(<?= isset($answers_by_question[$question['id']]) ? count($answers_by_question[$question['id']]) : 0 ?>)</span>
                                            </button>
                                            <!-- Edit Button -->
                                            <button onclick="editQuestion(<?= $question['id'] ?>, '<?= htmlspecialchars($question['question_text'], ENT_QUOTES) ?>', <?= $question['is_public'] ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <!-- Delete Button -->
                                            <form method="post" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السؤال؟ سيتم حذف جميع الإجابات المرتبطة به.')">
                                                <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                                <button type="submit" name="delete_question" class="text-red-600 hover:text-red-800 text-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center text-sm text-gray-600">
                                    <span>
                                        <i class="fas fa-user-tie"></i>
                                        <?= htmlspecialchars($question['admin_name']) ?>
                                    </span>
                                    <span class="px-2 py-1 rounded-full text-xs <?= $question['is_public'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <i class="fas <?= $question['is_public'] ? 'fa-globe' : 'fa-lock' ?>"></i>
                                        <?= $question['is_public'] ? 'عام' : 'خاص' ?>
                                    </span>
                                </div>

                                <!-- Student Answers Section (Collapsible) -->
                                <div id="answers-<?= $question['id'] ?>" class="hidden mt-4 border-t pt-4">
                                    <h4 class="text-lg font-bold text-gray-700 mb-3">
                                        <i class="fas fa-comments"></i>
                                        إجابات الطلاب (<?= isset($answers_by_question[$question['id']]) ? count($answers_by_question[$question['id']]) : 0 ?>)
                                    </h4>
                                    
                                    <?php if (isset($answers_by_question[$question['id']]) && !empty($answers_by_question[$question['id']])): ?>
                                        <div class="space-y-3 max-h-96 overflow-y-auto">
                                            <?php foreach ($answers_by_question[$question['id']] as $answer): ?>
                                                <div class="bg-white rounded-lg p-3 border border-gray-200">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <div class="flex items-center space-x-2 space-x-reverse">
                                                            <span class="font-semibold text-gray-800">
                                                                <i class="fas fa-user"></i>
                                                                <?= htmlspecialchars($answer['student_name']) ?>
                                                            </span>
                                                            <span class="text-xs text-gray-500">
                                                                (ID: <?= $answer['student_id'] ?>)
                                                            </span>
                                                        </div>
                                                        <span class="text-xs text-gray-500">
                                                            <i class="fas fa-clock"></i>
                                                            <?= date('Y-m-d H:i', strtotime($answer['created_at'])) ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-700 text-sm"><?= htmlspecialchars($answer['answer_text']) ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-gray-500 py-4">
                                            <i class="fas fa-comment-slash text-2xl mb-2"></i>
                                            <p>لا توجد إجابات بعد</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Edit Form (Hidden by default) -->
                                <div id="edit-form-<?= $question['id'] ?>" class="hidden mt-4 p-4 bg-white rounded-lg border">
                                    <form method="post">
                                        <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                        <div class="mb-3">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                نص السؤال:
                                            </label>
                                            <textarea name="question_text" rows="3" required 
                                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"><?= htmlspecialchars($question['question_text']) ?></textarea>
                                        </div>
                                        <div class="flex items-center space-x-4 space-x-reverse mb-3">
                                            <input type="checkbox" name="is_public" id="is_public_<?= $question['id'] ?>" value="1" <?= $question['is_public'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600">
                                            <label for="is_public_<?= $question['id'] ?>" class="text-sm font-semibold text-gray-700">
                                                <i class="fas fa-globe"></i>
                                                إجابات عامة
                                            </label>
                                        </div>
                                        <div class="flex space-x-2 space-x-reverse">
                                            <button type="submit" name="update_question" class="bg-green-600 text-white px-4 py-2 rounded text-sm">
                                                <i class="fas fa-save"></i>
                                                حفظ
                                            </button>
                                            <button type="button" onclick="cancelEdit(<?= $question['id'] ?>)" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">
                                                <i class="fas fa-times"></i>
                                                إلغاء
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleAnswers(questionId) {
            const answersDiv = document.getElementById('answers-' + questionId);
            if (answersDiv.classList.contains('hidden')) {
                answersDiv.classList.remove('hidden');
            } else {
                answersDiv.classList.add('hidden');
            }
        }

        function editQuestion(questionId, currentText, isPublic) {
            // Hide the question text
            document.getElementById('question-text-' + questionId).style.display = 'none';
            // Show the edit form
            document.getElementById('edit-form-' + questionId).classList.remove('hidden');
        }

        function cancelEdit(questionId) {
            // Show the question text
            document.getElementById('question-text-' + questionId).style.display = 'block';
            // Hide the edit form
            document.getElementById('edit-form-' + questionId).classList.add('hidden');
        }

    </script>
</body>
</html>
