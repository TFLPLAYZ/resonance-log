<?php
// Start the session if you use session for login or user management
session_start();

// Create connection
require_once '../db_connect.php';
// $conn is available from db_connect.php

// Function to get attendance summary per dorm
function getAttendanceSummary($conn, $date_from = '', $date_to = '') {
    // We'll fetch dorm list dynamically from pengguna table
    $dorms = [];
    $dormResult = $conn->query("SELECT DISTINCT dorm FROM pengguna WHERE dorm IS NOT NULL ORDER BY dorm");
    while ($row = $dormResult->fetch_assoc()) {
        $dorms[] = $row['dorm'];
    }

    $summary = [];

    foreach ($dorms as $dorm) {
        // Total number of users in dorm (active status)
        $totalQuery = $conn->prepare("SELECT COUNT(*) AS total FROM pengguna WHERE dorm = ? AND status = 'Aktif'");
        $totalQuery->bind_param("s", $dorm);
        $totalQuery->execute();
        $totalResult = $totalQuery->get_result()->fetch_assoc();
        $totalUsers = (int)$totalResult['total'];

        // Count of users on 'Cuti Sem' in this dorm
        $cutiSemQuery = $conn->prepare("SELECT COUNT(*) AS cuti FROM pengguna WHERE dorm = ? AND status = 'Cuti Sem'");
        $cutiSemQuery->bind_param("s", $dorm);
        $cutiSemQuery->execute();
        $cutiSemResult = $cutiSemQuery->get_result()->fetch_assoc();
        $cutiSemCount = (int)$cutiSemResult['cuti'];

        // Build Attendance Query with Date Filters
        $sql = "SELECT status_keberadaan, COUNT(*) AS count
                FROM log_keberadaan lk
                JOIN pengguna p ON lk.no_kp_pengguna = p.no_kp
                WHERE p.dorm = ?";
        
        $params = [$dorm];
        $types = "s";

        if (!empty($date_from)) {
            $sql .= " AND DATE(lk.masa_imbasan) >= ?";
            $params[] = $date_from;
            $types .= "s";
        }

        if (!empty($date_to)) {
            $sql .= " AND DATE(lk.masa_imbasan) <= ?";
            $params[] = $date_to;
            $types .= "s";
        }

        // If no date filter is provided, default to latest date (same as before) for consistency
        if (empty($date_from) && empty($date_to)) {
             $dateResult = $conn->query("SELECT DATE(MAX(masa_imbasan)) AS latest_date FROM log_keberadaan");
             $latestDateRow = $dateResult->fetch_assoc();
             $latestDate = $latestDateRow['latest_date'];
             
             if ($latestDate) {
                 $sql .= " AND DATE(lk.masa_imbasan) = ?";
                 $params[] = $latestDate;
                 $types .= "s";
             }
        }

        $sql .= " GROUP BY status_keberadaan";

        $attendanceStmt = $conn->prepare($sql);
        $attendanceStmt->bind_param($types, ...$params);
        $attendanceStmt->execute();
        $attendanceResult = $attendanceStmt->get_result();

        // Initialize counts to 0
        $hadir = 0;
        $tidakHadir = 0;
        $lewat = 0;
        $kecemasan = 0;

        while ($row = $attendanceResult->fetch_assoc()) {
            switch ($row['status_keberadaan']) {
                case 'Hadir':
                    $hadir = (int)$row['count'];
                    break;
                case 'Tidak Hadir':
                    $tidakHadir = (int)$row['count'];
                    break;
                case 'Lewat':
                    $lewat = (int)$row['count'];
                    break;
                case 'Kecemasan':
                    $kecemasan = (int)$row['count'];
                    break;
            }
        }

        $summary[] = [
            'dorm' => $dorm,
            'total' => $totalUsers,
            'hadir' => $hadir,
            'tidak_hadir' => $tidakHadir,
            'lewat' => $lewat,
            'kecemasan' => $kecemasan,
            'cuti_sem' => $cutiSemCount,
        ];

        // Close statements
        $totalQuery->close();
        $cutiSemQuery->close();
        $attendanceStmt->close();
    }

    return $summary;
}

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Fetch attendance summary
$attendanceSummary = getAttendanceSummary($conn, $date_from, $date_to);

