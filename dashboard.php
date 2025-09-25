<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
require_once 'includes/db.php';
require_once 'includes/url_helper.php';
// email
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$role = $_SESSION['user']['role'];  

if ($_SESSION['user']['role'] === 'admin') {
    $group_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['group_id']) ? intval($_GET['group_id']) : 0);
    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, degree, profile_image FROM students WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll();
        
        // Get group message and emoji
        $stmt = $conn->prepare("SELECT message, emoji FROM groups WHERE id = ?");
        $stmt->execute([$group_id]);
        $group_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $group_message = $group_data['message'] ?? '';
        $group_emoji = $group_data['emoji'] ?? '🤖';
    } else {
        $students = [];
        $group_message = '';
        $group_emoji = '🤖';
    }
} else if ($_SESSION['user']['role'] === 'student') {
    $student_id = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT group_id, profile_image FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo "❌ لم يتم العثور على بيانات الطالب.";
        exit;
    }
    
    $group_id = $row['group_id'];
    $profile_image = $row['profile_image'] ?? 'default.png';

    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, degree, profile_image FROM students WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll();
        
        // Get group message and emoji
        $stmt = $conn->prepare("SELECT message, emoji FROM groups WHERE id = ?");
        $stmt->execute([$group_id]);
        $group_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $group_message = $group_data['message'] ?? '';
        $group_emoji = $group_data['emoji'] ?? '🤖';
    } else {
        $students = [];
        $group_message = '';
        $group_emoji = '🤖';
    }
} else {
    echo "❌ غير مسموح لك بالدخول.";
    exit;
}

// ---------------- إضافة الترتيب ----------------
usort($students, function($a, $b) {
    return $b['degree'] <=> $a['degree']; // sort descending
});

$ranks = [];
$rank = 1;
foreach ($students as $s) {
    $ranks[$s['id']] = $rank++;
}

