<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "includes/db.php";

// ✅ لازم تتأكد أن الأدمن مسجل دخول
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['user']['id']; 

// ✅ البحث
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// ✅ جلب الجروبات اللي هذا الأدمن مشرف عليها
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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }
    .search-box {
        margin-bottom: 20px;
    }
    .group-card {
        border: 1px solid #ccc;
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
        background: #f9f9f9;
    }
    .group-card h3 {
        margin: 0;
    }
    .btn {
        display: inline-block;
        padding: 8px 12px;
        background: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }
    .btn:hover {
        background: #0056b3;
    }
  </style>
</head>
<body>

  <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?> 👋</h1>
  <a href="logout.php" class="btn">Logout</a>

  <div class="search-box">
    <form method="get">
      <input type="text" name="search" placeholder="Search group by name..." value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit" class="btn">Search</button>
    </form>
  </div>

  <h2>Your Groups:</h2>
  <?php if ($groups): ?>
      <?php foreach ($groups as $group): ?>
        <div class="group-card">
          <h3><?php echo htmlspecialchars($group['name']); ?></h3>
          <p>Students: <?php echo $group['numStudt']; ?></p>
          <a href="manage_group.php?group_id=<?= $group['id'] ?>" class="btn">Manage Group</a>
          <a href="dashboard.php?group_id=<?= $group['id'] ?>" class="btn">الدرجات</a>

        </div>
      <?php endforeach; ?>
  <?php else: ?>
      <p>No groups found.</p>
  <?php endif; ?>

</body>
</html>
