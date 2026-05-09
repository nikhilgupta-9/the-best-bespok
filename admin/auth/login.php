<?php
// ========== BACKEND LOGIC ==========
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require '../db-conn.php'; // Adjust path if needed

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../index.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please refresh the page.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Prepare SQL statement
        $stmt = $conn->prepare("SELECT id, username, password FROM admin_user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $stmt->bind_result($id, $user, $hashed_password);
        
        if ($stmt->fetch()) {
            if (password_verify($password, $hashed_password)) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $id;
                $_SESSION['admin_user'] = $user;
                header("Location: ../index.php");
                exit();
            } else {
                usleep(rand(200000, 500000));
                $error = "Incorrect username or password";
            }
        } else {
            usleep(rand(200000, 500000));
            $error = "Incorrect username or password";
        }
        $stmt->close();
    }
}

// Generate fresh CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suite Tailor | Admin Access</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font for a lighter, elegant feel -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;  /* Light, airy background */
            background-image: radial-gradient(circle at 10% 20%, rgba(230, 240, 250, 0.5) 0%, rgba(245, 248, 250, 0.9) 90%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            position: relative;
        }

        /* Subtle fabric-like texture (tailor theme) */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" opacity="0.03"><path fill="none" stroke="%23333" stroke-width="0.5" d="M30 10 L50 30 M70 5 L90 25 M110 15 L130 35 M150 8 L170 28 M10 50 L30 70 M50 45 L70 65 M90 55 L110 75 M130 48 L150 68 M170 58 L190 78 M20 90 L40 110 M60 85 L80 105 M100 95 L120 115 M140 88 L160 108 M180 98 L200 118 M30 130 L50 150 M70 125 L90 145 M110 135 L130 155 M150 128 L170 148 M10 170 L30 190 M50 165 L70 185 M90 175 L110 195 M130 168 L150 188 M170 178 L190 198"/><path d="M40 40 L60 60 M80 30 L100 50 M120 40 L140 60 M160 30 L180 50 M30 80 L50 100 M70 70 L90 90 M110 80 L130 100 M150 70 L170 90 M40 120 L60 140 M80 110 L100 130 M120 120 L140 140 M160 110 L180 130 M30 160 L50 180 M70 150 L90 170 M110 160 L130 180 M150 150 L170 170" stroke="%23444" stroke-width="0.3" fill="none"/></svg>');
            background-repeat: repeat;
            pointer-events: none;
        }

        /* Main card – light, glossy, modern */
        .login-wrapper {
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 2;
        }

        .tailor-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(0px);
            border-radius: 32px;
            padding: 2.2rem 2rem 2.5rem;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.25s ease;
        }

        .brand-area {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .brand-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(145deg, #fff, #f8f9fc);
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255,255,255,0.8);
            border: 1px solid #eef2f6;
        }

        .brand-icon i {
            font-size: 2.3rem;
            color: #2c3e66;
            background: linear-gradient(135deg, #2c3e66, #1a2a44);
            -webkit-background-clip: text;
            background-clip: text;
            color: #2c3e66;
        }

        .brand-area h2 {
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: -0.3px;
            color: #1e2a3e;
            margin-bottom: 0.25rem;
        }

        .brand-area .sub {
            color: #5b6e8c;
            font-size: 0.9rem;
            font-weight: 400;
            border-top: 1px solid #e2e8f0;
            display: inline-block;
            padding-top: 0.5rem;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .input-group-custom {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .input-group-custom:focus-within {
            border-color: #8ba0bc;
            box-shadow: 0 0 0 3px rgba(44, 62, 102, 0.08);
        }

        .input-icon {
            padding: 0 0 0 1rem;
            color: #8196b0;
            font-size: 1rem;
        }

        .form-control-modern {
            border: none;
            background: transparent;
            padding: 0.85rem 1rem 0.85rem 0.75rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: #1e293b;
            width: 100%;
            outline: none;
        }

        .form-control-modern::placeholder {
            color: #b9c3d4;
            font-weight: 400;
            font-size: 0.85rem;
        }

        .password-toggle-icon {
            padding-right: 1rem;
            cursor: pointer;
            color: #8d9bb0;
            transition: color 0.2s;
        }

        .password-toggle-icon:hover {
            color: #2c3e66;
        }

        /* custom button refined */
        .btn-tailor {
            background: #1e2f41;
            border: none;
            padding: 0.85rem 1rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            color: white;
            width: 100%;
            transition: all 0.25s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-tailor:hover {
            background: #2c3e5c;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -8px rgba(30, 47, 65, 0.2);
        }

        .forgot-row {
            text-align: right;
            margin-top: 0.35rem;
            margin-bottom: 0.2rem;
        }

        .forgot-row a {
            font-size: 0.75rem;
            color: #6c7e9e;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-row a:hover {
            color: #1e2f41;
            text-decoration: underline;
        }

        .alert-soft {
            background: #fff5f0;
            border-left: 4px solid #e07c4c;
            border-radius: 18px;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            color: #a1451a;
            margin-top: 1rem;
            margin-bottom: 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-soft i {
            font-size: 1rem;
        }

        /* divider */
        .divider-text {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.8rem 0 0.5rem;
            color: #99a6bf;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .divider-text::before,
        .divider-text::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e9edf2;
        }
        .divider-text::before {
            margin-right: 0.8rem;
        }
        .divider-text::after {
            margin-left: 0.8rem;
        }

        .tailor-note {
            background: #fafcff;
            border-radius: 24px;
            text-align: center;
            padding: 0.7rem 1rem;
            margin-top: 1.5rem;
            font-size: 0.7rem;
            color: #6c7e9e;
            border: 1px solid #eef2f8;
        }

        .tailor-note i {
            margin-right: 4px;
        }

        /* responsive */
        @media (max-width: 480px) {
            .tailor-card {
                padding: 1.8rem 1.5rem;
            }
            .brand-area h2 {
                font-size: 1.5rem;
            }
        }

        /* subtle floating animation for card */
        .animate-float {
            animation: floatSoft 2s ease-out;
        }
        @keyframes floatSoft {
            0% { opacity: 0; transform: translateY(18px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>



<div class="login-wrapper animate__animated animate__fadeInUp animate-float">
    <div class="tailor-card">
        <div class="brand-area">
            <div class="brand-icon">
                <i class="fas fa-scroll"></i>
            </div>
            <h2>Suite Tailor</h2>
            <div class="sub">Admin Console • Secure Backstage</div>
        </div>

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="mb-4">
                <label class="form-label"><i class="far fa-user-circle me-1"></i> ADMIN USERNAME</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-envelope fa-fw"></i></span>
                    <input type="text" class="form-control-modern" id="username" name="username" required 
                           placeholder="e.g., master.tailor" autocomplete="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fas fa-key me-1"></i> PASSWORD</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-lock fa-fw"></i></span>
                    <input type="password" class="form-control-modern" id="password" name="password" required 
                           placeholder="••••••••" autocomplete="current-password">
                    <span class="password-toggle-icon" onclick="togglePassword()">
                        <i class="far fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
                <div class="forgot-row">
                    <a href="#recovery-link"><i class="fas fa-arrow-turn-right"></i> Reset credentials?</a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert-soft animate__animated animate__shakeX">
                    <i class="fas fa-circle-exclamation"></i> 
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-tailor mt-2">
                <i class="fas fa-arrow-right-to-bracket"></i> Sign in to Dashboard
            </button>

            <div class="divider-text">
                <span>secure • tailored access</span>
            </div>
            <div class="tailor-note">
                <i class="fas fa-shield-alt"></i> Secure login with CSRF protection | For Suite Tailor e-commerce
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Simple client-side eye candy: smooth autofocus + subtle fade for error
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('username');
        if (usernameInput) usernameInput.focus();

        // Optional: add gentle floating effect
        const card = document.querySelector('.tailor-card');
        if (card) {
            card.style.opacity = '0';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease';
                card.style.opacity = '1';
            }, 50);
        }
    });
</script>
</body>
</html>