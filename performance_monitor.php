<?php
// Performance Monitoring Dashboard for Ebdaa
session_start();
require_once 'includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Get performance metrics
function getPerformanceMetrics() {
    global $conn;
    
    $metrics = [];
    
    // Database performance
    $start = microtime(true);
    $stmt = $conn->query("SELECT COUNT(*) as count FROM students");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $metrics['db_query_time'] = round((microtime(true) - $start) * 1000, 2);
    $metrics['total_students'] = $result['count'];
    
    // Table sizes
    $tables = ['students', 'groups', 'questions', 'answers', 'notifications', 'group_admins', 'admin_invitations'];
    $metrics['table_sizes'] = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $metrics['table_sizes'][$table] = $result['count'];
    }
    
    // File system metrics
    $uploadsDir = 'uploads/';
    $metrics['uploads_size'] = 0;
    $metrics['uploads_count'] = 0;
    
    if (is_dir($uploadsDir)) {
        $files = glob($uploadsDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $metrics['uploads_size'] += filesize($file);
                $metrics['uploads_count']++;
            }
        }
    }
    
    // PHP memory usage
    $metrics['memory_usage'] = memory_get_usage(true);
    $metrics['memory_peak'] = memory_get_peak_usage(true);
    
    // Server load (if available)
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $metrics['server_load'] = $load[0];
    }
    
    return $metrics;
}

$metrics = getPerformanceMetrics();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراقب الأداء - إبداع 📊</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .metric-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(16, 185, 129, 0.1));
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-5px);
        }
        .performance-good { color: #10b981; }
        .performance-warning { color: #f59e0b; }
        .performance-critical { color: #ef4444; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-cyan-600 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="glass-card p-6 mb-8">
            <h1 class="text-3xl font-bold text-white text-center mb-4">
                <i class="fas fa-tachometer-alt"></i> مراقب الأداء
            </h1>
            <p class="text-white text-center opacity-80">مراقبة أداء النظام والأداء العام</p>
        </div>

        <!-- Performance Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="metric-card">
                <i class="fas fa-database text-4xl mb-4 text-blue-400"></i>
                <h3 class="text-lg font-semibold text-white mb-2">سرعة قاعدة البيانات</h3>
                <p class="text-2xl font-bold <?= $metrics['db_query_time'] < 50 ? 'performance-good' : ($metrics['db_query_time'] < 100 ? 'performance-warning' : 'performance-critical') ?>">
                    <?= $metrics['db_query_time'] ?>ms
                </p>
            </div>

            <div class="metric-card">
                <i class="fas fa-users text-4xl mb-4 text-green-400"></i>
                <h3 class="text-lg font-semibold text-white mb-2">إجمالي الطلاب</h3>
                <p class="text-2xl font-bold text-white"><?= number_format($metrics['total_students']) ?></p>
            </div>

            <div class="metric-card">
                <i class="fas fa-memory text-4xl mb-4 text-purple-400"></i>
                <h3 class="text-lg font-semibold text-white mb-2">استخدام الذاكرة</h3>
                <p class="text-2xl font-bold <?= $metrics['memory_usage'] < 50 * 1024 * 1024 ? 'performance-good' : ($metrics['memory_usage'] < 100 * 1024 * 1024 ? 'performance-warning' : 'performance-critical') ?>">
                    <?= round($metrics['memory_usage'] / 1024 / 1024, 2) ?>MB
                </p>
            </div>

            <div class="metric-card">
                <i class="fas fa-images text-4xl mb-4 text-yellow-400"></i>
                <h3 class="text-lg font-semibold text-white mb-2">حجم الملفات</h3>
                <p class="text-2xl font-bold text-white"><?= round($metrics['uploads_size'] / 1024 / 1024, 2) ?>MB</p>
            </div>
        </div>

        <!-- Detailed Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Database Tables -->
            <div class="glass-card p-6">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-table"></i> جداول قاعدة البيانات
                </h2>
                <div class="space-y-4">
                    <?php foreach ($metrics['table_sizes'] as $table => $count): ?>
                        <div class="flex justify-between items-center p-3 bg-white bg-opacity-10 rounded-lg">
                            <span class="text-white font-medium"><?= ucfirst($table) ?></span>
                            <span class="text-blue-300 font-bold"><?= number_format($count) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- System Information -->
            <div class="glass-card p-6">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-server"></i> معلومات النظام
                </h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-10 rounded-lg">
                        <span class="text-white font-medium">إصدار PHP</span>
                        <span class="text-blue-300 font-bold"><?= PHP_VERSION ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-10 rounded-lg">
                        <span class="text-white font-medium">الذاكرة المستخدمة</span>
                        <span class="text-blue-300 font-bold"><?= round($metrics['memory_usage'] / 1024 / 1024, 2) ?>MB</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-10 rounded-lg">
                        <span class="text-white font-medium">الذاكرة القصوى</span>
                        <span class="text-blue-300 font-bold"><?= round($metrics['memory_peak'] / 1024 / 1024, 2) ?>MB</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-10 rounded-lg">
                        <span class="text-white font-medium">عدد الملفات</span>
                        <span class="text-blue-300 font-bold"><?= $metrics['uploads_count'] ?></span>
                    </div>
                    <?php if (isset($metrics['server_load'])): ?>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-10 rounded-lg">
                        <span class="text-white font-medium">حمل الخادم</span>
                        <span class="text-blue-300 font-bold"><?= round($metrics['server_load'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Performance Recommendations -->
        <div class="glass-card p-6 mt-8">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-lightbulb"></i> توصيات الأداء
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if ($metrics['db_query_time'] > 100): ?>
                <div class="p-4 bg-red-500 bg-opacity-20 border border-red-500 rounded-lg">
                    <h3 class="text-red-300 font-bold mb-2">⚠️ قاعدة البيانات بطيئة</h3>
                    <p class="text-white text-sm">استخدم optimize_database.php لتحسين الأداء</p>
                </div>
                <?php endif; ?>

                <?php if ($metrics['memory_usage'] > 100 * 1024 * 1024): ?>
                <div class="p-4 bg-yellow-500 bg-opacity-20 border border-yellow-500 rounded-lg">
                    <h3 class="text-yellow-300 font-bold mb-2">⚠️ استخدام ذاكرة عالي</h3>
                    <p class="text-white text-sm">فكر في تحسين الكود أو زيادة ذاكرة PHP</p>
                </div>
                <?php endif; ?>

                <?php if ($metrics['uploads_size'] > 50 * 1024 * 1024): ?>
                <div class="p-4 bg-blue-500 bg-opacity-20 border border-blue-500 rounded-lg">
                    <h3 class="text-blue-300 font-bold mb-2">💡 تحسين الصور</h3>
                    <p class="text-white text-sm">استخدم optimize_images.php لضغط الصور</p>
                </div>
                <?php endif; ?>

                <div class="p-4 bg-green-500 bg-opacity-20 border border-green-500 rounded-lg">
                    <h3 class="text-green-300 font-bold mb-2">✅ تحسينات مقترحة</h3>
                    <p class="text-white text-sm">• تفعيل Service Worker للذاكرة المؤقتة<br>• استخدام CDN للملفات الثابتة<br>• ضغط Gzip للملفات</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-center space-x-4 space-x-reverse mt-8">
            <a href="optimize_database.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                <i class="fas fa-database"></i> تحسين قاعدة البيانات
            </a>
            <a href="optimize_images.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                <i class="fas fa-images"></i> تحسين الصور
            </a>
            <a href="admin.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                <i class="fas fa-arrow-right"></i> العودة للإدارة
            </a>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
