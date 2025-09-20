<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';
// email
// ✅ تحقق أن المستخدم أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

// ✅ جلب المجموعات والمديرين
$groups = $conn->query("SELECT * FROM groups")->fetchAll(PDO::FETCH_ASSOC);
$admins = $conn->query("SELECT id, username FROM admins")->fetchAll(PDO::FETCH_ASSOC);


// ✅ عند إضافة علاقة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $group_id = (int)($_POST['group_id'] ?? 0);
    $admin_id = (int)($_POST['admin_id'] ?? 0);

    if ($group_id && $admin_id) {
        $check = $conn->prepare("SELECT 1 FROM group_admins WHERE group_id = ? AND admin_id = ?");
        $check->execute([$group_id, $admin_id]);

        if ($check->rowCount() === 0) {
            $stmt = $conn->prepare("INSERT INTO group_admins (group_id, admin_id) VALUES (?, ?)");
            $stmt->execute([$group_id, $admin_id]);
            $_SESSION['success'] = "✅ تم ربط الأدمن بالمجموعة";
        } else {
            $_SESSION['error'] = "⚠️ العلاقة موجودة بالفعل";
        }
    }
    header("Location: group_admin_edit.php");
    exit;
}

// ✅ حذف علاقة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $group_id = (int)($_POST['group_id'] ?? 0);
    $admin_id = (int)($_POST['admin_id'] ?? 0);

    if ($group_id && $admin_id) {
        $stmt = $conn->prepare("DELETE FROM group_admins WHERE group_id = ? AND admin_id = ?");
        $stmt->execute([$group_id, $admin_id]);
        $_SESSION['success'] = "🗑️ تم حذف العلاقة";
    }
    header("Location: group_admin_edit.php");
    exit;
}

// ✅ جلب العلاقات
$groupAdmins = $conn->query("
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
    <title>إدارة ربط الأدمن بالمجموعات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="p-6 bg-gray-100">

    <h1 class="text-2xl font-bold mb-4">إدارة ربط الأدمن بالمجموعات</h1>

    <!-- رسائل -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="bg-green-200 text-green-800 p-2 mb-4 rounded"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="bg-red-200 text-red-800 p-2 mb-4 rounded"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- نموذج إضافة علاقة -->
    <form method="POST" class="mb-6 p-4 bg-white shadow rounded">
        <input type="hidden" name="action" value="add">
        <label class="block mb-2">اختر المجموعة:</label>
        <select name="group_id" class="p-2 border rounded w-full mb-4">
            <?php foreach ($groups as $g): ?>
                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="block mb-2">اختر الأدمن:</label>
<select name="admin_id" class="p-2 border rounded w-full mb-4">
    <?php foreach ($admins as $a): ?>
        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['username']) ?></option>
    <?php endforeach; ?>
</select>


        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">➕ إضافة علاقة</button>
    </form>

    <!-- عرض العلاقات -->
    <h2 class="text-xl font-semibold mb-2">العلاقات الحالية:</h2>
    <table class="table-auto w-full bg-white shadow rounded">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2 border">المجموعة</th>
                <th class="p-2 border">الأدمن</th>
                <th class="p-2 border">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groupAdmins as $ga): ?>
                <tr>
                    <td class="p-2 border"><?= htmlspecialchars($ga['group_name']) ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($ga['admin_name']) ?></td>
                    <td class="p-2 border text-center">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="group_id" value="<?= $ga['group_id'] ?>">
                            <input type="hidden" name="admin_id" value="<?= $ga['admin_id'] ?>">
                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded">❌ حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
