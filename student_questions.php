<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";

// Check if user is student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user']['id'];

// Mark all notifications as read when student visits questions page
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ?");
$stmt->execute([$student_id]);

// Get student's group
$stmt = $conn->prepare("SELECT group_id FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
$group_id = $student['group_id'];

if (!$group_id) {
    $_SESSION['error'] = "لم يتم العثور على مجموعة للطالب";
    header("Location: dashboard.php");
    exit();
}

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    $question_id = intval($_POST['question_id']);
    $answer_text = trim($_POST['answer_text']);
    
    if ($question_id > 0 && !empty($answer_text)) {
        try {
            // Always insert new answer (students can have multiple answers per question)
            $stmt = $conn->prepare("INSERT INTO answers (question_id, student_id, answer_text) VALUES (?, ?, ?)");
            $stmt->execute([$question_id, $student_id, $answer_text]);
            $_SESSION['success'] = "تم إرسال إجابتك بنجاح!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في إرسال الإجابة: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "يرجى كتابة إجابة";
    }
}

// Handle answer update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_answer'])) {
    $answer_id = intval($_POST['answer_id']);
    $answer_text = trim($_POST['answer_text']);
    
    if ($answer_id > 0 && !empty($answer_text)) {
        try {
            // Verify the answer belongs to this student
            $stmt = $conn->prepare("SELECT id FROM answers WHERE id = ? AND student_id = ?");
            $stmt->execute([$answer_id, $student_id]);
            
            if ($stmt->fetch()) {
                $stmt = $conn->prepare("UPDATE answers SET answer_text = ? WHERE id = ? AND student_id = ?");
                $stmt->execute([$answer_text, $answer_id, $student_id]);
                $_SESSION['success'] = "تم تحديث إجابتك بنجاح!";
            } else {
                $_SESSION['error'] = "غير مسموح لك بتعديل هذه الإجابة";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في تحديث الإجابة: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "يرجى كتابة إجابة صحيحة";
    }
}

// Handle answer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_answer'])) {
    $answer_id = intval($_POST['answer_id']);
    
    if ($answer_id > 0) {
        try {
            // Verify the answer belongs to this student
            $stmt = $conn->prepare("SELECT id FROM answers WHERE id = ? AND student_id = ?");
            $stmt->execute([$answer_id, $student_id]);
            
            if ($stmt->fetch()) {
                $stmt = $conn->prepare("DELETE FROM answers WHERE id = ? AND student_id = ?");
                $stmt->execute([$answer_id, $student_id]);
                $_SESSION['success'] = "تم حذف إجابتك بنجاح!";
            } else {
                $_SESSION['error'] = "غير مسموح لك بحذف هذه الإجابة";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في حذف الإجابة: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف الإجابة غير صحيح";
    }
}

// Get questions for this group
$questions = $conn->prepare("
    SELECT q.*, a.name as admin_name
    FROM questions q 
    JOIN admins a ON q.admin_id = a.id 
    WHERE q.group_id = ? 
    ORDER BY q.created_at DESC
");
$questions->execute([$group_id]);
$questions = $questions->fetchAll();

// Get all answers for this student
$my_answers = $conn->prepare("
    SELECT id, question_id, answer_text, created_at 
    FROM answers 
    WHERE student_id = ? 
    ORDER BY created_at DESC
");
$my_answers->execute([$student_id]);
$my_answers = $my_answers->fetchAll();

// Group answers by question_id
$my_answers_by_question = [];
foreach ($my_answers as $answer) {
    $my_answers_by_question[$answer['question_id']][] = $answer;
}

// Get all answers for public questions
$public_questions = array_filter($questions, function($q) { return $q['is_public']; });
$public_question_ids = array_column($public_questions, 'id');

$all_answers = [];
if (!empty($public_question_ids)) {
    $placeholders = str_repeat('?,', count($public_question_ids) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT a.*, s.name as student_name 
        FROM answers a 
        JOIN students s ON a.student_id = s.id 
        WHERE a.question_id IN ($placeholders)
        ORDER BY a.created_at DESC
    ");
    $stmt->execute($public_question_ids);
    $all_answers = $stmt->fetchAll();
}

// Group answers by question_id
$answers_by_question = [];
foreach ($all_answers as $answer) {
    $answers_by_question[$answer['question_id']][] = $answer;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأسئلة والأجوبة - إبداع ❓</title>
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

        .btn-primary.active {
            background: linear-gradient(45deg, #10b981, #059669);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-primary.active:hover {
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.4);
        }

        .question-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            position: relative;
            overflow: hidden;
        }

        .question-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4, #10b981);
        }

        .question-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }

        .question-card:active {
            transform: translateY(-1px);
        }

        .question-card.active {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #3b82f6;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
        }

        .question-card.active::before {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8, #1e40af);
            height: 6px;
        }

        .question-card.active .question-title {
            color: #1e40af;
        }

        .question-card.active .chevron-icon {
            color: #3b82f6;
            transform: rotate(180deg) scale(1.1);
        }

        .question-content {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 16px 16px;
            margin-top: -1px;
            position: relative;
        }

        .question-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4, #10b981);
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .answer-card {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .answer-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .answer-form {
            background: linear-gradient(135deg, #f1f5f9 0%, #ffffff 100%);
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .answer-form:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .question-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .question-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            color: #64748b;
            font-size: 0.875rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item i {
            color: #3b82f6;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-public {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #166534;
        }

        .status-private {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        .chevron-icon {
            color: #94a3b8;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }

        .question-card:hover .chevron-icon {
            color: #3b82f6;
            transform: scale(1.1);
        }

        .answer-text {
            color: #374151;
            line-height: 1.7;
            font-size: 1rem;
        }

        .answer-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .edit-btn {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border-color: #93c5fd;
        }

        .edit-btn:hover {
            background: linear-gradient(135deg, #bfdbfe, #93c5fd);
            transform: translateY(-1px);
        }

        .delete-btn {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            border-color: #fca5a5;
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            transform: translateY(-1px);
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #3b82f6;
        }

    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="nav-glass px-6 py-4 flex justify-between items-center">
        <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            ❓ الأسئلة والأجوبة
        </span>
        <div class="space-x-2 space-x-reverse">
            <a href="dashboard.php" class="btn-primary">
                <i class="fas fa-chart-bar"></i>
                الترتيب
            </a>
            <a href="student_questions.php" class="btn-primary active">
                <i class="fas fa-question-circle"></i>
                الأسئلة
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

        <!-- Questions List -->
        <div class="space-y-4">
            <?php if (empty($questions)): ?>
                <div class="card p-8 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-600 mb-2">لا توجد أسئلة بعد</h3>
                    <p class="text-gray-500">سيتم إشعارك عند إضافة أسئلة جديدة</p>
                </div>
            <?php else: ?>
                <?php foreach ($questions as $question): ?>
                    <div class="card p-6 question-card cursor-pointer" onclick="toggleQuestion(<?= $question['id'] ?>)">
                        <!-- Question Header (Always Visible) -->
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="question-title">
                                    <?= htmlspecialchars($question['question_text']) ?>
                                </h3>
                                <div class="question-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span><?= htmlspecialchars($question['admin_name']) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= date('Y-m-d H:i', strtotime($question['created_at'])) ?></span>
                                    </div>
                                    <div class="status-badge <?= $question['is_public'] ? 'status-public' : 'status-private' ?>">
                                        <i class="fas <?= $question['is_public'] ? 'fa-globe' : 'fa-lock' ?>"></i>
                                        <?= $question['is_public'] ? 'عام' : 'خاص' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4">
                                <i class="fas fa-chevron-down chevron-icon" id="chevron-<?= $question['id'] ?>"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Question Content (Collapsible) -->
                    <div class="question-content p-6 hidden" id="content-<?= $question['id'] ?>">

                        <!-- Answer Form -->
                        <div class="answer-form">
                            <h4 class="section-title">
                                <i class="fas fa-plus-circle"></i>
                                إضافة إجابة جديدة
                            </h4>
                            <form method="post">
                                <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                <textarea name="answer_text" rows="4" required 
                                    class="w-full p-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-gray-700 placeholder-gray-400"
                                    placeholder="اكتب إجابتك هنا..."></textarea>
                                <div class="mt-4 text-right">
                                    <button type="submit" name="submit_answer" class="btn-primary px-6 py-3">
                                        <i class="fas fa-paper-plane"></i>
                                        إرسال الإجابة
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- My Answers -->
                        <?php if (isset($my_answers_by_question[$question['id']])): ?>
                            <div class="mt-8">
                                <h4 class="section-title">
                                    <i class="fas fa-user"></i>
                                    إجاباتي
                                </h4>
                                <div class="space-y-4">
                                    <?php foreach ($my_answers_by_question[$question['id']] as $answer): ?>
                                        <div class="answer-card">
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm text-gray-600">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            <?= date('Y-m-d H:i', strtotime($answer['created_at'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="answer-actions">
                                                    <!-- Edit Button -->
                                                    <button onclick="editAnswer(<?= $answer['id'] ?>, '<?= htmlspecialchars($answer['answer_text'], ENT_QUOTES) ?>')" 
                                                            class="action-btn edit-btn">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        تعديل
                                                    </button>
                                                    <!-- Delete Button -->
                                                    <form method="post" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الإجابة؟')">
                                                        <input type="hidden" name="answer_id" value="<?= $answer['id'] ?>">
                                                        <button type="submit" name="delete_answer" class="action-btn delete-btn">
                                                            <i class="fas fa-trash mr-1"></i>
                                                            حذف
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <p class="answer-text" id="answer-text-<?= $answer['id'] ?>"><?= htmlspecialchars($answer['answer_text']) ?></p>
                                            
                                            <!-- Edit Form (Hidden by default) -->
                                            <div id="edit-form-<?= $answer['id'] ?>" class="hidden mt-4 p-4 bg-gray-50 rounded-xl border">
                                                <form method="post">
                                                    <input type="hidden" name="answer_id" value="<?= $answer['id'] ?>">
                                                    <textarea name="answer_text" rows="3" required 
                                                        class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-gray-700"><?= htmlspecialchars($answer['answer_text']) ?></textarea>
                                                    <div class="flex space-x-2 space-x-reverse mt-3">
                                                        <button type="submit" name="update_answer" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                                            <i class="fas fa-save mr-1"></i>
                                                            حفظ
                                                        </button>
                                                        <button type="button" onclick="cancelEdit(<?= $answer['id'] ?>)" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors">
                                                            <i class="fas fa-times mr-1"></i>
                                                            إلغاء
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Public Answers (if question is public) -->
                        <?php if ($question['is_public'] && isset($answers_by_question[$question['id']])): ?>
                            <div class="mt-8">
                                <h4 class="section-title">
                                    <i class="fas fa-users"></i>
                                    إجابات الطلاب الآخرين
                                </h4>
                                <div class="space-y-4">
                                    <?php foreach ($answers_by_question[$question['id']] as $answer): ?>
                                        <div class="answer-card">
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-user text-green-600 text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-gray-800">
                                                            <?= htmlspecialchars($answer['student_name']) ?>
                                                        </div>
                                                        <div class="text-sm text-gray-600">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            <?= date('H:i', strtotime($answer['created_at'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="answer-text"><?= htmlspecialchars($answer['answer_text']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleQuestion(questionId) {
            const questionCard = document.querySelector(`[onclick="toggleQuestion(${questionId})"]`);
            const content = document.getElementById('content-' + questionId);
            const chevron = document.getElementById('chevron-' + questionId);
            
            // Close all other questions first
            document.querySelectorAll('.question-card.active').forEach(card => {
                if (card !== questionCard) {
                    card.classList.remove('active');
                    const otherContent = card.nextElementSibling;
                    if (otherContent && otherContent.classList.contains('question-content')) {
                        otherContent.classList.add('hidden');
                        const otherChevron = card.querySelector('.chevron-icon');
                        if (otherChevron) {
                            otherChevron.style.transform = 'rotate(0deg)';
                        }
                    }
                }
            });
            
            if (content.classList.contains('hidden')) {
                // Open this question
                content.classList.remove('hidden');
                questionCard.classList.add('active');
                chevron.style.transform = 'rotate(180deg) scale(1.1)';
            } else {
                // Close this question
                content.classList.add('hidden');
                questionCard.classList.remove('active');
                chevron.style.transform = 'rotate(0deg)';
            }
        }

        function editAnswer(answerId, currentText) {
            // Hide the answer text
            document.getElementById('answer-text-' + answerId).style.display = 'none';
            // Show the edit form
            document.getElementById('edit-form-' + answerId).classList.remove('hidden');
        }

        function cancelEdit(answerId) {
            // Show the answer text
            document.getElementById('answer-text-' + answerId).style.display = 'block';
            // Hide the edit form
            document.getElementById('edit-form-' + answerId).classList.add('hidden');
        }

    </script>
</body>
</html>
