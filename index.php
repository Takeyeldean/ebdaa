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
    .login-box {
      max-width: 400px;
      margin: 60px auto;
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    .login-box h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #2f80ed;
    }
    .form-group {
      position: relative;
      margin-bottom: 20px;
    }
    .form-group input, .form-group select {
      width: 85%;
      padding: 12px 15px 12px 40px;
      border: 1px solid #ccc;
      border-radius: 8px;
      outline: none;
      font-size: 15px;
    }
    .form-group i {
      position: absolute;
      top: 12px;
      left: 12px;
      color: #aaa;
    }
    .login-btn {
      width: 100%;
      padding: 12px;
      background-color: #2f80ed;
      color: #fff;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .login-btn:hover {
      background-color: #1e5ecd;
    }
    .login-footer {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: #777;
    }
    .login-footer a {
      color: #2f80ed;
      text-decoration: none;
    }
    .login-footer a:hover {
      text-decoration: underline;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
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
        <input type="email" name="email" placeholder="Email address" required>
      </div>
      <div class="form-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Password" required>
      </div>
      <button type="submit" class="login-btn">Login</button>
    </form>
    
  </div>
</body>
</html>
