<?php
// Mulakan sesi untuk mendapatkan mesej ralat jika ada
session_start();

$error_message = '';
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Kosongkan selepas dipaparkan
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk - Resonance Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background Particles */
        .bg-animation {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 20s;
        }

        .particle:nth-child(2) {
            width: 60px;
            height: 60px;
            left: 70%;
            animation-delay: 2s;
            animation-duration: 18s;
        }

        .particle:nth-child(3) {
            width: 100px;
            height: 100px;
            left: 40%;
            animation-delay: 4s;
            animation-duration: 22s;
        }

        .particle:nth-child(4) {
            width: 50px;
            height: 50px;
            left: 80%;
            animation-delay: 1s;
            animation-duration: 16s;
        }

        .particle:nth-child(5) {
            width: 70px;
            height: 70px;
            left: 20%;
            animation-delay: 3s;
            animation-duration: 19s;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Login Box */
        .login-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #0f172a, #3b82f6, #0f172a);
            background-size: 200% 100%;
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 2em;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: #64748b;
            font-size: 0.95em;
            font-weight: 400;
        }

        /* Error Message */
        .error-message {
            color: #dc2626;
            background: #fee2e2;
            border: 1px solid #fecaca;
            padding: 12px;
            margin-bottom: 25px;
            border-radius: 12px;
            text-align: center;
            font-size: 0.9em;
            font-weight: 500;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Form */
        form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* Input Group */
        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9em;
            color: #334155;
            font-weight: 600;
            transition: color 0.3s;
        }

        .input-wrapper {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 14px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .input-group input:focus {
            outline: none;
            border-color: #0f172a;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.1);
        }

        .input-group input:focus + .input-icon {
            color: #0f172a;
            transform: scale(1.1);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1em;
            transition: all 0.3s;
            pointer-events: none;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1em;
        }

        .toggle-password:hover {
            color: #0f172a;
        }

        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.05em;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            margin-top: 10px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-submit:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit span {
            position: relative;
            z-index: 1;
        }

        /* Loading Animation */
        .btn-submit.loading {
            pointer-events: none;
        }

        .btn-submit.loading span {
            opacity: 0;
        }

        .btn-submit.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-box {
                padding: 40px 30px;
            }

            .login-header h1 {
                font-size: 1.6em;
            }
        }
    </style>
</head>
<body>
    <!-- Background Animation -->
    <div class="bg-animation">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Resonance Log</h1>
                <p>Sistem Kehadiran Blok Gemilang</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form action="authenticate.php" method="POST" id="loginForm">
                <div class="input-group">
                    <label for="no_kp">No. KP</label>
                    <div class="input-wrapper">
                        <input type="text" id="no_kp" name="no_kp" required>
                        <i class="fas fa-id-card input-icon"></i>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="password">Kata Laluan (4 digit akhir)</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="kata_laluan" required maxlength="4">
                        <i class="fas fa-lock input-icon"></i>
                        <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <span>Log Masuk</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Form Submit Animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.classList.add('loading');
        });

        // Input Animation Effects
        const inputs = document.querySelectorAll('.input-group input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.querySelector('label').style.color = '#0f172a';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.parentElement.querySelector('label').style.color = '#334155';
                }
            });
        });
    </script>
</body>
</html>