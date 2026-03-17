<?php
session_start(); // Start session at the top

// Connect to database
require_once '../db_connect.php';
// $conn is available from db_connect.php

// Check if user is logged in (we expect 'nama_penuh' to be set in session)
if (isset($_SESSION['nama_penuh'])) {
    $nama_pengguna = $_SESSION['nama_penuh'];
} else {
    // Not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Portal Penghuni - Resonance Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --card-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            --primary-color: #6366f1;
            --secondary-color: #64748b;
            --text-main: #1e293b;
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: #f8fafc; color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width); background: #0f172a; display: flex; flex-direction: column;
            height: 100vh; position: fixed; z-index: 10001; transition: all 0.3s ease;
        }
        .sidebar-header { padding: 2rem; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-header h2 { font-size: 1.5rem; font-weight: 800; }
        .sidebar-nav { list-style: none; padding: 1rem; flex-grow: 1; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94a3b8;
            text-decoration: none; border-radius: 12px; font-weight: 500; transition: all 0.2s ease;
        }
        .sidebar-nav li.active a, .sidebar-nav a:hover { background: rgba(255, 255, 255, 0.05); color: white; }

        /* Main Content & Header */
        .main-content { flex-grow: 1; margin-left: var(--sidebar-width); padding: 2rem; width: calc(100% - var(--sidebar-width)); }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.25rem 2rem; margin: -2rem -2rem 2rem -2rem;
            background: var(--glass-bg); backdrop-filter: blur(15px);
            position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #e2e8f0;
        }
        .header h1 { font-size: 1.5rem; font-weight: 700; color: #0f172a; }
        .header-right { display: flex; align-items: center; gap: 1rem; }
        
        .user-profile { text-align: right; }
        .user-name { display: block; font-weight: 700; font-size: 0.9rem; color: #0f172a; }
        .user-role { font-size: 0.75rem; color: #64748b; font-weight: 500; }
        
        .logout-btn { background: #fee2e2; color: #ef4444; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }

        /* Welcome Card */
        .welcome-card { background: var(--primary-gradient); padding: 3rem; border-radius: 24px; color: white; margin-bottom: 2rem; box-shadow: var(--card-shadow); position: relative; overflow: hidden; }
        .welcome-card h2 { font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem; }
        .welcome-card p { opacity: 0.9; font-size: 1.1rem; }
        .welcome-card::after { content: '\f21b'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; right: -20px; bottom: -20px; font-size: 15rem; opacity: 0.1; transform: rotate(-15deg); }

        /* Summary Grid */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .summary-card { background: white; padding: 2rem; border-radius: 24px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: all 0.3s ease; display: flex; flex-direction: column; gap: 1.25rem; }
        .summary-card:hover { transform: translateY(-5px); box-shadow: var(--card-shadow); border-color: #6366f1; }
        
        .icon-box { width: 56px; height: 56px; border-radius: 16px; background: #f5f3ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .summary-card h3 { font-size: 1.25rem; font-weight: 700; color: #0f172a; }
        .summary-card p { color: #64748b; line-height: 1.6; font-size: 0.95rem; }
        
        .btn-modern { padding: 12px 20px; border-radius: 12px; background: #f8fafc; color: #6366f1; text-decoration: none; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; margin-top: auto; border: 1px solid #e2e8f0; }
        .btn-modern:hover { background: #6366f1; color: white; border-color: #6366f1; }

        /* Mobile */
        .menu-toggle { display: none; position: fixed; top: 1.25rem; left: 1.25rem; z-index: 10002; background: white; border: 1px solid #e2e8f0; padding: 10px; border-radius: 10px; cursor: pointer; }
        .popup-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); display: none; z-index: 999; }
        
        @media (max-width: 1024px) {
            .sidebar { left: -280px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; width: 100%; }
            .menu-toggle { display: block; }
            .header { padding-left: 5rem; }
        }
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; gap: 1rem; }
            .header-right { justify-content: space-between; }
            .welcome-card { padding: 2rem; }
            .welcome-card h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-cube fa-lg"></i>
            <h2>Resonance</h2>
        </div>
        <ul class="sidebar-nav">
            <li class="active"><a href="utama.php"><i class="fas fa-home"></i> <span>Utama</span></a></li>
            <li><a href="daftar_fingerprint.php"><i class="fas fa-fingerprint"></i> <span>Cap Jari</span></a></li>
            <li><a href="kemaskini_maklumat.php"><i class="fas fa-user-edit"></i> <span>Kemaskini</span></a></li>
            <li><a href="laporan_keberadaan.php"><i class="fas fa-chart-pie"></i> <span>Laporan</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1>Portal Penghuni</h1>
            <div class="header-right">
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($nama_pengguna); ?></span>
                    <span class="user-role">Penghuni</span>
                </div>
                <a href="../logout.php" style="text-decoration: none;">
                    <button class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> <span>Keluar</span>
                    </button>
                </a>
            </div>
        </header>

        <div class="card welcome-card">
            <h2>Selamat Datang, <?php echo htmlspecialchars($nama_pengguna); ?>!</h2>
            <p>Uruskan pendaftaran biometrik dan pantau rekod kehadiran anda dengan mudah.</p>
        </div>

        <div class="summary-grid">
            <div class="card summary-card">
                <div class="icon-box">
                    <i class="fas fa-address-card"></i>
                </div>
                <h3>Pendaftaran Biometrik</h3>
                <p>Mendaftar atau kemaskini data cap jari anda untuk akses asrama yang pantas.</p>
                <a href="daftar_fingerprint.php" class="btn-modern">Pergi ke Pendaftaran <i class="fas fa-arrow-right" style="margin-left: 8px;"></i></a>
            </div>
            
            <div class="card summary-card">
                <div class="icon-box">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Imbas Kehadiran</h3>
                <p>Rekod kehadiran harian anda ke asrama menggunakan sistem biometrik.</p>
                <a href="imbas_kehadiran.php" class="btn-modern">Mula Imbasan <i class="fas fa-arrow-right" style="margin-left: 8px;"></i></a>
            </div>

            <div class="card summary-card">
                <div class="icon-box">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>Laporan Kehadiran</h3>
                <p>Semak sejarah kehadiran anda dan pastikan status anda sentiasa dikemaskini.</p>
                <a href="laporan_keberadaan.php" class="btn-modern">Lihat Rekod <i class="fas fa-arrow-right" style="margin-left: 8px;"></i></a>
            </div>
        </div>
    </main>

    <div class="popup-overlay" id="overlay" onclick="toggleSidebar()"></div>

    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
    }
    </script>
</body>
</html>
