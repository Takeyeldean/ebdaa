<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "includes/db.php";
require_once "includes/url_helper.php";


$student_id = $_SESSION['user']['id'];
error_log("Current student ID: " . $student_id);

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
error_log("Request method: " . $_SERVER['REQUEST_METHOD'] . ", POST data: " . print_r($_POST, true));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    error_log("Form submitted - question_id: " . ($_POST['question_id'] ?? 'not set') . ", answer_text: " . ($_POST['answer_text'] ?? 'not set') . ", selected_option_id: " . ($_POST['selected_option_id'] ?? 'not set') . ", submit_answer: " . ($_POST['submit_answer'] ?? 'not set'));
    $question_id = intval($_POST['question_id']);
    $answer_text = trim($_POST['answer_text'] ?? '');
    $selected_option_id = intval($_POST['selected_option_id'] ?? 0);
    
    if ($question_id > 0) {
        try {
            // Get question type
            $stmt = $conn->prepare("SELECT question_type, points FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch();
            
            if (!$question) {
                $_SESSION['error'] = "السؤال غير موجود";
                header("Location: " . url('questions'));
                exit();
            }
            
            if ($question['question_type'] === 'mcq') {
                // Handle MCQ answer
                error_log("MCQ question detected - selected_option_id: " . $selected_option_id);
                error_log("Question data: " . print_r($question, true));
                if ($selected_option_id > 0) {
                    // Check if student already answered this MCQ question
                    $stmt = $conn->prepare("SELECT id FROM student_mcq_answers WHERE student_id = ? AND question_id = ?");
                    $stmt->execute([$student_id, $question_id]);
                    
                    if ($stmt->fetch()) {
                        $_SESSION['error'] = "لقد أجبت على هذا السؤال من قبل ولا يمكنك تغيير إجابتك";
                    } else {
                        // Get the selected option details
                        $stmt = $conn->prepare("SELECT is_correct FROM question_options WHERE id = ? AND question_id = ?");
                        $stmt->execute([$selected_option_id, $question_id]);
                        $option = $stmt->fetch();
                        
                        if ($option) {
                            $is_correct = $option['is_correct'];
                            $points_earned = $is_correct ? $question['points'] : 0;
                            
                            // Insert MCQ answer
                            error_log("Inserting MCQ answer - student_id: $student_id, question_id: $question_id, selected_option_id: $selected_option_id, is_correct: $is_correct, points_earned: $points_earned");
                            $stmt = $conn->prepare("INSERT INTO student_mcq_answers (student_id, question_id, selected_option_id, is_correct, points_earned) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$student_id, $question_id, $selected_option_id, $is_correct, $points_earned]);
                            error_log("MCQ answer inserted successfully");
                            
                            // Verify the answer was inserted
                            $stmt = $conn->prepare("SELECT * FROM student_mcq_answers WHERE student_id = ? AND question_id = ?");
                            $stmt->execute([$student_id, $question_id]);
                            $inserted_answer = $stmt->fetch();
                            error_log("Verified inserted answer: " . print_r($inserted_answer, true));
                            
                            // Update student's total degree if answer is correct
                            if ($is_correct && $points_earned > 0) {
                                $stmt = $conn->prepare("UPDATE students SET degree = degree + ? WHERE id = ?");
                                $stmt->execute([$points_earned, $student_id]);
                            }
                            
                            $_SESSION['success'] = $is_correct ? 
                                "🎉 إجابة صحيحة! لقد حصلت على {$points_earned} نقاط" : 
                                "❌ إجابة خاطئة. حاول مرة أخرى في السؤال التالي";
                        } else {
                            $_SESSION['error'] = "الخيار المحدد غير صحيح";
                        }
                    }
                } else {
                    $_SESSION['error'] = "يرجى اختيار إجابة للسؤال";
                }
            } else {
                // Handle text answer
                if (!empty($answer_text)) {
                    // Always insert new answer (students can have multiple answers per question)
                    $stmt = $conn->prepare("INSERT INTO answers (question_id, student_id, answer_text) VALUES (?, ?, ?)");
                    $stmt->execute([$question_id, $student_id, $answer_text]);
                    $_SESSION['success'] = "تم إرسال إجابتك بنجاح!";
                } else {
                    $_SESSION['error'] = "يرجى كتابة إجابة للسؤال";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ في إرسال الإجابة: " . $e->getMessage();
        }
        
        // Redirect to prevent form resubmission on page reload
        header("Location: " . url('questions'));
        exit();
    } else {
        $_SESSION['error'] = "معرف السؤال غير صحيح";
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
        
        // Redirect to prevent form resubmission on page reload
        header("Location: " . url('questions'));
        exit();
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
        
        // Redirect to prevent form resubmission on page reload
        header("Location: " . url('questions'));
        exit();
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

// Get MCQ options for all questions
$mcq_options_by_question = [];
if (!empty($questions)) {
    $question_ids = array_column($questions, 'id');
    $placeholders = str_repeat('?,', count($question_ids) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT qo.*, qo.question_id
        FROM question_options qo
        WHERE qo.question_id IN ($placeholders)
        ORDER BY qo.question_id, qo.option_order
    ");
    $stmt->execute($question_ids);
    $mcq_options = $stmt->fetchAll();
    
    // Group options by question ID
    foreach ($mcq_options as $option) {
        $mcq_options_by_question[$option['question_id']][] = $option;
    }
}

// Get student's MCQ answers to check if they already answered
$student_mcq_answers = [];
if (!empty($questions)) {
    $question_ids = array_column($questions, 'id');
    $placeholders = str_repeat('?,', count($question_ids) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT question_id, selected_option_id, is_correct, points_earned
        FROM student_mcq_answers
        WHERE student_id = ? AND question_id IN ($placeholders)
    ");
    $stmt->execute(array_merge([$student_id], $question_ids));
    $mcq_answers = $stmt->fetchAll();
    
    // Group answers by question ID
    foreach ($mcq_answers as $answer) {
        $student_mcq_answers[$answer['question_id']] = $answer;
    }
    
    // Debug: Log MCQ answers count
    error_log("Found " . count($mcq_answers) . " MCQ answers for student $student_id");
}

// Get read status for questions (try to create table if it doesn't exist)
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS question_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        question_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_question (student_id, question_id)
    )");
} catch (PDOException $e) {
    // Table might already exist or there might be permission issues
    error_log("Could not create question_reads table: " . $e->getMessage());
}

// Get read status for questions
$read_questions = [];
try {
    $stmt = $conn->prepare("SELECT question_id FROM question_reads WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $read_questions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Table might not exist yet, continue without read tracking
    error_log("Could not fetch read questions: " . $e->getMessage());
}

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
        /* Completely redesigned with soft, eye-comfortable styling inspired by design references */
        body {
      font-family: 'Cairo', Arial, sans-serif;
            background: linear-gradient(135deg, #fefefe 0%, #f8f9fa 100%);
                  background-size: 400% 400%;

            min-height: 100vh;
            line-height: 1.7;
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

        /* Refined card styling */
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

        /* Elegant button styling */
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

        /* Question card styling */
        .question-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: white;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            z-index: 1;
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

        /* Typography improvements */
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

        /* Status badges */
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

        /* Answer styling */
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

        .answer-form {
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .answer-form:focus-within {
            border-color: rgba(5, 150, 105, 0.3);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        /* Form elements */
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

        /* Action buttons */
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

        /* Section titles */
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

        /* Chevron icon */
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

        /* Unread question styling (new questions) */
        .question-card.unread {
            border-left: 4px solid #dc2626;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            position: relative;
        }

        .question-card.unread::after {
            content: 'جديد';
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(45deg, #dc2626, #b91c1c);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
        }

        .question-card.unread:hover {
            border-color: rgba(220, 38, 38, 0.3);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.15);
        }

        .question-card.unread::before {
            background: linear-gradient(90deg, #dc2626, #b91c1c, #991b1b);
        }

        /* Unanswered question styling (read but not answered) */
        .question-card.unanswered {
            border-left: 4px solid #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            position: relative;
        }

        .question-card.unanswered::after {
            content: 'لم يُجاب';
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(45deg, #f59e0b, #d97706);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .question-card.unanswered:hover {
            border-color: rgba(245, 158, 11, 0.3);
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.15);
        }

        .question-card.unanswered::before {
            background: linear-gradient(90deg, #f59e0b, #d97706, #b45309);
        }

        /* Message styling */
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

        /* Added responsive mobile navigation styles */
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

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            
            .nav-glass {
                padding: 16px 20px;
                position: relative;
            }
            
            .desktop-nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .question-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .answer-actions {
                flex-direction: column;
                gap: 8px;
            }
            
            .action-btn {
                text-align: center;
                justify-content: center;
            }

            .question-card.unread::after,
            .question-card.unanswered::after {
                right: 8px;
                top: 8px;
                font-size: 0.7rem;
                padding: 3px 6px;
            }
        }

        /* Smooth animations */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #d1d5db;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4b5563;
        }
    </style>
</head>
<body>
   
   <nav class="nav-glass px-6 py-4 flex justify-between items-center">
    <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      ⚡ إبداع
    </span>
    
    <div class="desktop-nav space-x-2 space-x-reverse">
            <a href="<?= url('dashboard') ?>" class="btn-primary">
              <i class="fas fa-chart-bar"></i>
              الترتيب
            </a>
            <a href="<?= url('questions') ?>" class="btn-primary active relative">
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
            <a href="<?= url('profile') ?>" class="btn-primary">
              <i class="fas fa-user"></i>
              حسابي
            </a>
    </div>

    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
        <i class="fas fa-bars" id="mobile-menu-icon"></i>
    </button>
    <div class="mobile-nav-menu" id="mobile-nav-menu">
        <div class="mobile-nav-links">
            <a href="<?= url('dashboard') ?>" class="btn-primary">
              <i class="fas fa-chart-bar"></i>
              الترتيب
            </a>
            <a href="<?= url('questions') ?>" class="btn-primary active relative">
              <i class="fas fa-question-circle"></i>
              الأسئلة
              <?php if ($notification_count > 0): ?>
                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center animate-pulse">
                  <?= $notification_count ?>
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

    <div class="container mx-auto p-6 max-w-4xl">
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

        <div class="space-y-6">
            <?php if (empty($questions)): ?>
                <div class="card empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>لا توجد أسئلة بعد</h3>
                    <p>سيتم إشعارك عند إضافة أسئلة جديدة</p>
                </div>
            <?php else: ?>
                <?php foreach ($questions as $question): ?>
                    <?php 
                    $is_read = in_array($question['id'], $read_questions);
                    // Check if answered (either text answer or MCQ answer)
                    $has_text_answer = isset($my_answers_by_question[$question['id']]);
                    $has_mcq_answer = isset($student_mcq_answers[$question['id']]);
                    $is_answered = $has_text_answer || $has_mcq_answer;
                    $card_class = 'question-card';
                    
                    // Debug: Log answer status for MCQ questions only
                    if ($question['question_type'] === 'mcq') {
                        error_log("MCQ Question {$question['id']}: has_mcq_answer=$has_mcq_answer, is_answered=$is_answered");
                    }
                    
                    if (!$is_read) {
                        $card_class .= ' unread';
                    } elseif (!$is_answered) {
                        $card_class .= ' unanswered';
                    }
                    ?>
                    <div class="card p-6 <?= $card_class ?>" onclick="toggleQuestion(<?= $question['id'] ?>)">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="question-title text-balance">
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
                            <div class="mr-4">
                                <i class="fas fa-chevron-down chevron-icon" id="chevron-<?= $question['id'] ?>"></i>
                            </div>
                        </div>
                    </div>

                    <div class="question-content p-6 hidden" id="content-<?= $question['id'] ?>">
                        <div class="answer-form">
                            <?php if ($question['question_type'] === 'mcq'): ?>
                                <?php if (isset($student_mcq_answers[$question['id']])): ?>
                                     Already answered MCQ 
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                        <h4 class="section-title text-green-800">
                                            <i class="fas fa-check-circle"></i>
                                            لقد أجبت على هذا السؤال
                                        </h4>
                                        <p class="text-green-700">
                                            <?php 
                                            $answer = $student_mcq_answers[$question['id']];
                                            $selected_option = null;
                                            foreach ($mcq_options_by_question[$question['id']] as $option) {
                                                if ($option['id'] == $answer['selected_option_id']) {
                                                    $selected_option = $option;
                                                    break;
                                                }
                                            }
                                            ?>
                                            إجابتك: <strong><?= htmlspecialchars($selected_option['option_text']) ?></strong>
                                            <?php if ($answer['is_correct']): ?>
                                                <span class="text-green-600">✅ (صحيحة - <?= $answer['points_earned'] ?> نقاط)</span>
                                            <?php else: ?>
                                                <span class="text-red-600">❌ (خاطئة)</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <h4 class="section-title">
                                        <i class="fas fa-list-ul"></i>
                                        اختر الإجابة الصحيحة
                                    </h4>
                                    <form method="post" id="mcq-form-<?= $question['id'] ?>">
                                        <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                        <input type="hidden" name="submit_answer" value="1">
                                        <div class="space-y-3">
                                            <?php if (isset($mcq_options_by_question[$question['id']])): ?>
                                                <?php foreach ($mcq_options_by_question[$question['id']] as $option): ?>
                                                    <label class="flex items-center space-x-3 space-x-reverse p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                        <input type="radio" name="selected_option_id" value="<?= $option['id'] ?>" class="w-4 h-4 text-blue-600">
                                                        <span class="flex-1 text-gray-700"><?= htmlspecialchars($option['option_text']) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-gray-500">لا توجد خيارات متاحة لهذا السؤال</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-4 text-right">
                                            <button type="button" onclick="confirmMcqAnswer(<?= $question['id'] ?>)" class="btn-primary px-6 py-3">
                                                <i class="fas fa-paper-plane"></i>
                                                إرسال الإجابة
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <h4 class="section-title">
                                    <i class="fas fa-plus-circle"></i>
                                    إضافة إجابة جديدة
                                </h4>
                                <form method="post">
                                    <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                    <textarea name="answer_text" rows="4" required 
                                        class="form-textarea"
                                        placeholder="اكتب إجابتك هنا..."></textarea>
                                    <div class="mt-4 text-right">
                                        <button type="submit" name="submit_answer" class="btn-primary px-6 py-3">
                                            <i class="fas fa-paper-plane"></i>
                                            إرسال الإجابة
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>

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
                                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-user text-green-600 text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm text-gray-600">
                                                            <i class="fas fa-clock ml-1"></i>
                                                            <?= date('Y-m-d H:i', strtotime($answer['created_at'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="answer-actions flex gap-2">
                                                    <button onclick="editAnswer(<?= $answer['id'] ?>, '<?= htmlspecialchars($answer['answer_text'], ENT_QUOTES) ?>')" 
                                                            class="action-btn edit-btn">
                                                        <i class="fas fa-edit ml-1"></i>
                                                        تعديل
                                                    </button>
                                                    <form method="post" action="<?= url('questions') ?>" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الإجابة؟')">
                                                        <input type="hidden" name="answer_id" value="<?= $answer['id'] ?>">
                                                        <button type="submit" name="delete_answer" class="action-btn delete-btn">
                                                            <i class="fas fa-trash ml-1"></i>
                                                            حذف
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <p class="text-gray-700 leading-relaxed text-pretty" id="answer-text-<?= $answer['id'] ?>"><?= htmlspecialchars($answer['answer_text']) ?></p>
                                            
                                            <div id="edit-form-<?= $answer['id'] ?>" class="hidden mt-4 p-4 bg-gray-50 rounded-xl border">
                                                <form method="post" action="<?= url('questions') ?>">
                                                    <input type="hidden" name="answer_id" value="<?= $answer['id'] ?>">
                                                    <textarea name="answer_text" rows="3" required 
                                                        class="form-textarea"><?= htmlspecialchars($answer['answer_text']) ?></textarea>
                                                    <div class="flex space-x-2 space-x-reverse mt-3">
                                                        <button type="submit" name="update_answer" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                                            <i class="fas fa-save ml-1"></i>
                                                            حفظ
                                                        </button>
                                                        <button type="button" onclick="cancelEdit(<?= $answer['id'] ?>)" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors">
                                                            <i class="fas fa-times ml-1"></i>
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
                                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-800">
                                                            <?= htmlspecialchars($answer['student_name']) ?>
                                                        </div>
                                                        <div class="text-sm text-gray-600">
                                                            <i class="fas fa-clock ml-1"></i>
                                                            <?= date('H:i', strtotime($answer['created_at'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-gray-700 leading-relaxed text-pretty"><?= htmlspecialchars($answer['answer_text']) ?></p>
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

    <div id="mcq-confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">تأكيد الإجابة</h3>
                <p class="text-sm text-gray-500 mb-6">
                    هل أنت متأكد من إجابتك؟ لا يمكنك تغيير إجابتك بعد الإرسال.
                </p>
                <div class="flex space-x-3 space-x-reverse">
                    <button type="button" onclick="closeMcqModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                        إلغاء
                    </button>
                    <button type="button" onclick="submitMcqAnswer()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        تأكيد الإرسال
                    </button>
                </div>
            </div>
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
                    content.classList.remove('hidden');
                questionCard.classList.add('active');
                chevron.style.transform = 'rotate(180deg)';
                
                // Mark question as read when opened
                markQuestionAsRead(questionId);
            } else {
                content.classList.add('hidden');
                questionCard.classList.remove('active');
                chevron.style.transform = 'rotate(0deg)';
            }
        }

        function markQuestionAsRead(questionId) {
            // Send AJAX request to mark question as read
            fetch('mark_question_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'question_id=' + questionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove unread and unanswered classes and update styling
                    const questionCard = document.querySelector(`[onclick="toggleQuestion(${questionId})"]`);
                    if (questionCard) {
                        questionCard.classList.remove('unread', 'unanswered');
                        // Remove the "جديد" badge
                        const badge = questionCard.querySelector('::after');
                        if (badge) {
                            questionCard.style.setProperty('--badge-content', 'none');
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error marking question as read:', error);
            });
        }

        function editAnswer(answerId, currentText) {
            document.getElementById('answer-text-' + answerId).style.display = 'none';
            document.getElementById('edit-form-' + answerId).classList.remove('hidden');
        }

        function cancelEdit(answerId) {
            document.getElementById('answer-text-' + answerId).style.display = 'block';
            document.getElementById('edit-form-' + answerId).classList.add('hidden');
        }

        // MCQ Confirmation Functions
        let currentMcqQuestionId = null;

        function confirmMcqAnswer(questionId) {
            const form = document.getElementById('mcq-form-' + questionId);
            const selectedOption = form.querySelector('input[name="selected_option_id"]:checked');
            
            if (!selectedOption) {
                alert('يرجى اختيار إجابة قبل الإرسال');
                return;
            }
            
            currentMcqQuestionId = questionId;
            document.getElementById('mcq-confirmation-modal').classList.remove('hidden');
        }

        function closeMcqModal() {
            document.getElementById('mcq-confirmation-modal').classList.add('hidden');
            currentMcqQuestionId = null;
        }

        function submitMcqAnswer() {
            if (currentMcqQuestionId) {
                const form = document.getElementById('mcq-form-' + currentMcqQuestionId);
                // Ensure submit_answer field is present
                if (!form.querySelector('input[name="submit_answer"]')) {
                    const submitInput = document.createElement('input');
                    submitInput.type = 'hidden';
                    submitInput.name = 'submit_answer';
                    submitInput.value = '1';
                    form.appendChild(submitInput);
                }
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('mcq-confirmation-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMcqModal();
            }
        });

    </script>
</body>
</html>
