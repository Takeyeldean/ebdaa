<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';

// تأكد أن المستخدم Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}
// username
// جلب البيانات من الجداول
$stmt = $conn->prepare("
    SELECT students.*, groups.name AS group_name
    FROM students
    JOIN groups ON students.group_id = groups.id
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admins   = $conn->query("SELECT * FROM admins")->fetchAll(PDO::FETCH_ASSOC);
$groups   = $conn->query("SELECT * FROM groups")->fetchAll(PDO::FETCH_ASSOC);

$group_admins = $conn->query("
    SELECT ga.group_id, ga.admin_id, g.name AS group_name, a.name AS admin_name
    FROM group_admins ga
    JOIN groups g ON ga.group_id = g.id
    JOIN admins a ON ga.admin_id = a.id
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Database Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-primary {
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
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
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="nav-glass px-6 py-4 flex justify-between items-center">
        <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            ⚡ إبداع
        </span>
        
        <div class="space-x-2 space-x-reverse">
            <a href="../admin.php" class="btn-primary">
                <i class="fas fa-users"></i>
                المجموعات
            </a>
            <a href="../admin_questions.php" class="btn-primary">
                <i class="fas fa-question-circle"></i>
                الأسئلة
            </a>
            <a href="../admin_invitations.php" class="btn-primary">
                <i class="fas fa-envelope"></i>
                الدعوات
            </a>
            <a href="../profile.php" class="btn-primary">
                <i class="fas fa-user"></i>
                حسابي
            </a>
        </div>
    </nav>

    <div class="p-6">
        <h1 class="text-2xl font-bold mb-6"> Database Manager</h1>

    <!-- الطلاب -->
    <h2 class="text-xl font-semibold mb-3"> Students</h2>
    <table class="table-auto border-collapse border border-gray-400 mb-6 w-full bg-white shadow rounded">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">ID</th>
                <th class="p-2 border">Name</th>
                <th class="p-2 border">username</th>
                <th class="p-2 border">Group</th>
                <th class="p-2 border">Actions</th>
            </tr>
        </thead>
        <tbody>
           <?php foreach ($students as $s): ?>
    <tr>
        <td class="p-2 border"><?= $s['id'] ?></td>
        <td class="p-2 border"><?= htmlspecialchars($s['name']) ?></td>
        <td class="p-2 border"><?= htmlspecialchars($s['username']) ?></td>
        <td class="p-2 border"><?= htmlspecialchars($s['group_name']) ?></td> <!-- ✅ اسم المجموعة -->
        <td class="p-2 border">
            <a href="student_edit.php?id=<?= $s['id'] ?>" class="text-blue-500">Edit</a> | 
            <a href="student_delete.php?id=<?= $s['id'] ?>" class="text-red-500" onclick="return confirm('Delete student?')">Delete</a>
        </td>
    </tr>
<?php endforeach; ?>

        </tbody>
    </table>

    <!-- الأدمن -->
    <h2 class="text-xl font-semibold mb-3">Admins</h2>
    <table class="table-auto border-collapse border border-gray-400 mb-6 w-full bg-white shadow rounded">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">ID</th>
                <th class="p-2 border">Name</th>
                <th class="p-2 border">username</th>
                <th class="p-2 border">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $a): ?>
                <tr>
                    <td class="p-2 border"><?= $a['id'] ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($a['name']) ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($a['username']) ?></td>
                    <td class="p-2 border">
                        <a href="admin_edit.php?id=<?= $a['id'] ?>" class="text-blue-500">Edit</a> | 
                        <a href="admin_delete.php?id=<?= $a['id'] ?>" class="text-red-500" onclick="return confirm('Delete admin?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

            <!-- الجروبات-->
   <h2 class="text-xl font-semibold mb-3"> Groups</h2>
<table class="table-auto border-collapse border border-gray-400 mb-6 w-full bg-white shadow rounded">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Name</th>
            <th class="p-2 border">Number of Students</th>
            <th class="p-2 border">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($groups as $g): ?>
            <tr>
                <td class="p-2 border"><?= $g['id'] ?></td>
                <td class="p-2 border"><?= htmlspecialchars($g['name']) ?></td>
                <td class="p-2 border"><?= htmlspecialchars($g['numStudt']) ?></td> <!-- ✅ تأكد من الاسم في قاعدة البيانات -->
                <td class="p-2 border">
                    <a href="group_edit.php?id=<?= $g['id'] ?>" class="text-blue-500">Edit</a> | 
                    <a href="group_delete.php?id=<?= $g['id'] ?>" class="text-red-500" onclick="return confirm('Delete group?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


    <!-- الربط بين الأدمن والجروبات -->
    <h2 class="text-xl font-semibold mb-3"> Group Admins</h2>
    <table class="table-auto border-collapse border border-gray-400 mb-6 w-full bg-white shadow rounded">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Group ID - Admin ID</th>
                <th class="p-2 border">Group Name</th>
                <th class="p-2 border">Admin Name</th>
                <th class="p-2 border">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($group_admins as $ga): ?>
                <tr>
                    <td class="p-2 border"><?= $ga['group_id'] ?> - <?= $ga['admin_id'] ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($ga['group_name']) ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($ga['admin_name']) ?></td>
                    <td class="p-2 border">
                        <a href="group_admin_edit.php?group_id=<?= $ga['group_id'] ?>&admin_id=<?= $ga['admin_id'] ?>" class="text-blue-500">Edit</a> | 
                        <a href="group_admin_delete.php?group_id=<?= $ga['group_id'] ?>&admin_id=<?= $ga['admin_id'] ?>" class="text-red-500" onclick="return confirm('Delete this relation?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
