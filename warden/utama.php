<?php
session_start();
if (!isset($_SESSION['no_kp']) || $_SESSION['jawatan'] !== 'Warden') {
    header("Location: ../index.php?error=Sila log masuk dahulu");
    exit();
}

require_once '../db_connect.php'; // sambung ke db (pastikan anda ada fail ni dan betul detailnya)

$nama_penuh = $_SESSION['nama_penuh'] ?? 'Nama Tidak Dikenal';
$jawatan = $_SESSION['jawatan'] ?? 'Tidak Ditetapkan';

// Dapatkan minggu ini (Isnin sampai Jumaat) - contoh: tarikh sekarang to start of week
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('friday this week'));

// Jumlah pelajar aktif (jawatan = Pelajar, status = Aktif)
$query_total_students = "SELECT COUNT(*) AS total_students FROM pengguna WHERE jawatan = 'Pelajar' AND status = 'Aktif'";
$result = $conn->query($query_total_students);
$total_students = ($result && $row = $result->fetch_assoc()) ? $row['total_students'] : 0;

// Jumlah Tidak Hadir minggu ini
$query_absent = "
SELECT COUNT(DISTINCT no_kp_pengguna) AS total_absent
FROM log_keberadaan
WHERE status_keberadaan = 'Tidak Hadir' 
AND DATE(masa_imbasan) BETWEEN ? AND ?
";
$stmt = $conn->prepare($query_absent);
$stmt->bind_param("ss", $startOfWeek, $endOfWeek);
$stmt->execute();
$result = $stmt->get_result();
$total_absent = ($result && $row = $result->fetch_assoc()) ? $row['total_absent'] : 0;
$stmt->close();

// Jumlah Hadir + Lewat + Kecemasan (jumlah semasa)
$query_present = "
SELECT COUNT(DISTINCT no_kp_pengguna) AS total_present
FROM log_keberadaan
WHERE status_keberadaan IN ('Hadir', 'Lewat', 'Kecemasan')
AND DATE(masa_imbasan) BETWEEN ? AND ?
";
$stmt = $conn->prepare($query_present);
$stmt->bind_param("ss", $startOfWeek, $endOfWeek);
$stmt->execute();
$result = $stmt->get_result();
$total_present = ($result && $row = $result->fetch_assoc()) ? $row['total_present'] : 0;
$stmt->close();

// Dapatkan data mingguan untuk graf, contoh per hari (Isnin-Jumaat)
// Data: jumlah pelajar hadir per hari (Hadir + Lewat + Kecemasan)
$days = ['Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat'];
$dates = [];
$data_presence = [];

for ($i = 0; $i < 5; $i++) {
    $date = date('Y-m-d', strtotime("monday this week +$i day"));
    $dates[] = $date;

    $query_day = "
    SELECT COUNT(DISTINCT no_kp_pengguna) AS hadir_count
    FROM log_keberadaan
    WHERE status_keberadaan IN ('Hadir', 'Lewat', 'Kecemasan')
    AND DATE(masa_imbasan) = ?
    ";
    $stmt = $conn->prepare($query_day);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = ($result && $row = $result->fetch_assoc()) ? (int)$row['hadir_count'] : 0;
    $data_presence[] = $count;
}

$conn->close();
?>
<?php
// Array of day names in Malay, Sunday to Saturday
$daysInMalay = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];

// Get today
$today = new DateTime();

// Find Monday of this week
// In PHP, 1 = Monday, 7 = Sunday
$dayOfWeek = (int)$today->format('N'); // 1 (Mon) to 7 (Sun)
$intervalToMonday = new DateInterval('P' . ($dayOfWeek - 1) . 'D');
$monday = clone $today;
$monday->sub($intervalToMonday);

// Build array of Monday to Friday with day name and date
$days = [];
for ($i = 0; $i < 5; $i++) {
    $currentDay = clone $monday;
    $currentDay->add(new DateInterval('P' . $i . 'D'));
    $dayName = $daysInMalay[(int)$currentDay->format('w')]; // 0 (Sun) to 6 (Sat)
    $dateStr = $currentDay->format('d/m');
    $days[] = "$dayName - $dateStr";
}
?>


<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utama Warden - Resonance Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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

        .stat-info h3 { font-size: 0.85rem; color: #64748b; margin-bottom: 2px; }
        .stat-info p { font-size: 1.5rem; font-weight: 700; color: #0f172a; }

        /* Chart Card */
        .chart-card {
            background: white;
            padding: 2rem;
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #f1f5f9;
        }

        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .chart-header h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; }

        .chart-wrapper {
            position: relative;
            height: 400px;
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
            <li><a href="keberadaan.php"><i class="fas fa-chart-pie"></i> <span>Keberadaan</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice"></i> <span>Laporan</span></a></li>
            <li><a href="daftar_ajk_penghuni.php"><i class="fas fa-user-plus"></i> <span>Pendaftaran</span></a></li>
            <li><a href="senarai_ajk_penghuni.php"><i class="fas fa-users"></i> <span>Senarai Penghuni</span></a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        
        <!-- Header -->
        <header class="header">
            <div>
                <h1>Selamat Datang, Warden</h1>
                <p style="color: var(--secondary-color); font-size: 0.9rem;">Paparan ringkas kehadiran minggu ini.</p>
            </div>
            
            <div class="header-right">
                <div class="battery-status" title="Status Bateri Peranti">
                    <i id="batteryIcon" class="fas fa-battery-full" style="color: var(--success);"></i>
                    <span id="batteryLevel" style="font-weight: 600; color: var(--text-main);">100%</span>
                </div>
                
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($nama_penuh); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($jawatan); ?></span>
                </div>

                <a href="../logout.php" style="text-decoration: none;">
                    <button class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> <span>Keluar</span>
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
                    <h3>Jumlah Pelajar</h3>
                    <p><?php echo $total_students; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-info">
                    <h3>Tidak Hadir</h3>
                    <p><?php echo $total_absent; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Hadir (Semasa)</h3>
                    <p><?php echo $total_present; ?></p>
                </div>
            </div>
        </div>

        <!-- Weekly Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>Analisis Kehadiran Mingguan</h2>
                <div style="font-size: 0.9rem; color: var(--secondary-color);">
                    <i class="far fa-calendar-alt"></i> <?php echo date('d M', strtotime($startOfWeek)) . ' - ' . date('d M', strtotime($endOfWeek)); ?>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="weeklyAttendanceChart"></canvas>
            </div>
        </div>

    </main>

    <!-- Overlay for Mobile Sidebar -->
    <div class="popup-overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Scripts -->
    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
    }

    // --- Chart Logic ---
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('weeklyAttendanceChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.5)'); 
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($days); ?>,
                datasets: [{
                    label: 'Kehadiran Pelajar',
                    data: <?php echo json_encode($data_presence); ?>,
                    borderColor: '#6366f1',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    fill: true,
                    tension: 0.4 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: <?php echo $total_students; ?>,
                        grid: { color: '#f1f5f9', borderDash: [5, 5] },
                        ticks: { font: { family: "'Inter', sans-serif" }, color: '#64748b' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: "'Inter', sans-serif" }, color: '#64748b' }
                    }
                },
                animation: { duration: 2000, easing: 'easeOutQuart' }
            }
        });
    });
    </script>
</body>
</html>
