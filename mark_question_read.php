<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is a student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user']['id'];
$question_id = intval($_POST['question_id'] ?? 0);

if ($question_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
    exit();
}

try {
    // Try to create table if it doesn't exist
    $conn->exec("CREATE TABLE IF NOT EXISTS question_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        question_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_question (student_id, question_id)
    )");
    
    // Insert or update the read status
    $stmt = $conn->prepare("
        INSERT INTO question_reads (student_id, question_id) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$student_id, $question_id]);
    
    echo json_encode(['success' => true, 'message' => 'Question marked as read']);
    
} catch (PDOException $e) {
    error_log("Error marking question as read: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
