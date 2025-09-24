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
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['user']['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_question'])) {
    $group_id = intval($_POST['group_id']);
    $question_text = trim($_POST['question_text']);
    $question_type = $_POST['question_type'] ?? 'text';
    $points = intval($_POST['points'] ?? 0);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    if ($group_id > 0 && !empty($question_text)) {
        try {
            // Verify that the admin has access to this group
            $stmt = $conn->prepare("SELECT 1 FROM group_admins WHERE group_id = ? AND admin_id = ?");
            $stmt->execute([$group_id, $admin_id]);
            
            if ($stmt->fetch()) {
                // Start transaction
                $conn->beginTransaction();
                
                // Insert the question
                $stmt = $conn->prepare("INSERT INTO questions (group_id, admin_id, question_text, question_type, points, is_public) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$group_id, $admin_id, $question_text, $question_type, $points, $is_public]);
                $question_id = $conn->lastInsertId();
                
                // If it's an MCQ question, insert the options
                if ($question_type === 'mcq' && isset($_POST['mcq_options'])) {
                    $options = $_POST['mcq_options'];
                    $correct_option = intval($_POST['correct_option'] ?? 0);
                    
                    // Validate MCQ options
                    $valid_options = array_filter($options, function($option) {
                        return !empty(trim($option));
                    });
                    
                    if (count($valid_options) < 2) {
                        throw new Exception("يجب أن يكون هناك على الأقل خياران للإجابة");
                    }
                    
                    if ($correct_option < 0 || $correct_option >= count($valid_options)) {
                        throw new Exception("يجب اختيار إجابة صحيحة من الخيارات المتاحة");
                    }
                    
                    $option_index = 0;
                    foreach ($options as $index => $option_text) {
                        if (!empty(trim($option_text))) {
                            $is_correct = ($option_index == $correct_option) ? 1 : 0;
                            $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$question_id, trim($option_text), $is_correct, $option_index + 1]);
                            $option_index++;
                        }
                    }
                }
                
                // Create notifications for all students in this group
                $stmt = $conn->prepare("INSERT INTO notifications (student_id, question_id) SELECT id, ? FROM students WHERE group_id = ?");
                $stmt->execute([$question_id, $group_id]);
                
                // Commit transaction
                $conn->commit();
                
                $_SESSION['success'] = "تم إنشاء السؤال بنجاح!";
                
                // Redirect to prevent form resubmission on page reload
                header("Location: " . url('admin.questions'));
                exit();
            } else {
                $_SESSION['error'] = "ليس لديك صلاحية للوصول إلى هذه المجموعة";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "حدث خطأ في إنشاء السؤال: " . $e->getMessage();
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "خطأ في التحقق من الخيارات: " . $e->getMessage();
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
                
                // Redirect to prevent form resubmission on page reload
                header("Location: " . url('admin.questions'));
                exit();
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
                
                // Redirect to prevent form resubmission on page reload
                header("Location: " . url('admin.questions'));
                exit();
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
$mcq_options_by_question = [];

// Get MCQ options for all questions
if (!empty($all_question_ids)) {
    $placeholders = str_repeat('?,', count($all_question_ids) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT qo.*, qo.question_id
        FROM question_options qo
        WHERE qo.question_id IN ($placeholders)
        ORDER BY qo.question_id, qo.option_order
    ");
    $stmt->execute($all_question_ids);
    $mcq_options = $stmt->fetchAll();
    
    // Group options by question ID
    foreach ($mcq_options as $option) {
        $mcq_options_by_question[$option['question_id']][] = $option;
    }
}

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
            background: linear-gradient(135deg, #fefefe 0%, #f8f9fa 100%);
            min-height: 100vh;
            line-height: 1.7;
        }

        .nav-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 0 0 25px 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: white;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .question-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #059669, #10b981, #34d399);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .question-card:hover::before {
            opacity: 1;
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            border-color: rgba(5, 150, 105, 0.2);
        }

        .question-card.active {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-color: rgba(5, 150, 105, 0.3);
            transform: translateY(-1px);
        }

        .question-card.active::before {
            opacity: 1;
            height: 4px;
        }

        .question-content {
            background: linear-gradient(135deg, #fefefe 0%, #f9fafb 100%);
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-top: none;
            border-radius: 0 0 16px 16px;
            margin-top: -1px;
        }

        .question-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .question-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            color: #6b7280;
            font-size: 0.85rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item i {
            color: #059669;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-public {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .status-private {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        .answer-card {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .answer-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        .form-textarea {
            width: 100%;
            padding: 16px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            font-size: 0.95rem;
            line-height: 1.6;
            resize: vertical;
            transition: all 0.3s ease;
            background: white;
        }

        .form-textarea:focus {
            outline: none;
            border-color: rgba(5, 150, 105, 0.4);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .action-btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .edit-btn {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border-color: rgba(59, 130, 246, 0.2);
        }

        .edit-btn:hover {
            background: linear-gradient(135deg, #bfdbfe, #93c5fd);
            transform: translateY(-1px);
        }

        .delete-btn {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            border-color: rgba(220, 38, 38, 0.2);
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            transform: translateY(-1px);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #059669;
            font-size: 0.9rem;
        }

        .chevron-icon {
            color: #9ca3af;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .question-card:hover .chevron-icon {
            color: #059669;
        }

        .question-card.active .chevron-icon {
            color: #059669;
            transform: rotate(180deg);
        }

        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .message.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid rgba(5, 150, 105, 0.2);
        }

        .message.error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        /* Dynamic MCQ Options Styling */
        .mcq-option-row {
            transition: all 0.3s ease;
        }

        .mcq-option-row:hover {
            background-color: rgba(59, 130, 246, 0.05);
            border-radius: 0.5rem;
            padding: 0.5rem;
            margin: -0.5rem;
        }

        .mcq-option-row button {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .mcq-option-row:hover button {
            opacity: 1;
        }

        /* Pulse animation for radio buttons reminder */
        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
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
            grid-template-columns: 1fr;
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
            ⚡ إبداع
        </span>
        <div class="space-x-2 space-x-reverse">
            <a href="<?= url('admin') ?>" class="btn-primary">
                <i class="fas fa-users"></i>
                المجموعات
            </a>
            <a href="<?= url('admin.questions') ?>" class="btn-primary active">
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
    </nav>

    <div class="container mx-auto p-8 relative z-10">
        <!-- Success/Error Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
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
                    <select name="group_id" required class="form-textarea">
                        <option value="">-- اختر المجموعة --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-lg font-semibold text-gray-700 mb-2">
                        <i class="fas fa-question-circle"></i>
                        نوع السؤال:
                    </label>
                    <select name="question_type" id="question_type" required class="form-textarea" onchange="toggleQuestionType()">
                        <option value="text">سؤال نصي</option>
                        <option value="mcq">اختيار من متعدد </option>
                    </select>
                </div>

                <div>
                    <label class="block text-lg font-semibold text-gray-700 mb-2">
                        <i class="fas fa-question-circle"></i>
                        نص السؤال:
                    </label>
                    <textarea name="question_text" rows="4" required 
                        class="form-textarea"
                        placeholder="اكتب سؤالك هنا..."></textarea>
                </div>

                <div id="points_section" class="hidden">
                    <label class="block text-lg font-semibold text-gray-700 mb-2">
                        <i class="fas fa-star"></i>
                        عدد النقاط:
                    </label>
                    <input type="number" name="points" min="1" max="100" value="5" 
                        class="form-textarea" placeholder="عدد النقاط">
                </div>

                <div id="mcq_options_section" class="hidden">
                    <label class="block text-lg font-semibold text-gray-700 mb-2">
                        <i class="fas fa-list"></i>
                        خيارات الإجابة:
                    </label>
                    <div id="mcq_options_container" class="space-y-3">
                        <div class="flex items-center space-x-3 space-x-reverse mcq-option-row">
                            <input type="radio" name="correct_option" value="0" class="w-4 h-4 text-green-600">
                            <input type="text" name="mcq_options[]" placeholder="الخيار الأول" class="form-textarea flex-1" required>
                            <button type="button" onclick="removeMcqOption(this)" class="text-red-600 hover:text-red-800 p-1" title="حذف الخيار">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="flex items-center space-x-3 space-x-reverse mcq-option-row">
                            <input type="radio" name="correct_option" value="1" class="w-4 h-4 text-green-600">
                            <input type="text" name="mcq_options[]" placeholder="الخيار الثاني" class="form-textarea flex-1" required>
                            <button type="button" onclick="removeMcqOption(this)" class="text-red-600 hover:text-red-800 p-1" title="حذف الخيار">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" onclick="addMcqOption()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                            <i class="fas fa-plus"></i>
                            إضافة خيار جديد
                        </button>
                    </div>
                  
                </div>

                <div class="flex items-center space-x-4 space-x-reverse">
                    <input type="checkbox" name="is_public" id="is_public" value="1" checked class="w-5 h-5 text-blue-600">
                    <label for="is_public" class="text-lg font-semibold text-gray-700">
                        <i class="fas fa-globe"></i>
                        إجابات عامة (يمكن للطلاب رؤية إجابات بعضهم البعض)
                    </label>
                </div>

                <div class="text-center">
                    <button type="submit" name="create_question" class="btn-primary text-lg px-8 py-3" onclick="return validateMcqForm()">
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
                            <div class="card p-6     question-card" onclick="toggleQuestion(<?= $question['id'] ?>)">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="question-title text-balance flex-1" id="question-text-<?= $question['id'] ?>">
                                        <?= htmlspecialchars($question['question_text']) ?>
                                        <?php if ($question['question_type'] === 'mcq'): ?>
                                            <div class="mt-2">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <i class="fas fa-list-ul mr-1"></i>
                                                    MCQ (<?= $question['points'] ?> نقاط)
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </h3>
                                    <div class="mr-4">
                                        <i class="fas fa-chevron-down chevron-icon" id="chevron-<?= $question['id'] ?>"></i>
                                    </div>
                                </div>
                                
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
                                
                                <div class="flex justify-end items-center mt-3">
                                    <div class="flex space-x-1 space-x-reverse">
                                        <!-- View Answers Button -->
                                        <button onclick="event.stopPropagation(); toggleAnswers(<?= $question['id'] ?>)" 
                                                class="text-green-600 hover:text-green-800 text-sm" 
                                                title="عرض إجابات الطلاب">
                                            <i class="fas fa-comments"></i>
                                            <span class="text-xs">(<?= isset($answers_by_question[$question['id']]) ? count($answers_by_question[$question['id']]) : 0 ?>)</span>
                                        </button>
                                        <!-- Edit Button -->
                                        <button onclick="event.stopPropagation(); editQuestion(<?= $question['id'] ?>, '<?= htmlspecialchars($question['question_text'], ENT_QUOTES) ?>', <?= $question['is_public'] ?>)" 
                                                class="text-blue-600 hover:text-blue-800 text-l">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- Delete Button -->
                                        <form method="post" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السؤال؟ سيتم حذف جميع الإجابات المرتبطة به.')">
                                            <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                            <button type="submit" name="delete_question" class="text-red-600 hover:text-red-800 text-sm" onclick="event.stopPropagation();">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Question Content (Collapsible) -->
                            <div class="question-content p-6 hidden" id="content-<?= $question['id'] ?>">
                                <!-- MCQ Options Section -->
                                <?php if ($question['question_type'] === 'mcq' && isset($mcq_options_by_question[$question['id']])): ?>
                                    <div class="mb-6">
                                        <h4 class="section-title mb-3">
                                            <i class="fas fa-list-ul"></i>
                                            خيارات الإجابة:
                                        </h4>
                                        <div class="space-y-2">
                                            <?php foreach ($mcq_options_by_question[$question['id']] as $option): ?>
                                                <div class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg <?= $option['is_correct'] ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' ?>">
                                                    <div class="flex-shrink-0">
                                                        <?php if ($option['is_correct']): ?>
                                                            <i class="fas fa-check-circle text-green-600"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-circle text-gray-400"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="flex-1 <?= $option['is_correct'] ? 'font-semibold text-green-800' : 'text-gray-700' ?>">
                                                        <?= htmlspecialchars($option['option_text']) ?>
                                                    </span>
                                                    <?php if ($option['is_correct']): ?>
                                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                                            الإجابة الصحيحة
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Student Answers Section -->
                                <div id="answers-<?= $question['id'] ?>" class="mt-4">
                                    <h4 class="section-title">
                                        <i class="fas fa-comments"></i>
                                        إجابات الطلاب (<?= isset($answers_by_question[$question['id']]) ? count($answers_by_question[$question['id']]) : 0 ?>)
                                    </h4>
                                    
                                    <?php if (isset($answers_by_question[$question['id']]) && !empty($answers_by_question[$question['id']])): ?>
                                        <div class="space-y-3 max-h-96 overflow-y-auto">
                                            <?php foreach ($answers_by_question[$question['id']] as $answer): ?>
                                                <div class="answer-card">
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
                                            class="form-textarea"><?= htmlspecialchars($question['question_text']) ?></textarea>
                                    </div>
                                    <div class="flex items-center space-x-4 space-x-reverse mb-3">
                                        <input type="checkbox" name="is_public" id="is_public_<?= $question['id'] ?>" value="1" <?= $question['is_public'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600">
                                        <label for="is_public_<?= $question['id'] ?>" class="text-sm font-semibold text-gray-700">
                                            <i class="fas fa-globe"></i>
                                            إجابات عامة
                                        </label>
                                    </div>
                                    <div class="flex space-x-2 space-x-reverse">
                                        <button type="submit" name="update_question" class="action-btn edit-btn">
                                            <i class="fas fa-save"></i>
                                            حفظ
                                        </button>
                                        <button type="button" onclick="cancelEdit(<?= $question['id'] ?>)" class="action-btn delete-btn">
                                            <i class="fas fa-times"></i>
                                            إلغاء
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleQuestionType() {
            const questionType = document.getElementById('question_type').value;
            const pointsSection = document.getElementById('points_section');
            const mcqOptionsSection = document.getElementById('mcq_options_section');
            
            if (questionType === 'mcq') {
                pointsSection.classList.remove('hidden');
                mcqOptionsSection.classList.remove('hidden');
                // Add visual reminder for correct answer selection
                setTimeout(() => {
                    highlightCorrectAnswerReminder();
                }, 500);
            } else {
                pointsSection.classList.add('hidden');
                mcqOptionsSection.classList.add('hidden');
            }
        }

        function highlightCorrectAnswerReminder() {
            const radioButtons = document.querySelectorAll('input[name="correct_option"]');
            const hasSelected = document.querySelector('input[name="correct_option"]:checked');
            
            if (!hasSelected) {
                // Add pulsing animation to radio buttons to draw attention
                radioButtons.forEach(radio => {
                    radio.style.animation = 'pulse 2s infinite';
                });
                
                // Remove animation after 6 seconds
                setTimeout(() => {
                    radioButtons.forEach(radio => {
                        radio.style.animation = '';
                    });
                }, 6000);
            }
        }

        // Add event listeners to radio buttons to remove pulsing when selected
        document.addEventListener('change', function(e) {
            if (e.target.name === 'correct_option') {
                // Remove pulsing animation from all radio buttons
                const radioButtons = document.querySelectorAll('input[name="correct_option"]');
                radioButtons.forEach(radio => {
                    radio.style.animation = '';
                });
            }
        });

        function addMcqOption() {
            const container = document.getElementById('mcq_options_container');
            const optionCount = container.children.length;
            const optionNumber = optionCount + 1;
            
            const optionRow = document.createElement('div');
            optionRow.className = 'flex items-center space-x-3 space-x-reverse mcq-option-row';
            optionRow.innerHTML = `
                <input type="radio" name="correct_option" value="${optionCount}" class="w-4 h-4 text-green-600">
                <input type="text" name="mcq_options[]" placeholder="الخيار ${getArabicNumber(optionNumber)}" class="form-textarea flex-1" required>
                <button type="button" onclick="removeMcqOption(this)" class="text-red-600 hover:text-red-800 p-1" title="حذف الخيار">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            container.appendChild(optionRow);
        }

        function removeMcqOption(button) {
            const container = document.getElementById('mcq_options_container');
            if (container.children.length > 2) { // Minimum 2 options
                const optionRow = button.closest('.mcq-option-row');
                optionRow.remove();
                updateMcqOptionValues();
            } else {
                alert('يجب أن يكون هناك على الأقل خياران');
            }
        }

        function updateMcqOptionValues() {
            const container = document.getElementById('mcq_options_container');
            const radioButtons = container.querySelectorAll('input[name="correct_option"]');
            radioButtons.forEach((radio, index) => {
                radio.value = index;
            });
        }

        function getArabicNumber(num) {
            const arabicNumbers = ['الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس', 'السادس', 'السابع', 'الثامن', 'التاسع', 'العاشر'];
            return arabicNumbers[num - 1] || `الخيار ${num}`;
        }

        function validateMcqForm() {
            const questionType = document.getElementById('question_type').value;
            
            if (questionType === 'mcq') {
                // Check if MCQ options section is visible
                const mcqSection = document.getElementById('mcq_options_section');
                if (!mcqSection.classList.contains('hidden')) {
                    // Check if any correct answer is selected
                    const correctOption = document.querySelector('input[name="correct_option"]:checked');
                    if (!correctOption) {
                        // Show error message
                        showMcqValidationError('⚠️ يجب اختيار الإجابة الصحيحة قبل إرسال السؤال');
                        return false;
                    }
                    
                    // Check if all options have text
                    const optionInputs = document.querySelectorAll('input[name="mcq_options[]"]');
                    let emptyOptions = 0;
                    optionInputs.forEach(input => {
                        if (!input.value.trim()) {
                            emptyOptions++;
                        }
                    });
                    
                    if (emptyOptions > 0) {
                        showMcqValidationError(`⚠️ يجب ملء جميع الخيارات (${emptyOptions} خيار فارغ)`);
                        return false;
                    }
                }
            }
            
            return true;
        }

        function showMcqValidationError(message) {
            // Remove any existing error messages
            const existingError = document.querySelector('.mcq-validation-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Create error message element
            const errorDiv = document.createElement('div');
            errorDiv.className = 'mcq-validation-error bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
            errorDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Insert error message before the submit button
            const submitButton = document.querySelector('button[name="create_question"]');
            submitButton.parentNode.insertBefore(errorDiv, submitButton);
            
            // Scroll to error message
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Auto-remove error after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 5000);
        }

        function toggleQuestion(questionId) {
            const questionCard = document.querySelector(`[onclick="toggleQuestion(${questionId})"]`);
            const content = document.getElementById('content-' + questionId);
            const chevron = document.getElementById('chevron-' + questionId);
            
            // Check if elements exist
            if (!questionCard || !content || !chevron) {
                console.error('Required elements not found for question:', questionId);
                return;
            }
            
            // Close all other questions first - improved approach
            document.querySelectorAll('.question-card.active').forEach(activeCard => {
                if (activeCard !== questionCard) {
                    activeCard.classList.remove('active');
                    
                    // Get question ID from the active card more reliably
                    const activeOnclick = activeCard.getAttribute('onclick');
                    if (activeOnclick) {
                        const match = activeOnclick.match(/toggleQuestion$$(\d+)$$/);
                        if (match) {
                            const otherQuestionId = match[1];
                            const otherContent = document.getElementById('content-' + otherQuestionId);
                            const otherChevron = document.getElementById('chevron-' + otherQuestionId);
                            
                            if (otherContent) {
                                otherContent.classList.add('hidden');
                            }
                            if (otherChevron) {
                                otherChevron.style.transform = 'rotate(0deg)';
                            }
                        }
                    }
                }
            });
            
            // Toggle current question
            const isHidden = content.classList.contains('hidden');
            if (isHidden) {
                content.classList.remove('hidden');
                questionCard.classList.add('active');
                chevron.style.transform = 'rotate(180deg)';
                smoothScrollToElement(questionCard);
            } else {
                content.classList.add('hidden');
                questionCard.classList.remove('active');
                chevron.style.transform = 'rotate(0deg)';
            }
        }

        function toggleAnswers(questionId) {
            const answersDiv = document.getElementById('answers-' + questionId);
            if (!answersDiv) {
                console.error('Answers div not found for question:', questionId);
                return;
            }
            
            answersDiv.classList.toggle('hidden');
        }

        function editQuestion(questionId, currentText, isPublic) {
            const questionText = document.getElementById('question-text-' + questionId);
            const editForm = document.getElementById('edit-form-' + questionId);
            
            if (!questionText || !editForm) {
                console.error('Required elements not found for editing question:', questionId);
                return;
            }
            
            // Hide the question text and show edit form
            questionText.style.display = 'none';
            editForm.classList.remove('hidden');
            
            // Focus on the textarea for better UX
            const textarea = editForm.querySelector('textarea[name="question_text"]');
            if (textarea) {
                setTimeout(() => textarea.focus(), 100);
            }
        }

        function cancelEdit(questionId) {
            const questionText = document.getElementById('question-text-' + questionId);
            const editForm = document.getElementById('edit-form-' + questionId);
            
            if (!questionText || !editForm) {
                console.error('Required elements not found for canceling edit:', questionId);
                return;
            }
            
            // Show the question text and hide edit form
            questionText.style.display = 'block';
            editForm.classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            // Close all open questions when pressing Escape
            if (e.key === 'Escape') {
                document.querySelectorAll('.question-card.active').forEach(card => {
                    const onclick = card.getAttribute('onclick');
                    if (onclick) {
                        const match = onclick.match(/toggleQuestion$$(\d+)$$/);
                        if (match) {
                            const questionId = match[1];
                            const content = document.getElementById('content-' + questionId);
                            const chevron = document.getElementById('chevron-' + questionId);
                            
                            if (content) content.classList.add('hidden');
                            if (chevron) chevron.style.transform = 'rotate(0deg)';
                            card.classList.remove('active');
                        }
                    }
                });
            }
        });

        function smoothScrollToElement(element) {
            if (element) {
                element.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'nearest' 
                });
            }
        }
    </script>
</body>
</html>