// تجهيز البيانات للرسم
$labels = [];
$data = [];
$images = [];
foreach ($students as $student) {
    $labels[] = $student['name'] . " #" . $ranks[$student['id']] ;
    $data[] = $student['degree'];
    $images[] = $student['profile_image'] ?? 'default.png';
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إبداع - لوحة التحكم 🎯</title>
  <!-- Preload critical resources for faster loading -->
  <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" as="style">
  <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  
  <!-- Load optimized CSS asynchronously -->
  <link rel="preload" href="assets/css/optimized.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="assets/css/optimized.css"></noscript>
  <style>

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

     body {
       font-family: 'Cairo', Arial, sans-serif;
       background: #000011;
       background-image: 
         radial-gradient(ellipse at 20% 20%, rgba(138, 43, 226, 0.4) 0%, transparent 50%),
         radial-gradient(ellipse at 80% 80%, rgba(255, 20, 147, 0.3) 0%, transparent 50%),
         radial-gradient(ellipse at 40% 60%, rgba(0, 191, 255, 0.2) 0%, transparent 50%),
         radial-gradient(ellipse at 60% 30%, rgba(255, 105, 180, 0.25) 0%, transparent 50%),
         radial-gradient(ellipse at 10% 70%, rgba(138, 43, 226, 0.2) 0%, transparent 50%);
       background-size: 100% 100%, 100% 100%, 100% 100%, 100% 100%, 100% 100%;
       animation: galaxyShift 20s ease-in-out infinite;
       min-height: 100vh;
       overflow-x: hidden;
       position: relative;
     }

     @keyframes galaxyShift {
       0%, 100% { 
         background-position: 0% 0%, 100% 100%, 50% 50%, 25% 75%, 75% 25%;
       }
       25% { 
         background-position: 25% 25%, 75% 75%, 75% 25%, 50% 50%, 25% 75%;
       }
       50% { 
         background-position: 50% 50%, 50% 50%, 25% 75%, 75% 25%, 50% 50%;
       }
       75% { 
         background-position: 75% 75%, 25% 25%, 50% 50%, 25% 75%, 75% 25%;
       }
     }

     /* Galaxy Stars Background */
     body::before {
       content: '';
       position: fixed;
       top: 0;
       left: 0;
       width: 100%;
       height: 100%;
       background-image: 
         radial-gradient(2px 2px at 20px 30px, #eee, transparent),
         radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 90px 40px, #fff, transparent),
         radial-gradient(1px 1px at 130px 80px, rgba(255,255,255,0.6), transparent),
         radial-gradient(2px 2px at 160px 30px, #eee, transparent),
         radial-gradient(1px 1px at 200px 60px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 250px 20px, #fff, transparent),
         radial-gradient(1px 1px at 300px 90px, rgba(255,255,255,0.6), transparent),
         radial-gradient(2px 2px at 350px 50px, #eee, transparent),
         radial-gradient(1px 1px at 400px 10px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 450px 70px, #fff, transparent),
         radial-gradient(1px 1px at 500px 30px, rgba(255,255,255,0.6), transparent),
         radial-gradient(2px 2px at 550px 80px, #eee, transparent),
         radial-gradient(1px 1px at 600px 40px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 650px 10px, #fff, transparent),
         radial-gradient(1px 1px at 700px 60px, rgba(255,255,255,0.6), transparent),
         radial-gradient(2px 2px at 750px 20px, #eee, transparent),
         radial-gradient(1px 1px at 800px 90px, rgba(255,255,255,0.8), transparent),
         radial-gradient(1px 1px at 850px 50px, #fff, transparent),
         radial-gradient(1px 1px at 900px 10px, rgba(255,255,255,0.6), transparent);
       background-repeat: repeat;
       background-size: 200px 100px, 300px 150px, 400px 200px, 500px 250px, 600px 300px, 700px 350px, 800px 400px, 900px 450px, 1000px 500px, 1100px 550px, 1200px 600px, 1300px 650px, 1400px 700px, 1500px 750px, 1600px 800px, 1700px 850px, 1800px 900px, 1900px 950px, 2000px 1000px, 2100px 1050px;
       animation: twinkle 4s ease-in-out infinite alternate;
       pointer-events: none;
       z-index: 1;
     }

     @keyframes twinkle {
       0% { opacity: 0.3; }
       100% { opacity: 1; }
     }

     /* Galaxy Nebula Effects */
     body::after {
       content: '';
       position: fixed;
       top: 0;
       left: 0;
       width: 100%;
       height: 100%;
       background-image: 
         radial-gradient(ellipse 400px 200px at 10% 20%, rgba(138, 43, 226, 0.1) 0%, transparent 70%),
         radial-gradient(ellipse 300px 150px at 90% 80%, rgba(255, 20, 147, 0.08) 0%, transparent 70%),
         radial-gradient(ellipse 500px 250px at 30% 70%, rgba(0, 191, 255, 0.06) 0%, transparent 70%),
         radial-gradient(ellipse 350px 175px at 70% 30%, rgba(255, 105, 180, 0.07) 0%, transparent 70%);
       background-size: 100% 100%;
       animation: nebulaFloat 15s ease-in-out infinite;
       pointer-events: none;
       z-index: 2;
     }

     @keyframes nebulaFloat {
       0%, 100% { 
         transform: translate(0, 0) rotate(0deg);
         opacity: 0.6;
       }
       25% { 
         transform: translate(-20px, -10px) rotate(1deg);
         opacity: 0.8;
       }
       50% { 
         transform: translate(10px, -20px) rotate(-1deg);
         opacity: 0.7;
       }
       75% { 
         transform: translate(-10px, 15px) rotate(0.5deg);
         opacity: 0.9;
       }
     }

    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .floating {
      animation: floating 3s ease-in-out infinite;
    }

    @keyframes floating {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }

    .bounce-in {
      animation: bounceIn 0.8s ease-out;
    }

    @keyframes bounceIn {
      0% { transform: scale(0.3); opacity: 0; }
      50% { transform: scale(1.05); }
      70% { transform: scale(0.9); }
      100% { transform: scale(1); opacity: 1; }
    }

    .pulse {
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

        .nav-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 0 0 25px 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 10000;
        }

        /* Mobile hamburger menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1e40af;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 10001;
        }

        .mobile-menu-btn:hover {
            background: rgba(30, 64, 175, 0.1);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.2);
        }

        .mobile-menu-btn:active {
            transform: scale(0.95);
        }

        .mobile-nav-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 0 0 25px 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            padding: 20px;
            z-index: 9999;
        }

        .mobile-nav-menu.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        .mobile-nav-menu.active .mobile-nav-links .btn-primary {
            animation: fadeInUp 0.4s ease-out;
            animation-fill-mode: both;
        }

        .mobile-nav-menu.active .mobile-nav-links .btn-primary:nth-child(1) { animation-delay: 0.1s; }
        .mobile-nav-menu.active .mobile-nav-links .btn-primary:nth-child(2) { animation-delay: 0.2s; }
        .mobile-nav-menu.active .mobile-nav-links .btn-primary:nth-child(3) { animation-delay: 0.3s; }
        .mobile-nav-menu.active .mobile-nav-links .btn-primary:nth-child(4) { animation-delay: 0.4s; }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-nav-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .mobile-nav-links .btn-primary {
            justify-content: center !important;
            width: 100% !important;
            padding: 16px 24px !important;
            font-size: 1rem !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .mobile-nav-links .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .mobile-nav-links .btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3) !important;
            background: linear-gradient(45deg, #1e3a8a, #2563eb) !important;
        }

        .mobile-nav-links .btn-primary:hover::before {
            left: 100% !important;
        }

        .mobile-nav-links .btn-primary:active {
            transform: translateY(0) !important;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.2) !important;
        }

        /* ========================================
           MOBILE RESPONSIVE STYLES - Tablet & Mobile (768px and below)
           ======================================== */
        @media (max-width: 768px) {
            
            /* ========================================
               LAYOUT ADJUSTMENTS - Reduce spacing for mobile
               ======================================== */
            .container {
                padding: 8px !important;        /* Reduce container padding for more content space */
            }
            
            .nav-glass {
                padding: 12px 16px !important;  /* Smaller navbar padding */
            }

            /* ========================================
               NAVIGATION SWITCH - Desktop to Mobile Menu
               ======================================== */
            .desktop-nav {
                display: none;                  /* Hide desktop navigation */
            }

            .mobile-menu-btn {
                display: block;
            }

            /* Make text smaller on mobile */
            .text-4xl {
                font-size: 1.5rem; /* 24px instead of 36px */
            }

            /* ========================================
               TYPOGRAPHY SCALING - Make text readable on small screens
               ======================================== */
            .text-5xl {
                font-size: 1.5rem !important;   /* 24px instead of 48px - Main headings */
            }

            .text-4xl {
                font-size: 1.25rem !important;  /* 20px instead of 36px - Sub headings */
            }

            .text-3xl {
                font-size: 1.125rem !important; /* 18px instead of 30px - Section titles */
            }

            .text-2xl {
                font-size: 1rem !important;     /* 16px instead of 24px - Card titles */
            }

            .text-xl {
                font-size: 0.875rem !important; /* 14px instead of 20px - Body text */
            }

            .text-6xl {
                font-size: 1.75rem !important;  /* 28px instead of 60px - Large headings */
            }

            /* ========================================
               CARD LAYOUT OPTIMIZATION - Reduce padding for mobile
               ======================================== */
            .card {
                padding: 8px !important;        /* Smaller card padding */
                margin-bottom: 8px !important;  /* Reduce card spacing */
            }

            .welcome-card {
                padding: 8px !important;       /* Welcome card padding */
                margin-bottom: 22px !important; /* Welcome card spacing */
            }

            /* ========================================
               CHART CONTAINER - Optimize for mobile viewing
               ======================================== */
            .chart-container {
                padding: 8px !important;        /* Reduce chart padding */
                margin-bottom: 12px !important; /* Reduce chart spacing */
                height: 300px !important;       /* Fixed height for mobile */
                margin-left: 0 !important;      /* Remove left margin */
            }

            /* ========================================
               CHARACTER POSITIONING - Move character above chart on mobile
               ======================================== */
            .character-above {
                position: relative !important;  /* Change from absolute to relative */
                top: auto !important;          /* Reset top position */
                left: auto !important;         /* Reset left position */
                right: auto !important;        /* Reset right position */
                margin-bottom: 16px !important; /* Add bottom margin */
                text-align: center !important;  /* Center the character */
                z-index: 10 !important;        /* Ensure it's above other elements */
            }

            .character-behind {
                display: none !important;      /* Hide character behind chart */
            }

            /* ========================================
               SPACING OPTIMIZATION - Reduce margins and padding globally
               ======================================== */
            /* Bottom margins - Reduce vertical spacing */
            .mb-8 { margin-bottom: 12px !important; }  /* Large bottom margin */
            .mb-6 { margin-bottom: 8px !important; }   /* Medium bottom margin */
            .mb-4 { margin-bottom: 6px !important; }   /* Small bottom margin */
            .mb-3 { margin-bottom: 4px !important; }   /* Extra small bottom margin */
            .mb-2 { margin-bottom: 3px !important; }   /* Tiny bottom margin */
            .mb-1 { margin-bottom: 2px !important; }   /* Minimal bottom margin */

            /* Top margins - Reduce vertical spacing */
            .mt-8 { margin-top: 12px !important; }     /* Large top margin */
            .mt-12 { margin-top: 16px !important; }    /* Extra large top margin */

            /* Padding - Reduce internal spacing */
            .p-8 { padding: 8px !important; }          /* Large padding */
            .p-6 { padding: 6px !important; }          /* Medium padding */
            .p-4 { padding: 4px !important; }          /* Small padding */
            .p-3 { padding: 3px !important; }          /* Extra small padding */
            .p-2 { padding: 2px !important; }          /* Tiny padding */

            /* ========================================
               BUTTON OPTIMIZATION - Touch-friendly buttons
               ======================================== */
            .btn-primary {
                padding: 8px 12px !important;  /* Smaller button padding */
                font-size: 0.75rem !important; /* Smaller button text */
                margin-bottom: 6px !important; /* Reduce button spacing */
            }

            /* ========================================
               PROFILE IMAGE - Optimize for mobile
               ======================================== */
            .profile-image {
              margin-top: 20px !important;
                width: 120px !important;        /* Smaller profile image */
                height: 120px !important;       /* Maintain aspect ratio */
            }

            /* ========================================
               CHART RESPONSIVE - Optimize chart size
               ======================================== */
            .chart-wrapper {
                height: 250px !important;      /* Smaller chart height */
            }

            /* ========================================
               CHARACTER BUBBLE - Optimize character speech bubble
               ======================================== */
            .character-bubble {
              margin-top: 20px;
                max-width: 200px !important;   /* Limit bubble width */
                padding: 8px !important;       /* Reduce bubble padding */
                margin-bottom: 8px !important; /* Reduce bubble spacing */
            }

            .character-emoji {
                font-size: 4rem !important;    /* Smaller emoji size */
            }

            /* ========================================
               HORIZONTAL SPACING - Reduce horizontal gaps
               ======================================== */
            .space-x-4 > * + * {
                margin-right: 8px !important;  /* Reduce large horizontal spacing */
            }

            .space-x-2 > * + * {
                margin-right: 4px !important;  /* Reduce small horizontal spacing */
            }
        }

        /* ========================================
           SMALL MOBILE RESPONSIVE STYLES - Small phones (480px and below)
           ======================================== */
        @media (max-width: 480px) {
            
            /* ========================================
               EXTREME SPACING REDUCTION - Minimal padding for small screens
               ======================================== */
            .container {
                padding: 4px !important;         /* Minimal container padding */
            }
            
            .nav-glass {
                padding: 8px 12px !important;   /* Minimal navbar padding */
            }

            /* ========================================
               ULTRA-SMALL TYPOGRAPHY - Even smaller text for tiny screens
               ======================================== */
            .text-5xl {
                font-size: 1.25rem !important;  /* 20px - Very small main headings */
            }

            .text-4xl {
                font-size: 1.125rem !important; /* 18px - Very small sub headings */
            }

            .text-6xl {
                font-size: 1.5rem !important;   /* 24px - Very small large headings */
            }

            /* ========================================
               MINIMAL CARD SPACING - Ultra-compact cards
               ======================================== */
            .card {
                padding: 6px !important;         /* Minimal card padding */
            }

            .welcome-card {
                padding: 8px !important;         /* Minimal welcome card padding */
            }

            /* ========================================
               COMPACT CHART - Smaller chart for tiny screens
               ======================================== */
            .chart-container {
                padding: 0px !important;         /* Minimal chart padding */
                height: 300px !important;        /* Smaller chart height */
            }

            /* ========================================
               TINY BUTTONS - Ultra-small touch targets
               ======================================== */
            .btn-primary {
                padding: 6px 10px !important;   /* Minimal button padding */
                font-size: 0.7rem !important;   /* Very small button text */
            }

            /* ========================================
               MINI PROFILE IMAGE - Very small profile picture
               ======================================== */
            .profile-image {
              margin-top: 30px;
                width: 120px !important;          /* Very small profile image */
                height: 120px !important;         /* Maintain aspect ratio */
            }

            /* ========================================
               COMPACT CHART WRAPPER - Smaller chart container
               ======================================== */
            .chart-wrapper {
                height: 450px !important;        /* Very small chart height */
            }

            /* ========================================
               MINI CHARACTER BUBBLE - Smaller speech bubble
               ======================================== */
            .character-bubble {
              margin-top: 20px;
                max-width: 180px !important;     /* Very small bubble width */
                padding: 0px !important;         /* Minimal bubble padding */
            }

            .character-emoji {

                font-size: 3.5rem !important;    /* Very small emoji */
            }
        }

        /* ========================================
           DESKTOP STYLES - Large screens (769px and above)
           ======================================== */
        @media (min-width: 769px) {
            .desktop-nav {
                display: flex;                  /* Show desktop navigation */
            }

            .mobile-menu-btn {
                display: none;
            }
            
            .mobile-nav-menu {
                display: none !important;
            }
        }


     .chart-container {
       background: rgba(0, 0, 0, 0.8);
       backdrop-filter: blur(20px);
       border: 2px solid transparent;
       border-radius: 20px;
       position: relative;
       overflow: hidden;
       padding-top: 40px; /* Extra space for profile images and degrees below */
     }

     .chart-container::before {
       content: '';
       position: absolute;
       top: 0;
       left: 0;
       right: 0;
       bottom: 0;
       border-radius: 20px;
       padding: 2px;
       background: linear-gradient(45deg, #ff006e, #00f5ff, #8338ec, #3a86ff, #06ffa5);
       background-size: 400% 400%;
       animation: gradientBorder 3s ease infinite;
       -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
       -webkit-mask-composite: exclude;
       mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
       mask-composite: exclude;
     }

     @keyframes gradientBorder {
       0%, 100% { background-position: 0% 50%; }
       50% { background-position: 100% 50%; }
     }

     .welcome-card {
       background: rgba(0, 0, 0, 0.8);
       backdrop-filter: blur(15px);
       border: 2px solid #ff006e;
       border-radius: 20px;
       box-shadow: 0 0 30px rgba(255, 0, 110, 0.3);
       position: relative;
       overflow: hidden;
     }

     .welcome-card::before {
       content: '';
       position: absolute;
       top: -50%;
       left: -50%;
       width: 200%;
       height: 200%;
       background: linear-gradient(45deg, transparent, rgba(255, 0, 110, 0.1), transparent);
       animation: welcomeShine 3s infinite;
     }

     @keyframes welcomeShine {
       0% { transform: rotate(0deg); }
       100% { transform: rotate(360deg); }
     }

     .profile-image {
       border: 4px solid #00ffff;
       box-shadow: 
         0 0 20px rgba(0, 255, 255, 0.5),
         inset 0 0 20px rgba(0, 255, 255, 0.1);
       animation: profilePulse 2s infinite;
       transition: all 0.3s ease;
     }

     .profile-image:hover {
       transform: scale(1.1);
       box-shadow: 
         0 0 30px rgba(0, 255, 255, 0.8),
         inset 0 0 30px rgba(0, 255, 255, 0.2);
     }

     @keyframes profilePulse {
       0%, 100% { 
         box-shadow: 
           0 0 20px rgba(0, 255, 255, 0.5),
           inset 0 0 20px rgba(0, 255, 255, 0.1);
       }
       50% { 
         box-shadow: 
           0 0 30px rgba(0, 255, 255, 0.8),
           inset 0 0 30px rgba(0, 255, 255, 0.2);
       }
     }

    .btn-primary {
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

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(30, 64, 175, 0.4);
        }

        .btn-primary.active {
            background: linear-gradient(45deg, #10b981, #059669);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-primary.active:hover {
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.4);
        }

    .decoration {
      position: absolute;
      pointer-events: none;
      z-index: 1;
    }

     .decoration-icon {
       color: #fff;
       font-size: 2rem;
       animation: galaxyFloat 6s ease-in-out infinite;
       opacity: 0.7;
       text-shadow: 0 0 10px currentColor;
     }
    
     @keyframes galaxyFloat {
       0%, 100% { 
         transform: translateY(0px) rotate(0deg) scale(0.8); 
         opacity: 0.3; 
       }
       25% { 
         transform: translateY(-15px) rotate(90deg) scale(1.1); 
         opacity: 0.8; 
       }
       50% { 
         transform: translateY(-10px) rotate(180deg) scale(0.9); 
         opacity: 0.6; 
       }
       75% { 
         transform: translateY(-20px) rotate(270deg) scale(1.2); 
         opacity: 0.9; 
       }
     }

    .decoration-1 { top: 5%; left: 5%; }
    .decoration-2 { top: 10%; right: 8%; }
    .decoration-3 { bottom: 15%; left: 10%; }
    .decoration-4 { bottom: 8%; right: 5%; }
    .decoration-5 { top: 20%; left: 15%; }
    .decoration-6 { top: 30%; right: 20%; }
    .decoration-7 { bottom: 25%; left: 5%; }
    .decoration-8 { bottom: 35%; right: 15%; }
    .decoration-9 { top: 40%; left: 8%; }
    .decoration-10 { top: 50%; right: 10%; }
    .decoration-11 { bottom: 45%; left: 20%; }
    .decoration-12 { bottom: 55%; right: 25%; }

    .character-behind {
      pointer-events: none;
    }

    .character-bubble {
      animation: float 3s ease-in-out infinite;
      position: relative;
    }

    .character-bubble::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 20px;
      width: 0;
      height: 0;
      border-left: 10px solid transparent;
      border-right: 10px solid transparent;
      border-top: 10px solid rgba(255, 255, 255, 0.9);
    }

    .character-emoji {
      filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }
  </style>
</head>
<body>

   <!-- Galaxy Floating Particles -->
   <div id="particles-container"></div>
   
   <!-- Galaxy Decorative elements -->
   <div class="decoration decoration-1">
     <span class="decoration-icon">⭐</span>
   </div>
   <div class="decoration decoration-2">
     <span class="decoration-icon">🌟</span>
   </div>
   <div class="decoration decoration-3">
     <span class="decoration-icon">✨</span>
   </div>
   <div class="decoration decoration-4">
     <span class="decoration-icon">💫</span>
   </div>
   <div class="decoration decoration-5">
     <span class="decoration-icon">🌠</span>
   </div>
   <div class="decoration decoration-6">
     <span class="decoration-icon">🚀</span>
   </div>
   <div class="decoration decoration-7">
     <span class="decoration-icon">🛸</span>
   </div>
   <div class="decoration decoration-8">
     <span class="decoration-icon">🌌</span>
   </div>
   <div class="decoration decoration-9">
     <span class="decoration-icon">🔮</span>
   </div>
   <div class="decoration decoration-10">
     <span class="decoration-icon">💎</span>
   </div>
   <div class="decoration decoration-11">
     <span class="decoration-icon">⚡</span>
   </div>
   <div class="decoration decoration-12">
     <span class="decoration-icon">🔥</span>
   </div>

  <!-- Navbar -->
  <nav class="nav-glass px-6 py-4 flex justify-between items-center relative">
    <span class="text-4xl font-bold" style="background: linear-gradient(45deg, #1e40af, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
      ⚡ إبداع
    </span>
    
    <!-- Desktop Navigation -->
    <div class="desktop-nav space-x-2 space-x-reverse">
        <?php if ($role === 'student'): ?>
            <a href="<?= url('dashboard') ?>" class="btn-primary active">
              <i class="fas fa-chart-bar"></i>
              الترتيب
            </a>
            <a href="<?= url('questions') ?>" class="btn-primary relative">
              <i class="fas fa-question-circle"></i>
              الأسئلة
              <?php
              // Get unread notifications count
              $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE student_id = ? AND is_read = 0");
              $stmt->execute([$_SESSION['user']['id']]);
              $notification_count = $stmt->fetch()['count'];
              if ($notification_count > 0): ?>
                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center animate-pulse">
                  <?= $notification_count ?>
                </span>
              <?php endif; ?>
            </a>
            <a href="<?= url('profile') ?>" class="btn-primary">
              <i class="fas fa-user"></i>
              حسابي
            </a>
        <?php endif; ?> 

        <?php if ($role === 'admin'): ?>
            <a href="<?= url('admin') ?>" class="btn-primary active">
              <i class="fas fa-users"></i>
              المجموعات
            </a>
            <a href="<?= url('admin.questions') ?>" class="btn-primary">
              <i class="fas fa-question-circle"></i>
              الأسئلة
            </a>
            <a href="<?= url('admin.invitations') ?>" class="btn-primary relative">
              <i class="fas fa-envelope"></i>
              الدعوات
              <?php
              // Get pending invitations count
              $admin_username = $_SESSION['user']['username'] ?? '';
              if (!empty($admin_username)) {
                  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_invitations WHERE invited_username = ? AND status = 'pending'");
                  $stmt->execute([$admin_username]);
                  $invitation_count = $stmt->fetch()['count'];
              } else {
                  $invitation_count = 0;
              }
              if ($invitation_count > 0): ?>
                <span class="absolute -top-2 -right-2 bg-orange-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center animate-pulse">
                  <?= $invitation_count ?>
                </span>
              <?php endif; ?>
            </a>
            <a href="<?= url('profile') ?>" class="btn-primary">
              <i class="fas fa-user"></i>
              حسابي
            </a>
        <?php endif; ?>
    </div>

    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
        <i class="fas fa-bars" id="mobile-menu-icon"></i>
    </button>

    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav-menu" id="mobile-nav-menu">
        <div class="mobile-nav-links">
        
        <!-- ========================================
             STUDENT MOBILE NAVIGATION
             ======================================== -->
        <?php if ($role === 'student'): ?>
            <!-- Dashboard/Ranking Link -->
            <a href="<?= url('dashboard') ?>" class="btn-primary active">
              <i class="fas fa-chart-bar"></i> الترتيب
            </a>
            
            <!-- Questions Link with Notification Badge -->
            <a href="<?= url('questions') ?>" class="btn-primary">
              <i class="fas fa-question-circle"></i> الأسئلة
              <?php if ($notification_count > 0): ?>
                <span style="color: #ef4444; font-weight: bold;">(<?= $notification_count ?>)</span>
              <?php endif; ?>
            </a>
            
            <!-- Profile Link -->
            <a href="<?= url('profile') ?>" class="btn-primary">
              <i class="fas fa-user"></i> حسابي
            </a>
        <?php endif; ?> 

        <!-- ========================================
             ADMIN MOBILE NAVIGATION
             ======================================== -->
        <?php if ($role === 'admin'): ?>
            <!-- Groups Link -->
            <a href="<?= url('admin') ?>" class="btn-primary">
              <i class="fas fa-users"></i> المجموعات
            </a>
            
            <!-- Questions Link -->
            <a href="<?= url('admin.questions') ?>" class="btn-primary">
              <i class="fas fa-question-circle"></i> الأسئلة
            </a>
            
            <!-- Invitations Link with Notification Badge -->
            <a href="<?= url('admin.invitations') ?>" class="btn-primary">
              <i class="fas fa-envelope"></i> الدعوات
              <?php if ($invitation_count > 0): ?>
                <span style="color: #f97316; font-weight: bold;">(<?= $invitation_count ?>)</span>
              <?php endif; ?>
            </a>
            
            <!-- Profile Link -->
            <a href="<?= url('profile') ?>" class="btn-primary">
              <i class="fas fa-user"></i> حسابي
            </a>
        <?php endif; ?>
        </div>
    </div>
  </nav>

  <div class="container mx-auto p-8 relative z-10">
    <!-- صورة الحساب -->
    <?php if (isset($profile_image)): ?>
      <div class="flex justify-center -mt-4">
        <img src="/ebdaa/uploads/<?= htmlspecialchars($profile_image); ?>" 
             alt="Profile Image" 
             class="w-36 h-36 rounded-full profile-image floating">
      </div>
    <?php endif; ?> 

    <!-- الترحيب -->
    <?php if ($_SESSION['user']['role'] === 'student'): ?>
       <div class="welcome-card text-center mt-8 p-8 bounce-in relative z-10">
         <h2 class="text-5xl font-bold mb-4" style="color: #ff006e; text-shadow: 0 0 20px rgba(255, 0, 110, 0.5);">
           مرحباً أيها البطل <span class="text-6xl" style="color: #00ffff; text-shadow: 0 0 20px rgba(0, 255, 255, 0.5);"><?= htmlspecialchars($_SESSION['user']['name']); ?></span>! ⚡
         </h2>
         <p class="text-xl mb-4" style="color: #8338ec;">دعنا نرى من الأبطال في مجموعتك! 🏆</p>
         <div class="flex justify-center space-x-4 space-x-reverse text-3xl">
           <span style="color: #00ffff; filter: drop-shadow(0 0 10px currentColor);">⚡</span>
           <span style="color: #ff006e; filter: drop-shadow(0 0 10px currentColor);">🔥</span>
           <span style="color: #8338ec; filter: drop-shadow(0 0 10px currentColor);">🎮</span>
           <span style="color: #06ffa5; filter: drop-shadow(0 0 10px currentColor);">🏆</span>
           <span style="color: #3a86ff; filter: drop-shadow(0 0 10px currentColor);">🚀</span>
         </div>
       </div>
    <?php endif; ?> 

    <!-- ========================================
         CHARACTER ABOVE CHART (MOBILE ONLY)
         ======================================== -->
    <!-- This character appears above the chart on mobile devices -->
    <!-- Hidden by default, shown by JavaScript when screen width <= 768px -->
    <div class="character-above hidden">
      <!-- Character speech bubble with group message -->
      <div class="character-bubble bg-white bg-opacity-90 rounded-2xl p-4 shadow-lg mb-4 max-w-xs mx-auto">
        <p class="text-sm font-bold text-blue-600 text-center mb-1">المعلم <?= htmlspecialchars($group_emoji) ?></p>
        <?php if (!empty($group_message)): ?>
          <!-- Show custom group message if available -->
          <p class="text-sm text-gray-800 text-center"><?= htmlspecialchars($group_message) ?></p>
        <?php else: ?>
          <!-- Default motivational message -->
          <p class="text-lg font-bold text-gray-800 text-center">أنا أتابع تقدمكم! 🔥</p>
          <p class="text-sm text-gray-600 text-center">استمروا في التميز! ⚡</p>
        <?php endif; ?>
      </div>
      <!-- Character emoji (large size) -->
      <div class="character-emoji text-8xl text-center"><?= htmlspecialchars($group_emoji) ?></div>
    </div>

    <!-- ========================================
         CHART CONTAINER WITH RESPONSIVE CHARACTER
         ======================================== -->
    <div class="mt-12 flex justify-center relative">
      
      <!-- ========================================
           CHARACTER BEHIND CHART (DESKTOP ONLY)
           ======================================== -->
      <!-- This character appears behind the chart on desktop -->
      <!-- Hidden on mobile, shown by JavaScript when screen width > 768px -->
      <div class="character-behind absolute left-0 top-0 z-0">
        <!-- Character speech bubble with group message -->
        <div class="character-bubble bg-white bg-opacity-90 rounded-2xl p-4 shadow-lg mb-4 max-w-xs">
          <p class="text-sm font-bold text-blue-600 text-center mb-1">المعلم <?= htmlspecialchars($group_emoji) ?></p>
          <?php if (!empty($group_message)): ?>
            <!-- Show custom group message if available -->
            <p class="text-sm text-gray-800 text-center"><?= htmlspecialchars($group_message) ?></p>
          <?php else: ?>
            <!-- Default motivational message -->
            <p class="text-lg font-bold text-gray-800 text-center">أنا أتابع تقدمكم! 🔥</p>
            <p class="text-sm text-gray-600 text-center">استمروا في التميز! ⚡</p>
          <?php endif; ?>
        </div>
        <!-- Character emoji (large size) -->
        <div class="character-emoji text-8xl"><?= htmlspecialchars($group_emoji) ?></div>
      </div>
      
      <div class="chart-container p-8 w-full max-w-6xl relative h-[500px] floating z-10 ml-24">
        <canvas id="gpaChart"></canvas>
      </div>
    </div>  
  </div>

 <script>
   /* ========================================
      MOBILE MENU TOGGLE FUNCTION
      ======================================== */
   function toggleMobileMenu() {
     const mobileMenu = document.getElementById('mobile-nav-menu');
     const menuIcon = document.getElementById('mobile-menu-icon');
     
     if (mobileMenu.classList.contains('active')) {
         mobileMenu.classList.remove('active');
         menuIcon.classList.remove('fa-times');
         menuIcon.classList.add('fa-bars');
     } else {
         mobileMenu.classList.add('active');
         menuIcon.classList.remove('fa-bars');
         menuIcon.classList.add('fa-times');
     }
   }

   /* ========================================
      CLOSE MOBILE MENU WHEN CLICKING OUTSIDE
      ======================================== */
   document.addEventListener('click', function(e) {
     const mobileMenu = document.getElementById('mobile-nav-menu');
     const menuBtn = document.querySelector('.mobile-menu-btn');
     
     if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
         mobileMenu.classList.remove('active');
         document.getElementById('mobile-menu-icon').classList.remove('fa-times');
         document.getElementById('mobile-menu-icon').classList.add('fa-bars');
     }
   });

   /* ========================================
      RESPONSIVE CHARACTER POSITIONING
      ======================================== */
   function handleResponsiveCharacter() {
     // Get references to character elements
     const characterAbove = document.querySelector('.character-above');
     const characterBehind = document.querySelector('.character-behind');
     
     // Check if screen width is mobile size (768px or less)
     if (window.innerWidth <= 768) {
       // Mobile: Show character above chart, hide character behind chart
       characterAbove.classList.remove('hidden');
       characterBehind.style.display = 'none';
     } else {
       // Desktop: Hide character above chart, show character behind chart
       characterAbove.classList.add('hidden');
       characterBehind.style.display = 'block';
     }
   }

   /* ========================================
      EVENT LISTENERS - Initialize responsive behavior
      ======================================== */
   // Call function when page loads
   window.addEventListener('load', handleResponsiveCharacter);
   // Call function when window is resized (e.g., rotating device)
   window.addEventListener('resize', handleResponsiveCharacter);

   // Create floating galaxy particles
   function createGalaxyParticles() {
     const container = document.getElementById('particles-container');
     for (let i = 0; i < 80; i++) {
       const particle = document.createElement('div');
       particle.className = 'particle';
       particle.style.position = 'fixed';
       particle.style.width = Math.random() * 4 + 1 + 'px';
       particle.style.height = particle.style.width;
       particle.style.background = `hsl(${Math.random() * 60 + 200}, 70%, 70%)`;
       particle.style.borderRadius = '50%';
       particle.style.left = Math.random() * 100 + '%';
       particle.style.top = Math.random() * 100 + '%';
       particle.style.pointerEvents = 'none';
       particle.style.zIndex = '1';
       particle.style.boxShadow = `0 0 ${Math.random() * 10 + 5}px currentColor`;
       particle.style.animation = `galaxyParticle ${Math.random() * 10 + 10}s linear infinite`;
       particle.style.animationDelay = Math.random() * 10 + 's';
       
       container.appendChild(particle);
     }
   }

   // Add galaxy particle animation
   const style = document.createElement('style');
   style.textContent = `
     @keyframes galaxyParticle {
       0% {
         transform: translateY(100vh) translateX(0px) rotate(0deg);
         opacity: 0;
       }
       10% {
         opacity: 1;
       }
       90% {
         opacity: 1;
       }
       100% {
         transform: translateY(-100px) translateX(${Math.random() * 200 - 100}px) rotate(360deg);
         opacity: 0;
       }
     }
   `;
   document.head.appendChild(style);

   // Initialize particles
   createGalaxyParticles();

   // Performance monitoring
   const startTime = performance.now();
  
  const ctx = document.getElementById('gpaChart').getContext('2d');

  const labels = <?= json_encode($labels) ?>; // أسماء الطلاب
  const data = <?= json_encode($data) ?>;     // درجات الطلاب

  // 🥇🥈🥉 الميداليات والرموز التعبيرية للأولاد
  const medalEmojis = ["🥇", "🥈", "🥉"];
  const topTitles = ["البطل الذهبي", "البطل الفضي", "البطل البرونزي"];

  // ألوان متدرجة لكل الأعمدة
  function createGradient(color1, color2) {
    const g = ctx.createLinearGradient(0, 0, 0, 400);
    g.addColorStop(0, color1);
    g.addColorStop(1, color2);
    return g;
  }

   // Gaming Colors for Epic Look
   const gamingColors = [
     { start: '#ff006e', end: '#8338ec' }, // Pink to Purple
     { start: '#00f5ff', end: '#0099ff' }, // Cyan to Blue  
     { start: '#06ffa5', end: '#00cc88' }, // Green to Teal
     { start: '#ffbe0b', end: '#fb8500' }, // Yellow to Orange
     { start: '#8338ec', end: '#3a86ff' }, // Purple to Blue
     { start: '#ff006e', end: '#ff4081' }, // Pink variations
     { start: '#00ffff', end: '#40e0d0' }, // Cyan variations
     { start: '#32cd32', end: '#00ff7f' }, // Green variations
     { start: '#ff1493', end: '#ff69b4' }, // Pink variations
     { start: '#1e90ff', end: '#00bfff' }  // Blue variations
   ];

   // Create epic gradients
   function createEpicGradient(colorPair, index) {
     const gradient = ctx.createLinearGradient(0, 0, 0, 400);
     gradient.addColorStop(0, colorPair.start);
     gradient.addColorStop(0.5, colorPair.end);
     gradient.addColorStop(1, colorPair.start + '80'); // Add transparency
     return gradient;
   }

  const barColors = data.map((_, i) => {
    const colorPair = gamingColors[i % gamingColors.length];
    return createEpicGradient(colorPair, i);
  });

  // Create images array for profile pictures
  const images = <?= json_encode($images) ?>;
  
  // Preload images
  const loadedImages = [];
  const imagePromises = images && images.length > 0 ? images.map((imageName, index) => {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        loadedImages[index] = img;
        resolve(img);
      };
      img.onerror = () => {
        // If image fails to load, create a default avatar
        const canvas = document.createElement('canvas');
        canvas.width = 60;
        canvas.height = 60;
        const ctx2 = canvas.getContext('2d');
        
        // Create a gradient background
        const gradient = ctx2.createLinearGradient(0, 0, 60, 60);
        gradient.addColorStop(0, '#3b82f6');
        gradient.addColorStop(1, '#1e40af');
        ctx2.fillStyle = gradient;
        ctx2.fillRect(0, 0, 60, 60);
        
        // Add initials or emoji
        ctx2.fillStyle = 'white';
        ctx2.font = 'bold 24px Arial';
        ctx2.textAlign = 'center';
        ctx2.textBaseline = 'middle';
        ctx2.fillText('👤', 30, 30);
        
        loadedImages[index] = canvas;
        resolve(canvas);
      };
      img.src = `/ebdaa/uploads/${imageName}`;
    });
  }) : [];

  // Animation frame for fire effect
  let animationFrame = 0;
  
   // Enhanced Profile Images Plugin with Gaming Effects
   const profileImagePlugin = {
     id: 'profileImages',
     afterDatasetsDraw: (chart) => {
       const { ctx, data, chartArea } = chart;
       const meta = chart.getDatasetMeta(0);
       
       meta.data.forEach((bar, index) => {
         if (loadedImages[index]) {
           const x = bar.x;
           const y = bar.y ; // Position above the bar
           const imageSize = window.innerWidth <= 900 ? 30 : 70; // Smaller on mobile, bigger on desktop
           
           // Gaming glow effect
           ctx.save();
           ctx.shadowColor = gamingColors[index % gamingColors.length].start;
           ctx.shadowBlur = 20;
           
           // Draw neon border
           ctx.beginPath();
           ctx.arc(x, y, imageSize/2 + 6, 0, 2 * Math.PI);
           ctx.strokeStyle = gamingColors[index % gamingColors.length].start;
           ctx.lineWidth = 4;
           ctx.stroke();
           
           // Draw inner glow
           ctx.beginPath();
           ctx.arc(x, y, imageSize/2 + 2, 0, 2 * Math.PI);
           ctx.strokeStyle = '#ffffff';
           ctx.lineWidth = 2;
           ctx.stroke();
           
           // Clip and draw image
           ctx.beginPath();
           ctx.arc(x, y, imageSize/2, 0, 2 * Math.PI);
           ctx.clip();
           
           const img = loadedImages[index];
           const imgX = x - imageSize/2;
           const imgY = y - imageSize/2;
           ctx.drawImage(img, imgX, imgY, imageSize, imageSize);
           
           ctx.restore();
         }
       });
     }
   };

  // Wait for all images to load before creating chart
  const loadImages = imagePromises.length > 0 ? Promise.all(imagePromises) : Promise.resolve();
  
  loadImages.then(() => {
    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
         labels: labels.map((name, i) => {
           const medals = ['🥇', '🥈', '🥉'];
           if (i < 3) {
             return medals[i] + ' ' + name;
           }
           return '⚡ ' + name;
         }),
        datasets: [{
          label: 'درجات الأبطال',
          data: data,
          backgroundColor: barColors,
          borderRadius: 20,
          borderWidth: 2,
          borderColor: '#ffffff',
          hoverBorderWidth: 5,
          hoverBorderColor: '#FFD700'
        }]
      },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 2500,
        easing: 'easeOutBounce',
        delay: (context) => {
          return context.dataIndex * 400;
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      },
         scales: {
           y: { 
             beginAtZero: true,
             grid: {
               color: 'rgba(0, 255, 255, 0.2)',
               lineWidth: 2,
               drawBorder: true,
               borderColor: 'rgba(0, 255, 255, 0.5)'
             },
             ticks: {
               color: '#00ffff',
               font: { size: window.innerWidth <= 768 ? 12 : 16, weight: 'bold' },
               callback: function(value) {
                 return value ;
               },
               padding: 10
             }
           },
           x: {
             grid: { 
               display: false 
             },
             ticks: {
               color: '#ffffff',
               font: function(context) {
                 const baseSize = window.innerWidth <= 768 ? 10 : 14;
                 const largeSize = window.innerWidth <= 768 ? 12 : 18;
                 return { 
                   size: context.index < 3 ? largeSize : baseSize, 
                   weight: 'bold'
                 };
               },
               maxRotation: 45,
               minRotation: 0,
               padding: 0
             }
           }
         },
      plugins: {
         title: {
           display: true,
           text: '⚡ سباق الأبطال الملحمي 🏆',
           font: { 
             size: window.innerWidth <= 768 ? 16 : 32, 
             weight: 'bold', 
             family: 'Cairo' 
           },
           color: '#00ffff',
           padding: window.innerWidth <= 768 ? 15 : 30
         },
        legend: { 
          display: false 
        },
        datalabels: {
          display: false  // Completely disable datalabels
        },  
         tooltip: {
           backgroundColor: 'rgba(0, 0, 0, 0.9)',
           titleColor: '#00ffff',
           bodyColor: '#ffffff',
           borderColor: '#ff006e',
           borderWidth: 3,
           cornerRadius: 15,
           titleFont: { size: window.innerWidth <= 768 ? 14 : 18, weight: 'bold' },
           bodyFont: { size: window.innerWidth <= 768 ? 12 : 16, weight: 'bold' },
           callbacks: {
             title: function(context) {
               const index = context[0].dataIndex;
               const titles = [' البطل الذهبي', ' البطل الفضي', ' البطل البرونزي'];
               if (index < 3) {
                 return titles[index] + context[0].label;
               }
               return  context[0].label;
             },
             label: function(context) {
               return '💪 النقاط: ' + context.parsed.y ;
             }
           }
         },
    

        

      }
    },
    plugins: [ChartDataLabels, profileImagePlugin]
  });
  
  // Performance optimization
  chart.canvas.style.willChange = 'transform';
  chart.canvas.style.transform = 'translateZ(0)';
  
  // Performance monitoring
  const endTime = performance.now();
  console.log(`Chart rendered in ${(endTime - startTime).toFixed(2)}ms`);
  
  });
</script>

<!-- Load optimized JavaScript -->
<script src="assets/js/simple-optimized.js"></script>

<!-- Service Worker Registration -->
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
      navigator.serviceWorker.register('sw.js')
        .then(function(registration) {
          console.log('SW registered: ', registration);
        })
        .catch(function(registrationError) {
          console.log('SW registration failed: ', registrationError);
        });
      });
  }
</script>

</body>
</html>


