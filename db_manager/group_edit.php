<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';
// email
// ✅ لازم يكون الأدمن مسجل دخول
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

// ✅ تحقق من ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ لم يتم تحديد المجموعة");
}

$groupId = (int)$_GET['id'];

// ✅ جلب بيانات المجموعة
$stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    die("❌ المجموعة غير موجودة");
}

// ✅ لو تم الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $numStudt = (int)$_POST['numStudt'];

    if (empty($name)) {
        $error = "❌ من فضلك أدخل اسم المجموعة";
    } elseif ($numStudt < 0) {
        $error = "❌ عدد الطلاب يجب أن يكون رقم موجب";
    } else {
        $stmt = $conn->prepare("UPDATE groups SET name = ?, numStudt = ? WHERE id = ?");
        $updated = $stmt->execute([$name, $numStudt, $groupId]);

        if ($updated) {
            $_SESSION['success'] = "✅ تم تعديل المجموعة بنجاح";
            header("Location: db_manager.php");
            exit;
        } else {
            $error = "❌ حدث خطأ أثناء التعديل";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل المجموعة</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4">✏️ تعديل المجموعة</h2>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-2 mb-4 rounded"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block font-medium">اسم المجموعة</label>
                <input type="text" name="name" value="<?= htmlspecialchars($group['name']) ?>" 
                       class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block font-medium">عدد الطلاب</label>
                <input type="number" name="numStudt" value="<?= htmlspecialchars($group['numStudt']) ?>" 
                       class="w-full border rounded p-2" min="0" required>
            </div>

            <div class="flex justify-between">
                <a href="db_manager.php" class="bg-gray-500 text-white px-4 py-2 rounded">رجوع</a>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">حفظ</button>
            </div>
        </form>
    </div>
</body>
</html>
            