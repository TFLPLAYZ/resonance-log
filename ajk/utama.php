<?php
session_start();

require_once '../db_connect.php';
// $conn is available from db_connect.php

// Query counts by status_keberadaan for today
$sql = "
    SELECT status_keberadaan, COUNT(*) as total
    FROM log_keberadaan
    WHERE DATE(masa_imbasan) = CURDATE()
    GROUP BY status_keberadaan
";

$result = $conn->query($sql);

// Initialize counts to zero
$counts = [
    'Hadir' => 0,
    'Tidak Hadir' => 0,
    'Lewat' => 0,
    'Kecemasan' => 0 // if you want to track this too
];

// Fill counts from query result
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status = $row['status_keberadaan'];
        $counts[$status] = (int)$row['total'];
    }
}

$conn->close();

if (!isset($_SESSION['no_kp'])) {
    header('Location: ../login.html');
    exit();
}

$nama_penuh = $_SESSION['nama_penuh'] ?? 'Nama Tidak Dikenal';
$jawatan_user = $_SESSION['jawatan'] ?? 'Tidak Ditetapkan';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Utama AJK - Resonance Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.2);
            --card-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            --primary-color: #6366f1;
            --secondary-color: #64748b;
            --text-main: #1e293b;
            --sidebar-width: 280px;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: #f8fafc;
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Premium Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: #0f172a;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: fixed;
            height: 100vh;
            z-index: 10001;
        }

        .sidebar-header {
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 1rem;
            flex-grow: 1;
        }

        .sidebar-nav li { margin-bottom: 0.5rem; }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar-nav li.active a,
        .sidebar-nav a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .sidebar-nav a i {
            width: 24px;
            font-size: 1.1rem;
            text-align: center;
        }

        /* Main Content & Sticky Header */
        .main-content {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: all 0.3s ease;
            width: calc(100% - var(--sidebar-width));
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid #e2e8f0;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .battery-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: 1px solid #f1f5f9;
        }

        /* User Profile & Layout */
        .user-profile {
            background: white;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .user-name { font-weight: 600; font-size: 0.9rem; color: #1e293b; }
        .user-role { font-size: 0.75rem; color: #64748b; }

        .logout-btn {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .logout-btn:hover { background: #fecaca; transform: translateY(-1px); }

        /* Stats Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
        }

        .stat-card:hover { transform: translateY(-5px); }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
        .stat-icon.green { background: #ecfdf5; color: #10b981; }
        .stat-icon.red { background: #fef2f2; color: #ef4444; }
        .stat-icon.yellow { background: #fffbeb; color: #f59e0b; }

        .stat-info h3 { font-size: 0.85rem; color: #64748b; margin-bottom: 2px; }
        .stat-info p { font-size: 1.5rem; font-weight: 700; color: #0f172a; }

        /* Chart Card */
        .card-graph {
            background: white;
            padding: 2rem;
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #f1f5f9;
        }

        .card-header { margin-bottom: 1.5rem; }
        .card-header h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; }

        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        /* Mobile Adjustments */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 1.25rem;
            left: 1.25rem;
            z-index: 10002;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            cursor: pointer;
        }

        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            display: none;
            z-index: 9999;
        }

        @media (max-width: 1024px) {
            .sidebar { left: -280px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; width: 100%; }
            .menu-toggle { display: block; }
            .header { padding-left: 5rem; }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-cube fa-lg" style="color: var(--primary-color);"></i>
            <h2>Resonance Log</h2>
        </div>
        <ul class="sidebar-nav">
            <li class="active"><a href="utama.php"><i class="fas fa-tachometer-alt"></i> <span>Utama</span></a></li>
            <li><a href="keberadaan.php"><i class="fas fa-chart-bar"></i> <span>Keberadaan</span></a></li>
            <li><a href="daftar_pelajar.php"><i class="fas fa-user-plus"></i> <span>Daftar Penghuni</span></a></li>
            <li><a href="senarai_pelajar.php"><i class="fas fa-users"></i> <span>Senarai Penghuni</span></a></li>
            <li><a href="daftar_fingerprint.php"><i class="fas fa-fingerprint"></i> <span>Daftar Fingerprint</span></a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div>
                <h1>Selamat Datang, AJK</h1>
                <p style="color: var(--secondary-color); font-size: 0.9rem;">Status ringkas kehadiran hari ini.</p>
            </div>
            
            <div class="header-right">
                <div class="battery-status" title="Status Bateri Peranti">
                    <i id="batteryIcon" class="fas fa-battery-full"></i>
                    <span id="batteryLevel">100%</span>
                </div>

                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($nama_penuh); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($jawatan_user); ?></span>
                </div>
                
                <a href="../logout.php" style="text-decoration: none;">
                    <button class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> <span>Log Keluar</span>
                    </button>
                </a>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3>Jumlah Penghuni</h3>
                    <p>89</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Hadir Hari Ini</h3>
                    <p><?php echo $counts['Hadir']; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-info">
                    <h3>Tidak Hadir</h3>
                    <p><?php echo $counts['Tidak Hadir']; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Lewat</h3>
                    <p><?php echo $counts['Lewat']; ?></p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="card-graph">
            <div class="card-header">
                <h2>Analisis Keberadaan Harian</h2>
            </div>
            <div class="chart-container">
                <canvas id="dailyAttendanceChart"></canvas>
            </div>
        </div>

    </main>

    <!-- Overlay for Mobile Sidebar -->
    <div class="popup-overlay" id="overlay" onclick="toggleSidebar()"></div>

    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
    }

    // --- Chart Logic ---
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('dailyAttendanceChart').getContext('2d');
        const doughnutData = [<?php echo $counts['Hadir']; ?>, <?php echo $counts['Tidak Hadir']; ?>, <?php echo $counts['Lewat']; ?>];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Tidak Hadir', 'Lewat'],
                datasets: [{
                    data: doughnutData,
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    hoverOffset: 15,
                    borderWidth: 0,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true, font: { family: "'Inter', sans-serif", size: 13 } }
                    }
                },
                animation: { duration: 2000, easing: 'easeOutQuart' }
            }
        });
    });
    </script>
</body>
</html>
