<?php
require_once '../db_connect.php';

// =========================
// HANDLE POST (UPDATE)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_kehadiran'])) {
    $no_kp_pengguna = $_POST['no_kp_pengguna'] ?? '';
    $status_keberadaan = $_POST['status_keberadaan'] ?? '';
    $catatan = $_POST['catatan'] ?? '';

    $allowed_statuses = ['Hadir', 'Lewat', 'Tidak Hadir', 'Kecemasan'];

    if (!empty($no_kp_pengguna) && in_array($status_keberadaan, $allowed_statuses)) {
        $stmt = $conn->prepare(
            "SELECT id_cap_jari FROM cap_jari WHERE no_kp_pengguna = ?"
        );
        $stmt->bind_param("s", $no_kp_pengguna);
        $stmt->execute();
        $res = $stmt->get_result();
        $cap = $res->fetch_assoc();
        $stmt->close();

        if ($cap) {
            $stmt2 = $conn->prepare(
                "INSERT INTO log_keberadaan 
                 (id_cap_jari, no_kp_pengguna, status_keberadaan, catatan) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt2->bind_param(
                "isss",
                $cap['id_cap_jari'],
                $no_kp_pengguna,
                $status_keberadaan,
                $catatan
            );
            $message = $stmt2->execute()
                ? "Kehadiran berjaya dikemaskini."
                : "Ralat: " . $stmt2->error;
            $stmt2->close();
        } else {
            $message = "Cap jari tidak dijumpai.";
        }
    } else {
        $message = "Data tidak sah.";
    }
}

// =========================
// GET DORM & DATE FILTERS
// =========================
$dormFilter = $_GET['dorm'] ?? null;
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// =========================
// FETCH DATA
// =========================

// Build sub-query conditions for date filtering
$dateCondition = "";
$dateParams = [];

if ($date_from) {
    $dateCondition .= " AND DATE(masa_imbasan) >= ?";
    $dateParams[] = $date_from;
}
if ($date_to) {
    $dateCondition .= " AND DATE(masa_imbasan) <= ?";
    $dateParams[] = $date_to;
}

$sql = "
SELECT 
    p.dorm,
    p.no_kp,
    p.nama_penuh,
    lk.status_keberadaan,
    lk.catatan
FROM pengguna p
LEFT JOIN (
    SELECT l1.*
    FROM log_keberadaan l1
    INNER JOIN (
        SELECT no_kp_pengguna, MAX(masa_imbasan) AS max_time
        FROM log_keberadaan
        WHERE 1=1 $dateCondition
        GROUP BY no_kp_pengguna
    ) l2 
    ON l1.no_kp_pengguna = l2.no_kp_pengguna
    AND l1.masa_imbasan = l2.max_time
) lk ON p.no_kp = lk.no_kp_pengguna
WHERE (p.jawatan = 'Pelajar' OR p.jawatan = 'AJK')
";

$params = [];
$types = "";

// Bind date parameters first (for subquery)
foreach ($dateParams as $dp) {
    $params[] = $dp;
    $types .= "s";
}

// Bind dorm filter (for main query)
if ($dormFilter) {
    $sql .= " AND p.dorm = ?";
    $params[] = $dormFilter;
    $types .= "s";
}

