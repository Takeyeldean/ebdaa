<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الصفحة غير موجودة - إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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
            animation: gradientShift 12s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .error-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 4rem 2rem;
            max-width: 600px;
            margin: 2rem;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 1rem;
        }

        .error-message {
            font-size: 1.2rem;
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-home {
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(30, 64, 175, 0.4);
        }

        .emoji {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="emoji">😵</div>
        <div class="error-code">404</div>
        <h1 class="error-title">الصفحة غير موجودة</h1>
        <p class="error-message">
            عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.<br>
            تحقق من الرابط أو عد إلى الصفحة الرئيسية.
        </p>
        <a href="/" class="btn-home">
            <i class="fas fa-home"></i>
            العودة للصفحة الرئيسية
        </a>
    </div>
</body>
</html>