?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keberadaan - Resonance Log</title>
    <!-- New Unique CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        .header-right { display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: #fee2e2; color: #ef4444; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .battery-status { background: white; padding: 8px 16px; border-radius: 50px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; font-weight: 600; color: #10b981; }

        /* Filter Card */
        .filter-card { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; }
        .filter-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 15px; }
        .filter-header h3 { font-size: 1.1rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        
        .quick-filters { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-quick { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 50px; font-size: 0.85rem; color: #64748b; cursor: pointer; transition: all 0.2s; font-weight: 600; }
        .btn-quick:hover { background: #f5f3ff; color: #6366f1; border-color: #6366f1; }
        
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #64748b; }
        .input-with-icon { position: relative; width: 100%; }
        .input-with-icon i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6366f1; z-index: 10; }
        .form-group input { padding: 12px 16px 12px 40px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 1rem; outline: none; background: #f8fafc; transition: all 0.2s; }
        .form-group input:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .button-group { display: flex; gap: 12px; }

        /* Summary Card & Table */
        .card { background: white; border-radius: 24px; padding: 2rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; margin-bottom: 2rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .card-header h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; }
        
        .table-responsive { overflow-x: auto; border-radius: 16px; border: 1px solid #f1f5f9; }
        .styled-table { width: 100%; border-collapse: collapse; text-align: left; }
        .styled-table th { background: #f8fafc; padding: 16px; font-weight: 600; color: #64748b; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
        .styled-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; color: #1e293b; }
        .styled-table tr:hover td { background: #f8fafc; }

        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; font-size: 0.9rem; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn-info { background: #f5f3ff; color: #6366f1; border: 1px solid #e0e7ff; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }

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
            .filter-header { flex-direction: column; align-items: stretch; }
            .quick-filters { justify-content: center; }
            .card-header { flex-direction: column; align-items: stretch; }
            .action-buttons { flex-direction: column; }
            
            /* Responsive Table */
            .styled-table, .styled-table thead, .styled-table tbody, .styled-table th, .styled-table td, .styled-table tr { display: block; }
            .styled-table thead tr { position: absolute; top: -9999px; left: -9999px; }
            .styled-table tr { border: 1px solid #f1f5f9; border-radius: 12px; margin-bottom: 1rem; padding: 1rem; }
            .styled-table td { border: none; padding: 8px 0; padding-left: 50%; position: relative; display: flex; align-items: center; justify-content: flex-end; }
            .styled-table td::before { content: attr(data-label); position: absolute; left: 0; font-weight: 600; color: #64748b; font-size: 0.8rem; }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            
            <h2>Resonance Log</h2>
        </div>
        <ul class="sidebar-nav">
            <li><a href="utama.php"><i class="fas fa-tachometer-alt"></i> <span>Utama</span></a></li>
            <li class="active"><a href="keberadaan.php"><i class="fas fa-chart-pie"></i> <span>Keberadaan</span></a></li> 
            <li><a href="laporan.php"><i class="fas fa-file-invoice"></i> <span>Laporan</span></a></li>
            <li><a href="daftar_ajk_penghuni.php"><i class="fas fa-user-plus"></i> <span>Pendaftaran</span></a></li>
            <li><a href="senarai_ajk_penghuni.php"><i class="fas fa-users"></i> <span>Senarai Penghuni</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1>Status Keberadaan</h1>
            <div class="header-right">
                <div class="battery-status">
                    <i id="batteryIcon" class="fas fa-battery-full"></i>
                    <span id="batteryLevel">100%</span>
                </div>
                <button class="logout-btn" onclick="window.location.href='../logout.php'">
                    <i class="fas fa-sign-out-alt"></i> <span>Keluar</span>
                </button>
            </div>
        </header>

        <div class="filter-card">
            <div class="filter-header">
                <h3><i class="fas fa-filter"></i> Penapis Masa</h3>
                <div class="quick-filters">
                    <button type="button" class="btn-quick" onclick="setDateRange('today')">Hari Ini</button>
                    <button type="button" class="btn-quick" onclick="setDateRange('yesterday')">Semalam</button>
                    <button type="button" class="btn-quick" onclick="setDateRange('this_week')">Minggu Ini</button>
                    <button type="button" class="btn-quick" onclick="setDateRange('this_month')">Bulan Ini</button>
                </div>
            </div>
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label>Dari Tarikh</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Hingga Tarikh</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>
                <div class="form-group button-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tapis</button>
                    <a href="keberadaan.php" class="btn btn-danger"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>

        <script>
            function setDateRange(type) {
                const today = new Date();
                let fromDate, toDate;
                
                // Helper to format date as YYYY-MM-DD
                const formatDate = (date) => {
                    const d = new Date(date);
                    let month = '' + (d.getMonth() + 1);
                    let day = '' + d.getDate();
                    const year = d.getFullYear();

                    if (month.length < 2) month = '0' + month;
                    if (day.length < 2) day = '0' + day;

                    return [year, month, day].join('-');
                }

                if (type === 'today') {
                    fromDate = formatDate(today);
                    toDate = formatDate(today);
                } else if (type === 'yesterday') {
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    fromDate = formatDate(yesterday);
                    toDate = formatDate(yesterday);
                } else if (type === 'this_week') {
                    const firstDay = new Date(today.setDate(today.getDate() - today.getDay())); // Sunday
                    const lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 6)); // Saturday
                    fromDate = formatDate(firstDay);
                    toDate = formatDate(lastDay); 
                } else if (type === 'this_month') {
                    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    fromDate = formatDate(firstDay);
                    toDate = formatDate(lastDay);
                }

                document.getElementById('date_from').value = fromDate;
                document.getElementById('date_to').value = toDate;
            }
        </script>

        <div class="card">
            <div class="card-header">
                <h2>Ringkasan Kehadiran Mengikut Dorm</h2>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="exportTableToExcel('kehadiranTable', 'Ringkasan_Kehadiran')">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-danger" onclick="exportTableToPDF('kehadiranTable', 'Ringkasan_Kehadiran')">
                         <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="styled-table" id="kehadiranTable">
                    <thead>
                        <tr>
                            <th>Dorm</th>
                            <th>Keberadaan</th>
                            <th>Hadir</th>
                            <th>Tidak Hadir</th>
                            <th>Lewat</th>
                            <th>Kecemasan</th>
                            <th>Cuti Sem</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceSummary as $row): ?>
                        <tr>
                            <td data-label="Dorm"><?= htmlspecialchars($row['dorm']) ?></td>
                            <td data-label="Keberadaan"><?= $row['total'] . '/' . $row['total'] ?></td>
                            <td data-label="Hadir"><?= $row['hadir'] ?></td>
                            <td data-label="Tidak Hadir"><?= $row['tidak_hadir'] ?></td>
                            <td data-label="Lewat"><?= $row['lewat'] ?></td>
                            <td data-label="Kecemasan"><?= $row['kecemasan'] ?></td>
                            <td data-label="Cuti Sem"><?= $row['cuti_sem'] ?></td>
                            <td data-label="Tindakan">
                                <a href="senarai_kehadiran_dorm.php?dorm=<?= urlencode($row['dorm']) ?>" class="btn btn-info">
                                    <i class="fas fa-list"></i> Senarai
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="popup-overlay" id="overlay" onclick="toggleSidebar()" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; z-index:999;"></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        }

        // --- Export Functions (Mock Implementation for now, keeping existing if present or adding basic logic) ---
        // Note: Real export requires the libraries linked in head
        
        function exportTableToExcel(tableID, filename = ''){
            // Basic implementation using the included library
            var downloadLink;
            var dataType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            var tableSelect = document.getElementById(tableID);
            var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
            
            // This is a simplified placeholder. Ideally use SheetJS (XLSX) properly:
             const wb = XLSX.utils.table_to_book(tableSelect, {sheet:"Sheet1"});
             XLSX.writeFile(wb, filename + '.xlsx');
        }

        function exportTableToPDF(tableID, filename = ''){
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.autoTable({ html: '#' + tableID });
            doc.save(filename + '.pdf');
        }
    </script>
</body>
</html>

<?php
// Close DB connection
$conn->close();
?>