$sql .= " ORDER BY p.dorm, p.no_kp";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Senarai Kehadiran Dorm <?= htmlspecialchars($dormFilter ?? 'Semua') ?> - Resonance Log</title>
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
        .header h1 { font-size: 1.5rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 12px; }
        .header h1 span.badge-dorm { font-size: 0.85rem; padding: 6px 12px; background: #e0f2fe; color: #0369a1; border-radius: 50px; }
        .logout-btn { background: #fee2e2; color: #ef4444; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }

        /* Filter Card */
        .filter-card { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; }
        .filter-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 12px; }
        .filter-header h3 { font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; color: #0f172a; }
        .quick-filters { display: flex; gap: 8px; }
        .btn-quick { background: #f1f5f9; border: none; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: #475569; cursor: pointer; transition: 0.2s; }
        .btn-quick:hover { background: #e2e8f0; color: #6366f1; }
        
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #64748b; }
        .input-with-icon { position: relative; display: flex; align-items: center; }
        .input-with-icon i { position: absolute; left: 14px; color: #6366f1; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; outline: none; background: #f8fafc; transition: all 0.2s; }
        .form-group input:focus, .form-group select:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .form-group input { padding-left: 40px; }

        /* Table Card */
        .card { background: white; border-radius: 24px; padding: 2rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; margin-bottom: 2rem; }
        .table-responsive { border-radius: 16px; border: 1px solid #f1f5f9; overflow-x: auto; }
        .styled-table { width: 100%; border-collapse: collapse; text-align: left; }
        .styled-table th { background: #f8fafc; padding: 16px; font-weight: 600; color: #64748b; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
        .styled-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; color: #1e293b; }
        .styled-table tr:hover td { background: #f8fafc; }

        /* Status Colors */
        .status-badge { padding: 4px 10px; border-radius: 50px; font-weight: 600; font-size: 0.8rem; }
        .status-hadir { background: #dcfce7; color: #15803d; }
        .status-lewat { background: #fffbeb; color: #b45309; }
        .status-tidak { background: #fee2e2; color: #b91c1c; }
        .status-none { background: #f1f5f9; color: #64748b; }

        /* Inline Form Row */
        .update-form-row { background: #f8fafc; }
        .update-form-container { padding: 20px; background: white; border: 1px solid #e2e8f0; border-radius: 16px; margin: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .update-form { display: flex; gap: 15px; align-items: end; flex-wrap: wrap; }

        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; font-size: 0.9rem; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
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
            .filter-form { grid-template-columns: 1fr; }
            .update-form { flex-direction: column; align-items: stretch; }
            .styled-table, .styled-table thead, .styled-table tbody, .styled-table th, .styled-table td, .styled-table tr { display: block; }
            .styled-table thead tr { position: absolute; top: -9999px; left: -9999px; }
            .styled-table tr { border: 1px solid #f1f5f9; border-radius: 12px; margin-bottom: 1rem; padding: 1rem; }
            .styled-table td { border: none; padding: 8px 0; padding-left: 50%; position: relative; display: flex; align-items: center; justify-content: flex-end; }
            .styled-table td::before { content: attr(data-label); position: absolute; left: 0; font-weight: 600; color: #64748b; font-size: 0.8rem; }
            .update-form-row td { display: block; padding: 0 !important; }
            .update-form-row td::before { content: none; }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-cube fa-lg" style="color: var(--primary-color);"></i>
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
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="keberadaan.php" class="btn btn-secondary" style="padding: 8px 12px; border-radius: 50%;"><i class="fas fa-arrow-left"></i></a>
                <h1>Senarai Kehadiran <span class="badge-dorm"><?= htmlspecialchars($dormFilter ?? 'Semua') ?></span></h1>
            </div>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i> <span>Keluar</span>
            </button>
        </header>

        <?php if (isset($message)): ?>
            <div style="background: #ecfdf5; color: #047857; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #a7f3d0; animation: slideDown 0.4s;">
                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

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
                <?php if($dormFilter): ?><input type="hidden" name="dorm" value="<?= htmlspecialchars($dormFilter) ?>"><?php endif; ?>
                <div class="form-group">
                    <label>Dari Tarikh</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Hingga Tarikh</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                </div>
                <div class="form-group button-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tapis</button>
                    <a href="senarai_kehadiran_dorm.php<?= $dormFilter ? '?dorm='.urlencode($dormFilter) : '' ?>" class="btn btn-danger"><i class="fas fa-redo"></i> Reset</a>
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
            <div class="table-responsive">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Dorm</th>
                            <th>No KP</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Catatan</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $no_kp = htmlspecialchars($row['no_kp']);
                        ?>
                        <tr>
                            <td data-label="Dorm"><?= htmlspecialchars($row['dorm']) ?></td>
                            <td data-label="No KP"><?= $no_kp ?></td>
                            <td data-label="Nama" style="font-weight: 600;"><?= htmlspecialchars($row['nama_penuh']) ?></td>
                            <td data-label="Status">
                                <?php 
                                    $statusClass = 'status-none';
                                    $statusLabel = htmlspecialchars($row['status_keberadaan'] ?? '-');
                                    switch($row['status_keberadaan']) {
                                        case 'Hadir': $statusClass = 'status-hadir'; break;
                                        case 'Tidak Hadir': $statusClass = 'status-tidak'; break;
                                        case 'Lewat': $statusClass = 'status-lewat'; break;
                                        case 'Kecemasan': $statusClass = 'status-tidak'; break;
                                    }
                                ?>
                                <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td data-label="Catatan"><?= htmlspecialchars($row['catatan'] ?? '-') ?></td>
                            <td data-label="Tindakan">
                                <button class="btn btn-warning update-btn" data-id="<?= $no_kp ?>">
                                    <i class="fas fa-edit"></i> Kemaskini
                                </button>
                            </td>
                        </tr>

                        <tr class="update-form-row" id="form-<?= $no_kp ?>" style="display:none;">
                            <td colspan="6">
                                <div class="update-form-container">
                                    <form method="post" class="update-form">
                                        <input type="hidden" name="no_kp_pengguna" value="<?= $no_kp ?>">
                                        
                                        <div class="form-group" style="flex: 1;">
                                            <label>Status Keberadaan</label>
                                            <select name="status_keberadaan" required>
                                                <option value="">-- Sila Pilih --</option>
                                                <option value="Hadir">Hadir</option>
                                                <option value="Lewat">Lewat</option>
                                                <option value="Tidak Hadir">Tidak Hadir</option>
                                                <option value="Kecemasan">Kecemasan</option>
                                            </select>
                                        </div>

                                        <div class="form-group" style="flex: 2;">
                                            <label>Catatan</label>
                                            <input type="text" name="catatan" placeholder="Contoh: Demam, Balik Kampung...">
                                        </div>

                                        <div style="display: flex; gap: 8px;">
                                            <button type="submit" name="update_kehadiran" class="btn btn-primary">
                                                Simpan
                                            </button>
                                            <button type="button" class="btn btn-secondary cancel-btn" data-id="<?= $no_kp ?>">
                                                Batal
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 4rem;">
                                <div style="color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                                    <i class="fas fa-inbox fa-3x" style="opacity: 0.2;"></i>
                                    <p>Tiada data dijumpai.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
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

        document.querySelectorAll('.update-btn').forEach(btn => {
            btn.onclick = () => {
                document.querySelectorAll('.update-form-row').forEach(r => r.style.display = 'none');
                const formRow = document.getElementById('form-' + btn.dataset.id);
                formRow.style.display = window.innerWidth <= 768 ? 'block' : 'table-row';
            };
        });
        
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.onclick = () => {
                document.getElementById('form-' + btn.dataset.id).style.display = 'none';
            }
        });
    </script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
