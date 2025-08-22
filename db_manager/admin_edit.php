<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';

// ✅ تأكد ان اللي فاتح Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

// ✅ تأكد ان فيه ID مبعوت
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ لم يتم تحديد الأدمن");
}

$adminId = (int)$_GET['id'];

// ✅ جلب بيانات الأدمن
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("❌ الأدمن غير موجود");
}

// ✅ لو الفورم اتبعت (تحديث البيانات)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role  = trim($_POST['role']);

    if (empty($name) || empty($email)) {
        $_SESSION['error'] = "❌ كل الحقول مطلوبة";
    } else {
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, role = ? WHERE id = ?");
        $updated = $stmt->execute([$name, $email, $role, $adminId]);

        if ($updated) {
            $_SESSION['success'] = "✅ تم تحديث بيانات الأدمن بنجاح";
            header("Location: db_manager.php");
            exit;
        } else {
            $_SESSION['error'] = "❌ حدث خطأ أثناء التحديث";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل الأدمن</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">

    <div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">✏️ تعديل بيانات الأدمن</h1>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="bg-red-200 text-red-800 p-2 rounded mb-2">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block mb-1">الاسم</label>
                <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block mb-1">البريد الإلكتروني</label>
                <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" class="w-full border p-2 rounded" required>
            </div>

         

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">💾 حفظ التغييرات</button>
            <a href="db_manager.php" class="ml-2 text-gray-600">إلغاء</a>
        </form>
    </div>

</body>
</html>
