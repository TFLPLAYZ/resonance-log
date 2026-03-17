<?php
// laporan.php - Enhanced version with improved functionality

// --- Database connection ---
require_once '../db_connect.php';
// $conn is available from db_connect.php

// --- Get filter parameters ---
$filter_dorm = isset($_GET['dorm']) ? $_GET['dorm'] : '';
$filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause for filters
$where_clauses = [];
$params = [];
$types = '';

if (!empty($date_from)) {
    $where_clauses[] = "DATE(l.masa_imbasan) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(l.masa_imbasan) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$join_needed = !empty($filter_dorm) || !empty($filter_kelas);

if ($join_needed) {
    if (!empty($filter_dorm)) {
        $where_clauses[] = "p.dorm = ?";
        $params[] = $filter_dorm;
        $types .= 's';
    }
    
    if (!empty($filter_kelas)) {
        $where_clauses[] = "p.kelas = ?";
        $params[] = $filter_kelas;
        $types .= 's';
    }
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$join_sql = $join_needed ? 'LEFT JOIN pengguna p ON l.no_kp_pengguna = p.no_kp' : '';

// DAILY DATA
$sql_daily = "SELECT 
    DATE(l.masa_imbasan) as day_date, 
    l.status_keberadaan, 
    COUNT(*) as total 
FROM log_keberadaan l
$join_sql
$where_sql
GROUP BY day_date, l.status_keberadaan 
ORDER BY day_date DESC";

$stmt_daily = $conn->prepare($sql_daily);
if (!empty($params)) {
    $stmt_daily->bind_param($types, ...$params);
}
$stmt_daily->execute();
$result_daily = $stmt_daily->get_result();
$daily_data = [];

while($row = $result_daily->fetch_assoc()){
    $date = $row['day_date'];
    $status = $row['status_keberadaan'];
    $total = (int)$row['total'];

    if (!isset($daily_data[$date])) {
        $daily_data[$date] = ['Hadir'=>0, 'Tidak Hadir'=>0, 'Lewat'=>0, 'Kecemasan'=>0];
    }
    $daily_data[$date][$status] = $total;
}

// WEEKLY DATA
$sql_weekly = "SELECT 
    YEAR(l.masa_imbasan) AS year,
    WEEK(l.masa_imbasan, 1) AS week,
    l.status_keberadaan, 
    COUNT(*) AS total
FROM log_keberadaan l
$join_sql
$where_sql
GROUP BY year, week, l.status_keberadaan
ORDER BY year DESC, week DESC";

$stmt_weekly = $conn->prepare($sql_weekly);
if (!empty($params)) {
    $stmt_weekly->bind_param($types, ...$params);
}
$stmt_weekly->execute();
$result_weekly = $stmt_weekly->get_result();
$weekly_data = [];

while($row = $result_weekly->fetch_assoc()){
    $year = $row['year'];
    $week = $row['week'];
    $status = $row['status_keberadaan'];
    $total = (int)$row['total'];

    $week_label = $year . "-W" . str_pad($week, 2, "0", STR_PAD_LEFT);
    if (!isset($weekly_data[$week_label])) {
        $weekly_data[$week_label] = ['Hadir'=>0, 'Tidak Hadir'=>0, 'Lewat'=>0, 'Kecemasan'=>0];
    }
    $weekly_data[$week_label][$status] = $total;
}

// MONTHLY DATA
$sql_monthly = "SELECT 
    YEAR(l.masa_imbasan) AS year,
    MONTH(l.masa_imbasan) AS month,
    l.status_keberadaan, 
    COUNT(*) AS total
FROM log_keberadaan l
$join_sql
$where_sql
GROUP BY year, month, l.status_keberadaan
ORDER BY year DESC, month DESC";

$stmt_monthly = $conn->prepare($sql_monthly);
if (!empty($params)) {
    $stmt_monthly->bind_param($types, ...$params);
}
$stmt_monthly->execute();
$result_monthly = $stmt_monthly->get_result();
$monthly_data = [];

while($row = $result_monthly->fetch_assoc()){
    $year = $row['year'];
    $month = $row['month'];
    $status = $row['status_keberadaan'];
    $total = (int)$row['total'];

    $month_label = $year . "-" . str_pad($month, 2, "0", STR_PAD_LEFT);
    if (!isset($monthly_data[$month_label])) {
        $monthly_data[$month_label] = ['Hadir'=>0, 'Tidak Hadir'=>0, 'Lewat'=>0, 'Kecemasan'=>0];
    }
    $monthly_data[$month_label][$status] = $total;
}

// Get summary statistics
$sql_summary = "SELECT 
    l.status_keberadaan, 
    COUNT(*) as total
FROM log_keberadaan l
$join_sql
$where_sql
GROUP BY l.status_keberadaan";

$stmt_summary = $conn->prepare($sql_summary);
if (!empty($params)) {
    $stmt_summary->bind_param($types, ...$params);
}
$stmt_summary->execute();
$result_summary = $stmt_summary->get_result();
$summary = ['Hadir'=>0, 'Tidak Hadir'=>0, 'Lewat'=>0, 'Kecemasan'=>0];

while($row = $result_summary->fetch_assoc()){
    $summary[$row['status_keberadaan']] = (int)$row['total'];
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Laporan Keberadaan - Resonance Log</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
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
        .header h1 { font-size: 1.5rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 12px; }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .logout-btn { background: #fee2e2; color: #ef4444; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }

        /* Filter Card */
        .filter-card { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; }
        .filter-card h3 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #64748b; }
        .form-group input, .form-group select { padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 1rem; outline: none; background: #f8fafc; transition: all 0.2s; font-family: 'Inter', sans-serif; }
        .form-group input:focus, .form-group select:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        /* Custom Select Styling */
        .custom-select-wrapper { position: relative; width: 100%; }
        .custom-select { position: relative; display: flex; flex-direction: column; }
        .custom-select__trigger {
            padding: 0 16px; height: 48px; display: flex; align-items: center; justify-content: space-between;
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s;
        }
        .custom-select.open .custom-select__trigger { background: white; border-color: #6366f1; }
        .custom-select-options {
            position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e2e8f0;
            border-radius: 12px; box-shadow: var(--card-shadow); opacity: 0; visibility: hidden; z-index: 100;
            transition: all 0.2s; max-height: 200px; overflow-y: auto; margin-top: 4px;
        }
        .custom-select.open .custom-select-options { opacity: 1; visibility: visible; }
        .custom-option { padding: 12px 16px; cursor: pointer; transition: all 0.2s; }
        .custom-option:hover { background: #f8fafc; color: #6366f1; }
        .custom-option.selected { background: #6366f1; color: white; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 20px; border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--card-shadow); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .stat-icon.hadir { background: #ecfdf5; color: #10b981; }
        .stat-icon.tidak-hadir { background: #fef2f2; color: #ef4444; }
        .stat-icon.lewat { background: #fffbeb; color: #f59e0b; }
        .stat-icon.kecemasan { background: #eff6ff; color: #3b82f6; }
        .stat-info h4 { font-size: 0.85rem; color: #64748b; margin-bottom: 4px; }
        .stat-info p { font-size: 1.5rem; font-weight: 700; color: #0f172a; }

        /* Chart Cards */
        .card { background: white; border-radius: 24px; padding: 2rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; margin-bottom: 2rem; }
        .card h2 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
        .chart-container { height: 350px; position: relative; }

        /* Buttons & Utils */
        .btn { padding: 10px 20px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; font-size: 0.9rem; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }

        .no-data { text-align: center; padding: 4rem; color: #94a3b8; }
        .no-data i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.2; }

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
        }

        @media print {
            .sidebar, .menu-toggle, .header-right, .filter-card, .btn { display: none !important; }
            .main-content { margin: 0; padding: 0; width: 100%; }
            .card { box-shadow: none; border: 1px solid #eee; page-break-inside: avoid; }
            .chart-container { height: 300px; }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <div class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

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
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Laporan Keberadaan</h1>
            <div class="header-right">
                <button class="btn btn-primary" id="printBtn"><i class="fas fa-print"></i> Cetak</button>
                <button class="btn btn-success" id="exportPdfBtn"><i class="fas fa-file-pdf"></i> PDF</button>
                <button class="btn btn-success" id="exportExcelBtn"><i class="fas fa-file-excel"></i> Excel</button>
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log Keluar</a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <h3><i class="fas fa-filter"></i> Tapis Data</h3>
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label>Asrama</label>
                    <select name="dorm">
                        <option value="">Semua Asrama</option>
                        <option value="Gemilang" <?= $filter_dorm == 'Gemilang' ? 'selected' : '' ?>>Gemilang</option>
                        <option value="Bestari" <?= $filter_dorm == 'Bestari' ? 'selected' : '' ?>>Bestari</option>
                        <option value="Murni" <?= $filter_dorm == 'Murni' ? 'selected' : '' ?>>Murni</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" name="kelas" placeholder="Cth: 5A" value="<?= htmlspecialchars($filter_kelas) ?>">
                </div>
                <div class="form-group">
                    <label>Tarikh Mula</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="form-group">
                    <label>Tarikh Akhir</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-search"></i> Tapis</button>
                </div>
            </form>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon hadir"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h4>Hadir</h4>
                    <p><?php echo $summary['Hadir']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon tidak-hadir"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <h4>Tidak Hadir</h4>
                    <p><?php echo $summary['Tidak Hadir']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon lewat"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h4>Lewat</h4>
                    <p><?php echo $summary['Lewat']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon kecemasan"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <h4>Kecemasan</h4>
                    <p><?php echo $summary['Kecemasan']; ?></p>
                </div>
            </div>
        </div>

        <!-- Daily Report -->
        <div class="card">
            <h2><i class="fas fa-calendar-day" style="color: var(--primary-color);"></i> Laporan Harian</h2>
            <?php if (empty($daily_data)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>Tiada data untuk dipaparkan</p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="reportDaily"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <!-- Weekly Report -->
        <div class="card">
            <h2><i class="fas fa-calendar-week" style="color: var(--primary-color);"></i> Laporan Mingguan</h2>
            <?php if (empty($weekly_data)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>Tiada data untuk dipaparkan</p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="reportWeekly"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <!-- Monthly Report -->
        <div class="card">
            <h2><i class="fas fa-calendar-alt" style="color: var(--primary-color);"></i> Laporan Bulanan</h2>
            <?php if (empty($monthly_data)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>Tiada data untuk dipaparkan</p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="reportMonthly"></canvas>
                </div>
            <?php endif; ?>
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

    // Charts Logic
    const dailyData = <?php echo json_encode($daily_data); ?>;
    const weeklyData = <?php echo json_encode($weekly_data); ?>;
    const monthlyData = <?php echo json_encode($monthly_data); ?>;
    const summaryData = <?php echo json_encode($summary); ?>;

    function prepareChartData(dataObj) {
        const labels = Object.keys(dataObj);
        const hadir = [], tidakHadir = [], lewat = [], kecemasan = [];

        labels.forEach(date => {
            hadir.push(dataObj[date]['Hadir'] ?? 0);
            tidakHadir.push(dataObj[date]['Tidak Hadir'] ?? 0);
            lewat.push(dataObj[date]['Lewat'] ?? 0);
            kecemasan.push(dataObj[date]['Kecemasan'] ?? 0);
        });

        return { labels, hadir, tidakHadir, lewat, kecemasan };
    }

    function createStackedBarChart(ctx, labels, hadir, tidakHadir, lewat, kecemasan, title) {
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Hadir', data: hadir, backgroundColor: '#10b981', borderRadius: 8 },
                    { label: 'Tidak Hadir', data: tidakHadir, backgroundColor: '#ef4444', borderRadius: 8 },
                    { label: 'Lewat', data: lewat, backgroundColor: '#f59e0b', borderRadius: 8 },
                    { label: 'Kecemasan', data: kecemasan, backgroundColor: '#3b82f6', borderRadius: 8 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { 
                        stacked: true, 
                        grid: { display: false },
                        ticks: { font: { family: 'Inter' } }
                    },
                    y: { 
                        stacked: true, 
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { font: { family: 'Inter' } }
                    }
                },
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: { 
                            font: { family: 'Inter', size: 12 },
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: { 
                        mode: 'index', 
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        titleFont: { family: 'Inter', size: 14 },
                        bodyFont: { family: 'Inter', size: 13 },
                        borderColor: '#e2e8f0',
                        borderWidth: 1
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (Object.keys(dailyData).length > 0) {
            const d = prepareChartData(dailyData);
            createStackedBarChart(document.getElementById('reportDaily').getContext('2d'), d.labels, d.hadir, d.tidakHadir, d.lewat, d.kecemasan, 'Laporan Harian');
        }
        if (Object.keys(weeklyData).length > 0) {
            const w = prepareChartData(weeklyData);
            createStackedBarChart(document.getElementById('reportWeekly').getContext('2d'), w.labels, w.hadir, w.tidakHadir, w.lewat, w.kecemasan, 'Laporan Mingguan');
        }
        if (Object.keys(monthlyData).length > 0) {
            const m = prepareChartData(monthlyData);
            createStackedBarChart(document.getElementById('reportMonthly').getContext('2d'), m.labels, m.hadir, m.tidakHadir, m.lewat, m.kecemasan, 'Laporan Bulanan');
        }
    });

    // PRINT functionality
    document.getElementById('printBtn').addEventListener('click', () => {
        window.print();
    });

    // EXPORT PDF
    document.getElementById('exportPdfBtn').addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        doc.text("Laporan Keberadaan", 14, 20);
        doc.text("Tarikh: " + new Date().toLocaleDateString('ms-MY'), 14, 30);
        
        let finalY = 40;
        
        function prepareTableData(dataObj) {
            const rows = [];
            for (const [date, statuses] of Object.entries(dataObj)) {
                rows.push([date, statuses['Hadir']||0, statuses['Tidak Hadir']||0, statuses['Lewat']||0, statuses['Kecemasan']||0]);
            }
            return rows;
        }
        
        if (Object.keys(dailyData).length > 0) {
            doc.text("Laporan Harian", 14, finalY);
            doc.autoTable({
                startY: finalY + 5,
                head: [['Tarikh', 'Hadir', 'Tidak Hadir', 'Lewat', 'Kecemasan']],
                body: prepareTableData(dailyData),
                theme: 'grid',
                headStyles: { fillColor: [99, 102, 241] }
            });
            finalY = doc.lastAutoTable.finalY + 15;
        }
        
        if (Object.keys(weeklyData).length > 0) {
            doc.text("Laporan Mingguan", 14, finalY);
            doc.autoTable({
                startY: finalY + 5,
                head: [['Minggu', 'Hadir', 'Tidak Hadir', 'Lewat', 'Kecemasan']],
                body: prepareTableData(weeklyData),
                theme: 'grid',
                headStyles: { fillColor: [99, 102, 241] }
            });
            finalY = doc.lastAutoTable.finalY + 15;
        }
        
        if (Object.keys(monthlyData).length > 0) {
            doc.text("Laporan Bulanan", 14, finalY);
            doc.autoTable({
                startY: finalY + 5,
                head: [['Bulan', 'Hadir', 'Tidak Hadir', 'Lewat', 'Kecemasan']],
                body: prepareTableData(monthlyData),
                theme: 'grid',
                headStyles: { fillColor: [99, 102, 241] }
            });
        }
        
        doc.save('Laporan_Keberadaan.pdf');
    });

    // EXPORT EXCEL
    document.getElementById('exportExcelBtn').addEventListener('click', () => {
        const wb = XLSX.utils.book_new();
        
        function prepareExcelData(dataObj) {
            const rows = [['Tarikh', 'Hadir', 'Tidak Hadir', 'Lewat', 'Kecemasan']];
            for (const [date, statuses] of Object.entries(dataObj)) {
                rows.push([date, statuses['Hadir']||0, statuses['Tidak Hadir']||0, statuses['Lewat']||0, statuses['Kecemasan']||0]);
            }
            return rows;
        }
        
        if (Object.keys(dailyData).length > 0) {
            const ws = XLSX.utils.aoa_to_sheet(prepareExcelData(dailyData));
            XLSX.utils.book_append_sheet(wb, ws, 'Harian');
        }
        
        if (Object.keys(weeklyData).length > 0) {
            const ws = XLSX.utils.aoa_to_sheet(prepareExcelData(weeklyData));
            XLSX.utils.book_append_sheet(wb, ws, 'Mingguan');
        }
        
        if (Object.keys(monthlyData).length > 0) {
            const ws = XLSX.utils.aoa_to_sheet(prepareExcelData(monthlyData));
            XLSX.utils.book_append_sheet(wb, ws, 'Bulanan');
        }
        
        XLSX.writeFile(wb, 'Laporan_Keberadaan.xlsx');
    });
    </script>
</body>
</html>