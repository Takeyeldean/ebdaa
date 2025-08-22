<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../includes/db.php';

// ✅ تأكد أن المستخدم أدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("❌ غير مسموح لك بالدخول");
}

// ✅ تأكد أن الطالب محدد
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ لم يتم تحديد الطالب");
}

$studentId = (int)$_GET['id'];

// ✅ جلب بيانات الطالب
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("❌ الطالب غير موجود");
}

// ✅ في حالة حفظ التعديلات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $group_id = (int)$_POST['group_id'];

    $profile_image = $student['profile_image']; // الصورة القديمة

    // ✅ لو تم رفع صورة جديدة
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['profile_image']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            // ✅ امسح الصورة القديمة لو مش default.jpeg
            if (!empty($profile_image) && $profile_image !== 'default.jpeg') {
                $oldPath = $uploadDir . $profile_image;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $profile_image = $fileName;
        }
    }

    // ✅ تحديث بيانات الطالب
    $stmt = $conn->prepare("UPDATE students SET name=?, email=?, group_id=?, profile_image=? WHERE id=?");
    $updated = $stmt->execute([$name, $email, $group_id, $profile_image, $studentId]);

    if ($updated) {
        $_SESSION['success'] = "✅ تم تعديل بيانات الطالب بنجاح";
        header("Location: db_manager.php");
        exit;
    } else {
        $_SESSION['error'] = "❌ حدث خطأ أثناء التعديل";
    }
}

// ✅ جلب المجموعات لعرضها في القائمة
$groups = $conn->query("SELECT * FROM groups")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل الطالب</title>
</head>
<body>
    <h2>✏️ تعديل بيانات الطالب</h2>

    <form method="post" enctype="multipart/form-data">
        <label>الاسم:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required><br><br>

        <label>البريد:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required><br><br>

        <label>المجموعة:</label>
        <select name="group_id" required>
            <?php foreach ($groups as $group): ?>
                <option value="<?= $group['id'] ?>" <?= $student['group_id'] == $group['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($group['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>الصورة الحالية:</label><br>
        <img src="uploads/<?= htmlspecialchars($student['profile_image']) ?>" width="100"><br><br>

        <label>تغيير الصورة:</label>
        <input type="file" name="profile_image"><br><br>

        <button type="submit">💾 حفظ التعديلات</button>
    </form>

    <br>
    <a href="db_manager.php">🔙 رجوع</a>
</body>
</html>
