<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "includes/db.php";
// email
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
  <title>إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

  <!-- Navbar -->
   <nav class="bg-white shadow-lg px-6 py-3 flex justify-between items-center">
    <span class="text-blue-700 font-bold text-3xl">🎓 إبداع</span>
    <div class="space-x-2 space-x-reverse">
      
<a href="profile.php" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-800 transition flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z"/>
    </svg>
    حسابي
</a>
    </div>
  </nav>

  <div class="container mx-auto mt-8 px-4">

    <h1 class="text-3xl mb-6 font-bold text-gray-800">أهلا, <?php echo htmlspecialchars($_SESSION['user']['name']); ?> 👋</h1>

    <!-- Search Box -->
    <form method="get" class="mb-6 flex gap-2">
        <input type="text" name="search" placeholder="🔍 ابحث عن المجموعة..." 
               value="<?php echo htmlspecialchars($search); ?>" 
               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">بحث</button>
    </form>

    <h2 class="text-xl mb-4 font-semibold text-gray-700">مجموعاتك:</h2>
<?php if ($groups): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($groups as $group): ?>
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-3"> <?php echo htmlspecialchars($group['name']); ?></h3>
            <p class="mb-4 text-gray-600"> عدد الطلاب: <span class="font-semibold"><?php echo $group['numStudt']; ?></span></p>
            <div class="flex gap-2">
                <a href="manage_group.php?group_id=<?= $group['id'] ?>" class="bg-green-500 text-white px-3 py-2 rounded-lg hover:bg-green-600 transition"> إدارة</a>
                <a href="dashboard.php?group_id=<?= $group['id'] ?>" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition">عرض الدرجات</a>
            </div>
        </div>
    <?php endforeach; ?>

        <!-- زر إضافة مجموعة (أيقونة فقط) -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg flex flex-col justify-center items-center border border-dashed border-blue-400 ">
            <a href="add_group.php" 
               class="bg-blue-600 text-white p-4 rounded-full hover:bg-blue-700 transition flex items-center justify-center">
                <!-- أيقونة Plus -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </a>
        </div>
    </div>

<?php else: ?>
    <p class="text-gray-600">❌ لا توجد مجموعات مرتبطة بك.</p>
    <!-- زر إضافة مجموعة لو مفيش جروبات -->
    <div class="mt-4">
        <a href="add_group.php" 
           class="bg-blue-600 text-white p-4 rounded-full hover:bg-blue-700 transition flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </a>
    </div>
<?php endif; ?>


  </div>

</body>
</html>
