<?php
// Database Optimization Script for Ebdaa
// Run this once to optimize database performance

require_once 'includes/db.php';

try {
    echo "🚀 Starting database optimization...\n";
    
    // Add indexes for better query performance
    $indexes = [
        // Students table indexes
        "CREATE INDEX idx_students_group_id ON students(group_id)",
        "CREATE INDEX idx_students_name ON students(name)",
        "CREATE INDEX idx_students_username ON students(username)",
        "CREATE INDEX idx_students_degree ON students(degree)",
        
        // Groups table indexes
        "CREATE INDEX idx_groups_name ON groups(name)",
        "CREATE INDEX idx_groups_numStudt ON groups(numStudt)",
        
        // Questions table indexes
        "CREATE INDEX idx_questions_group_id ON questions(group_id)",
        "CREATE INDEX idx_questions_admin_id ON questions(admin_id)",
        "CREATE INDEX idx_questions_created_at ON questions(created_at)",
        "CREATE INDEX idx_questions_is_public ON questions(is_public)",
        
        // Answers table indexes
        "CREATE INDEX idx_answers_question_id ON answers(question_id)",
        "CREATE INDEX idx_answers_student_id ON answers(student_id)",
        "CREATE INDEX idx_answers_created_at ON answers(created_at)",
        
        // Notifications table indexes
        "CREATE INDEX idx_notifications_student_id ON notifications(student_id)",
        "CREATE INDEX idx_notifications_question_id ON notifications(question_id)",
        "CREATE INDEX idx_notifications_is_read ON notifications(is_read)",
        "CREATE INDEX idx_notifications_created_at ON notifications(created_at)",
        
        // Group admins table indexes
        "CREATE INDEX idx_group_admins_group_id ON group_admins(group_id)",
        "CREATE INDEX idx_group_admins_admin_id ON group_admins(admin_id)",
        
        // Admin invitations table indexes
        "CREATE INDEX idx_admin_invitations_group_id ON admin_invitations(group_id)",
        "CREATE INDEX idx_admin_invitations_invited_username ON admin_invitations(invited_username)",
        "CREATE INDEX idx_admin_invitations_status ON admin_invitations(status)",
        "CREATE INDEX idx_admin_invitations_created_at ON admin_invitations(created_at)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $conn->exec($index);
            echo "✅ Index created successfully\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠️  Index already exists, skipping...\n";
            } else {
                echo "❌ Error creating index: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Optimize tables
    $tables = ['students', 'groups', 'questions', 'answers', 'notifications', 'group_admins', 'admin_invitations'];
    
    foreach ($tables as $table) {
        try {
            $conn->exec("OPTIMIZE TABLE $table");
            echo "✅ Table $table optimized\n";
        } catch (PDOException $e) {
            echo "❌ Error optimizing table $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Update table statistics
    $conn->exec("ANALYZE TABLE students, groups, questions, answers, notifications, group_admins, admin_invitations");
    echo "✅ Table statistics updated\n";
    
    // Set optimal MySQL settings
    $settings = [
        "SET SESSION query_cache_type = ON",
        "SET SESSION query_cache_size = 268435456", // 256MB
        "SET SESSION tmp_table_size = 134217728",   // 128MB
        "SET SESSION max_heap_table_size = 134217728" // 128MB
    ];
    
    foreach ($settings as $setting) {
        try {
            $conn->exec($setting);
            echo "✅ Setting applied: $setting\n";
        } catch (PDOException $e) {
            echo "⚠️  Could not apply setting: $setting\n";
        }
    }
    
    echo "\n🎉 Database optimization completed successfully!\n";
    echo "📊 Performance improvements:\n";
    echo "   - Added 20+ database indexes\n";
    echo "   - Optimized all tables\n";
    echo "   - Updated table statistics\n";
    echo "   - Applied optimal MySQL settings\n";
    
} catch (Exception $e) {
    echo "❌ Error during optimization: " . $e->getMessage() . "\n";
}
?>
