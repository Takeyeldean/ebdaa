<?php
session_start();
if (isset($_SESSION['user'])) {
  header("Location: " . ($_SESSION['user']['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | Student Portal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(90deg, #fff7ad, #ffa9f9);
      margin: 0;
      padding: 0;
    }

    /* Navbar */
    nav {
      background-color: #fff;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      padding: 12px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    nav span {
      font-weight: bold;
      font-size: 1.5rem;
      color: #2563eb;
    }
    nav a {
      background-color: #2563eb;
      color: #fff;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      transition: background 0.3s;
    }
    nav a:hover {
      background-color: #1d4ed8;
    }

    /* Login box */
    .login-box {
      max-width: 400px;
      margin: 80px auto;
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    .login-box h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #2563eb;
      font-size: 28px;
    }

    .form-group {
      position: relative;
      margin-bottom: 20px;
    }
    .form-group input
     {
      width: 86%;
      padding: 12px 15px 12px 40px;
      border: 1px solid #ccc;
      border-radius: 8px;
      outline: none;
      font-size: 15px;
    }
    .form-group select{

      width: 100%;
      padding: 12px 15px 12px 40px;
      border: 1px solid #ccc;
      border-radius: 8px;
      outline: none;
      font-size: 15px;
    }
    .form-group i {
      position: absolute;
      top: 50%;
      left: 12px;
      transform: translateY(-50%);
      color: #aaa;
    }
    select {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      background: url('data:image/svg+xml;utf8,<svg fill="%23aaa" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat;
      background-position: 10px center;
      padding-left: 40px;
      padding-right: 35px;
    }

    .login-btn {
      width: 100%;
      padding: 12px;
      background-color: #2563eb;
      color: #fff;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .login-btn:hover {
      background-color: #1d4ed8;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gradient-to-b from-yellow-300 via-orange-400 to-orange-600 min-h-screen font-sans">

  <nav class="bg-white shadow-md px-6 py-3 flex justify-between items-center">
    
    <div>
      <!-- <a href="admin.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">المجموعات</a> -->
      <!-- <a href="profile.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">حسابي</a> -->
      
    </div>
    
    <span class="text-blue-600 font-bold text-2xl">🎓 إبداع </span>
  
</nav>

  <div class="login-box">
    <h2>تسجيل الدخول</h2>
    <form method="POST" action="login.php">
      <!-- اختيار الدور -->
      <div class="form-group">
        <i class="fas fa-user-shield"></i>
        <select name="role" required>
          <option value="">طالب / مشرف</option>
          <option value="student">طالب</option>
          <option value="admin">مشرف</option>
        </select>
      </div>
      <div class="form-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="البريد لإلكتروني" required>
      </div>
      <div class="form-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="كلمة المرور" required>
      </div>
      <button type="submit" class="login-btn">Login</button>
    </form>
    
  </div>
</body>
</html>
