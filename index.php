<?php
session_start();
if (isset($_SESSION['user'])) {
  header("Location: " . ($_SESSION['user']['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
  exit();
}
// username
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إبداع - مرحباً بك! 🌟</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Cairo', Arial, sans-serif;
      background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 25%, #3b82f6 50%, #06b6d4 75%, #10b981 100%);
      background-size: 400% 400%;
      animation: gradientShift 8s ease infinite;
      min-height: 100vh;
      overflow-x: hidden;
    }

    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    /* Floating animations */
    .floating {
      animation: floating 3s ease-in-out infinite;
    }

    @keyframes floating {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }

    .floating-delayed {
      animation: floating 3s ease-in-out infinite;
      animation-delay: 1.5s;
    }

    /* Navbar */
    nav {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-radius: 0 0 25px 25px;
    }

    nav span {
      font-weight: 800;
      font-size: 2rem;
      background: linear-gradient(45deg, #1e40af, #3b82f6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    nav a {
      background: linear-gradient(45deg, #1e40af, #3b82f6);
      color: #fff;
      padding: 12px 24px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
    }

    nav a:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
    }

    /* Login container */
    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: calc(100vh - 100px);
      padding: 20px;
    }

    /* Login box */
    .login-box {
      max-width: 450px;
      width: 100%;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      padding: 50px 40px;
      border-radius: 30px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: relative;
      overflow: hidden;
    }

    .login-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, #1e40af, #3b82f6, #06b6d4, #10b981, #f59e0b);
      background-size: 200% 100%;
      animation: gradientShift 3s ease infinite;
    }

    .login-box h2 {
      text-align: center;
      margin-bottom: 40px;
      color: #333;
      font-size: 2.5rem;
      font-weight: 800;
      background: linear-gradient(45deg, #1e40af, #3b82f6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .welcome-text {
      text-align: center;
      color: #666;
      font-size: 1.1rem;
      margin-bottom: 30px;
      font-weight: 500;
    }

    .form-group {
      position: relative;
      margin-bottom: 25px;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 18px 20px 18px 55px;
      border: 2px solid #e1e5e9;
      border-radius: 15px;
      outline: none;
      font-size: 16px;
      font-family: 'Cairo', sans-serif;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.8);
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
      transform: translateY(-2px);
    }

    .form-group i {
      position: absolute;
      top: 50%;
      right: 20px;
      transform: translateY(-50%);
      color: #3b82f6;
      font-size: 18px;
    }

    select {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      background: url('data:image/svg+xml;utf8,<svg fill="%233b82f6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat;
      background-position: 20px center;
      padding-right: 55px;
      padding-left: 20px;
    }

    .login-btn {
      width: 100%;
      padding: 18px;
      background: linear-gradient(45deg, #1e40af, #3b82f6);
      color: #fff;
      font-size: 18px;
      font-weight: 700;
      border: none;
      border-radius: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Cairo', sans-serif;
      box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
      position: relative;
      overflow: hidden;
    }

    .login-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }

    .login-btn:hover::before {
      left: 100%;
    }

    .login-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(30, 64, 175, 0.4);
    }

    .login-btn:active {
      transform: translateY(-1px);
    }

    /* Decorative elements */
    .decoration {
      position: absolute;
      pointer-events: none;
    }

    .decoration-icon {
      color: #f59e0b;
      font-size: 2rem;
      animation: twinkle 2s ease-in-out infinite;
    }

    .decoration-icon:nth-child(2) { animation-delay: 0.5s; }
    .decoration-icon:nth-child(3) { animation-delay: 1s; }

    @keyframes twinkle {
      0%, 100% { opacity: 0.3; transform: scale(1); }
      50% { opacity: 1; transform: scale(1.2); }
    }

    .decoration-1 { top: 10%; left: 10%; }
    .decoration-2 { top: 20%; right: 15%; }
    .decoration-3 { bottom: 20%; left: 20%; }
    .decoration-4 { bottom: 10%; right: 10%; }

    /* Responsive design */
    @media (max-width: 768px) {
      .login-box {
        padding: 30px 25px;
        margin: 20px;
      }
      
      .login-box h2 {
        font-size: 2rem;
      }
      
      nav {
        padding: 12px 20px;
      }
      
      nav span {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>

 

  <!-- Decorative elements for boys -->
  <div class="decoration decoration-1">
    <span class="decoration-icon">⚡</span>
  </div>
  <div class="decoration decoration-2">
    <span class="decoration-icon">🔥</span>
  </div>
  <div class="decoration decoration-3">
    <span class="decoration-icon">⚽</span>
  </div>
  <div class="decoration decoration-4">
    <span class="decoration-icon">🎮</span>
  </div>

  <div class="login-container">
    <div class="login-box floating">
      <h2>⚡ مرحباً بك في إبداع! ⚡</h2>
      <p class="welcome-text">دعنا نبدأ مغامرة التعلم الرائعة! 🚀</p>
      
      <form method="POST" action="login.php">
        <!-- اختيار الدور -->
        <div class="form-group">
          <i class="fas fa-user-shield"></i>
          <select name="role" required>
            <option value="">🎯 اختر دورك: طالب أم مشرف؟</option>
            <option value="student">🎓 طالب</option>
            <option value="admin">👨‍🏫 مشرف</option>
          </select>
        </div>
        
        <div class="form-group">
          <i class="fas fa-user"></i>
          <input type="text" name="username" placeholder="👤 اسم المستخدم" required>
        </div>
        
        <div class="form-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" placeholder="🔒 كلمة المرور" required>
        </div>
        
        <button type="submit" class="login-btn">
          ⚡ هيا نبدأ المغامرة! ⚡
        </button>
      </form>
    </div>
  </div>
</body>
</html>
