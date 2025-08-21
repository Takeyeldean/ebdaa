<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "includes/db.php";

// التأكد أن الأدمن مسجل دخول
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['user']['id']; 

// البحث
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// جلب الجروبات اللي هذا الأدمن مشرف عليها
$sql = "
    SELECT g.id, g.name, g.numStudt
    FROM groups g
    INNER JOIN group_admins ga ON g.id = ga.group_id
    WHERE ga.admin_id = :admin_id
";

if ($search !== "") {
    $sql .= " AND g.name LIKE :search";
}

$stmt = $conn->prepare($sql);
$stmt->bindParam(":admin_id", $admin_id, PDO::PARAM_INT);

if ($search !== "") {
    $likeSearch = "%$search%";
    $stmt->bindParam(":search", $likeSearch, PDO::PARAM_STR);
}

$stmt->execute();
$groups = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة درجات الطلاب</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

  <!-- Navbar -->
    <nav class="bg-white shadow-md px-6 py-3 flex justify-between items-center">
    <span class="text-blue-600 font-bold text-2xl">🎓 إبداع - إدارة المجموعة</span>
    
    <div>
        <!-- <a href="admin.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">المجموعات</a> -->
        <a href="profile.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">حسابي</a>

    </div>

  
</nav>

  <div class="container mx-auto mt-8 px-4">

    <h1 class="text-3xl mb-4">أهلا, <?php echo htmlspecialchars($_SESSION['user']['name']); ?> 👋</h1>

    <!-- Search Box -->
    <form method="get" class="mb-6 flex gap-2">
        <input type="text" name="search" placeholder="ابحث عن المجموعة..." 
               value="<?php echo htmlspecialchars($search); ?>" 
               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">بحث</button>
    </form>

    <h2 class="text-xl mb-3 font-semibold">المجموعات:</h2>

    <?php if ($groups): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($groups as $group): ?>
            <div class="bg-white p-4 rounded-lg shadow hover:shadow-lg transition">
                <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($group['name']); ?></h3>
                <p class="mb-4">عدد الطلاب: <?php echo $group['numStudt']; ?></p>
                <div class="flex gap-2">
                    <a href="manage_group.php?group_id=<?= $group['id'] ?>" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 transition">إدارة المجموعة</a>
                    <a href="dashboard.php?group_id=<?= $group['id'] ?>" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">الدرجات</a>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600">لا توجد مجموعات.</p>
    <?php endif; ?>

    <!-- <div class="mt-6">
        <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">تسجيل الخروج</a>
    </div> -->

  </div>

</body>
</html>
