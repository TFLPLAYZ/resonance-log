<?php
session_start();

// Database connection function inside this file
// Database connection
require_once '../db_connect.php';
// $conn is available from db_connect.php

if (!isset($_SESSION['no_kp']) || $_SESSION['jawatan'] !== 'Warden') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && !empty($_POST['no_kp'])) {
        $no_kp_to_delete = $_POST['no_kp'];
        $stmt = $conn->prepare("DELETE FROM pengguna WHERE no_kp = ?");
        $stmt->bind_param("s", $no_kp_to_delete);
        
        if ($stmt->execute()) {
            $msg = "Pengguna berjaya dipadam.";
        } else {
            $msg = "Gagal memadam pengguna: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'edit' && !empty($_POST['no_kp'])) {
        $no_kp = $_POST['no_kp'];
        $nama_penuh = $_POST['nama_penuh'];
        $dorm = $_POST['dorm'];
        $kohort = $_POST['kohort'];
        $kelas = $_POST['kelas'];
        $jawatan = $_POST['jawatan'];
        $status = $_POST['status'];

        $update_query = "UPDATE pengguna SET 
            nama_penuh=?,
            dorm=?,
            kohort=?,
            kelas=?,
            jawatan=?,
            status=?
            WHERE no_kp=?";
            
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssss", $nama_penuh, $dorm, $kohort, $kelas, $jawatan, $status, $no_kp);

        if ($stmt->execute()) {
            $msg = "Pengguna berjaya dikemaskini.";
        } else {
            $msg = "Gagal kemaskini pengguna: " . $stmt->error;
        }
        $stmt->close();
    }
}

$allowedSortColumns = ['no_kp', 'nama_penuh', 'dorm', 'kohort', 'kelas', 'jawatan', 'status'];
$sort_column = 'dorm';
$sort_order = 'ASC';

if (isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortColumns)) {
    $sort_column = $_GET['sort'];
}
if (isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC'])) {
    $sort_order = strtoupper($_GET['order']);
}

$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$where_clause = '';
$params = [];
$types = '';

if ($search !== '') {
    $search_param = "%$search%";
    $where_clause = "WHERE no_kp LIKE ? 
        OR nama_penuh LIKE ? 
        OR dorm LIKE ? 
        OR kohort LIKE ? 
        OR kelas LIKE ?
        OR jawatan LIKE ?
        OR status LIKE ?";
    // We have 7 placeholders
    $params = array_fill(0, 7, $search_param);
    $types = str_repeat('s', 7);
}

// Ensure sort column is safe (already vetted by allowed check above)
$query = "SELECT no_kp, nama_penuh, dorm, kohort, kelas, jawatan, status 
          FROM pengguna 
          $where_clause
          ORDER BY $sort_column $sort_order";

$stmt = $conn->prepare($query);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("RALAT PANGKALAN DATA: " . $stmt->error);
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senarai AJK - Resonance Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    
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
        .logout-btn { background: #fee2e2; color: #ef4444; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }

        /* Search & Sort Container */
        .search-sort-container { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; }
        .form-row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .form-row > div { flex: 1; min-width: 200px; }
        input[type="text"] { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; outline: none; background: #f8fafc; transition: all 0.2s; }
        input[type="text"]:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

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

        /* Table Card */
        .card { background: white; border-radius: 24px; padding: 2rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; margin-bottom: 2rem; overflow: hidden; }
        .table-responsive { overflow-x: auto; border-radius: 16px; border: 1px solid #f1f5f9; }
        .styled-table { width: 100%; border-collapse: collapse; text-align: left; }
        .styled-table th { background: #f8fafc; padding: 16px; font-weight: 600; color: #64748b; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
        .styled-table th a { color: inherit; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .styled-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; color: #1e293b; }
        .styled-table tr:hover td { background: #f8fafc; }

        /* Badges */
        .badge { padding: 6px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-dorm { background: #f1f5f9; color: #475569; }
        .badge-jawatan { background: #f5f3ff; color: #6366f1; }
        .badge-aktif { background: #dcfce7; color: #15803d; }
        .badge-tidak { background: #fee2e2; color: #ef4444; }

        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; font-size: 0.9rem; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn-save { background: #10b981; color: white; }
        .btn-cancel { background: #f1f5f9; color: #475569; }
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
            .form-row { flex-direction: column; align-items: stretch; }
            .form-row > div { width: 100%; }
            .btn { width: 100%; }
            
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
        <div class="sidebar-header"><h2>Resonance Log</h2></div>
        <ul class="sidebar-nav">
            <li><a href="utama.php"><i class="fas fa-tachometer-alt"></i> <span>Utama</span></a></li>
            <li><a href="keberadaan.php"><i class="fas fa-chart-pie"></i> <span>Keberadaan</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice"></i> <span>Laporan</span></a></li>
            <li><a href="daftar_ajk_penghuni.php"><i class="fas fa-user-plus"></i> <span>Pendaftaran</span></a></li>
            <li class="active"><a href="senarai_ajk_penghuni.php"><i class="fas fa-users"></i> <span>Senarai Penghuni</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1>Senarai AJK & Penghuni</h1>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i> <span>Keluar</span>
            </button>
        </header>

        <?php if (!empty($msg)) : ?>
            <div style="background: #ecfdf5; color: #065f46; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #a7f3d0; font-weight: 500;">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <section class="search-sort-container">
            <form method="GET" action="senarai_ajk_penghuni.php" class="form-row">
                <div style="flex: 2;">
                    <input type="text" name="search" placeholder="Cari nama, IC, atau dorm..." value="<?= htmlspecialchars($search) ?>" />
                </div>
                
                <div class="custom-select-wrapper" style="flex: 1;">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_column) ?>">
                    <div class="custom-select">
                        <div class="custom-select__trigger">
                            <span>Susun: <?= ucfirst(str_replace('_', ' ', $sort_column)) ?></span>
                            <div class="arrow"></div>
                        </div>
                        <div class="custom-select-options">
                            <?php foreach ($allowedSortColumns as $col): ?>
                                <span class="custom-option <?= $sort_column === $col ? 'selected' : '' ?>" data-value="<?= $col ?>">
                                    <?= ucfirst(str_replace('_', ' ', $col)) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="custom-select-wrapper" style="flex: 1;">
                    <input type="hidden" name="order" value="<?= htmlspecialchars($sort_order) ?>">
                    <div class="custom-select">
                        <div class="custom-select__trigger">
                            <span><?= $sort_order === 'ASC' ? 'Menaik (ASC)' : 'Menurun (DESC)' ?></span>
                            <div class="arrow"></div>
                        </div>
                        <div class="custom-select-options">
                            <span class="custom-option <?= $sort_order === 'ASC' ? 'selected' : '' ?>" data-value="ASC">Menaik (ASC)</span>
                            <span class="custom-option <?= $sort_order === 'DESC' ? 'selected' : '' ?>" data-value="DESC">Menurun (DESC)</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari
                </button>
                <a href="senarai_ajk_penghuni.php" class="btn btn-danger">Reset</a>
            </form>
        </section>

        <!-- Table Card -->
        <section class="card">
            <div class="table-responsive">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <?php
                            $columns = [
                                'no_kp' => 'No. KP',
                                'nama_penuh' => 'Nama',
                                'dorm' => 'Dorm',
                                'kohort' => 'Kohort',
                                'kelas' => 'Kelas',
                                'jawatan' => 'Jawatan',
                                'status' => 'Status',
                            ];
                            foreach ($columns as $col_key => $col_label) {
                                $new_order = ($sort_column === $col_key && $sort_order === 'ASC') ? 'DESC' : 'ASC';
                                $url = "?sort=$col_key&order=$new_order&search=" . urlencode($search);
                                echo "<th><a href=\"$url\">$col_label <i class='fas fa-sort'></i></a></th>";
                            }
                            ?>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $edit_no_kp = $_GET['edit_no_kp'] ?? '';

                    while ($row = mysqli_fetch_assoc($result)) :
                        if ($edit_no_kp === $row['no_kp']) :
                    ?>
                        <!-- Edit Mode Row -->
                        <tr class="edit-row">
                            <form method="POST" onsubmit="return confirm('Simpan perubahan?');">
                            <input type="hidden" name="action" value="edit" />
                            <input type="hidden" name="no_kp" value="<?= htmlspecialchars($row['no_kp']) ?>" />
                            
                            <td data-label="No. KP"><?= htmlspecialchars($row['no_kp']) ?></td>
                            <td data-label="Nama"><input type="text" name="nama_penuh" value="<?= htmlspecialchars($row['nama_penuh']) ?>" required style="padding:8px; border-radius:6px; border:1px solid #ccc; width:100%;"></td>
                            <td data-label="Dorm"><input type="text" name="dorm" value="<?= htmlspecialchars($row['dorm']) ?>" required style="padding:8px; border-radius:6px; border:1px solid #ccc; width:80px;"></td>
                            <td data-label="Kohort"><input type="text" name="kohort" value="<?= htmlspecialchars($row['kohort']) ?>" required style="padding:8px; border-radius:6px; border:1px solid #ccc; width:80px;"></td>
                            <td data-label="Kelas"><input type="text" name="kelas" value="<?= htmlspecialchars($row['kelas']) ?>" required style="padding:8px; border-radius:6px; border:1px solid #ccc; width:80px;"></td>
                            <td data-label="Jawatan">
                                <div class="custom-select-wrapper">
                                    <input type="hidden" name="jawatan" value="<?= htmlspecialchars($row['jawatan']) ?>">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger">
                                            <span><?= htmlspecialchars($row['jawatan']) ?></span>
                                            <div class="arrow"></div>
                                        </div>
                                        <div class="custom-select-options">
                                            <?php foreach (['Warden', 'AJK', 'Penghuni', 'Other'] as $r) echo "<span class='custom-option' data-value='$r'>$r</span>"; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Status">
                                <div class="custom-select-wrapper">
                                    <input type="hidden" name="status" value="<?= htmlspecialchars($row['status']) ?>">
                                    <div class="custom-select">
                                        <div class="custom-select__trigger">
                                            <span><?= htmlspecialchars($row['status']) ?></span>
                                            <div class="arrow"></div>
                                        </div>
                                        <div class="custom-select-options">
                                            <?php foreach (['Aktif', 'Tidak Aktif'] as $s) echo "<span class='custom-option' data-value='$s'>$s</span>"; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Tindakan">
                                <button type="submit" class="btn btn-save" title="Simpan"><i class="fas fa-check"></i></button>
                                <a href="senarai_ajk_penghuni.php" class="btn btn-cancel" title="Batal"><i class="fas fa-times"></i></a>
                            </td>
                            </form>
                        </tr>
                        <?php else: ?>
                        <!-- View Mode Row -->
                        <tr>
                            <td data-label="No. KP"><?= htmlspecialchars($row['no_kp']) ?></td>
                            <td data-label="Nama" style="font-weight: 500;"><?= htmlspecialchars($row['nama_penuh']) ?></td>
                            <td data-label="Dorm"><span class="badge badge-dorm"><?= htmlspecialchars($row['dorm']) ?></span></td>
                            <td data-label="Kohort"><?= htmlspecialchars($row['kohort']) ?></td>
                            <td data-label="Kelas"><?= htmlspecialchars($row['kelas']) ?></td>
                            <td data-label="Jawatan"><span class="badge badge-jawatan"><?= htmlspecialchars($row['jawatan']) ?></span></td>
                            <td data-label="Status">
                                <?php if($row['status'] == 'Aktif'): ?>
                                    <span class="badge badge-aktif">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-tidak">Tidak</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Tindakan">
                                <div style="display: flex; gap: 8px;">
                                    <a href="?edit_no_kp=<?= urlencode($row['no_kp']) ?>&<?= http_build_query(['search' => $search, 'sort' => $sort_column, 'order' => $sort_order]) ?>" class="btn btn-warning" style="padding: 8px;"><i class="fas fa-edit"></i></a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Padam pengguna ini?');">
                                        <input type="hidden" name="action" value="delete" />
                                        <input type="hidden" name="no_kp" value="<?= htmlspecialchars($row['no_kp']) ?>" />
                                        <button type="submit" class="btn btn-danger" style="padding: 8px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endif; endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <div class="popup-overlay" id="overlay" onclick="toggleSidebar()"></div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        }

        // Custom Select Logic
        document.addEventListener('click', function(e) {
            const select = e.target.closest('.custom-select');
            if (select) {
                select.classList.toggle('open');
                // Close other selects
                document.querySelectorAll('.custom-select').forEach(s => {
                    if (s !== select) s.classList.remove('open');
                });
            } else {
                document.querySelectorAll('.custom-select').forEach(s => s.classList.remove('open'));
            }
            
            if (e.target.classList.contains('custom-option')) {
                const option = e.target;
                const select = option.closest('.custom-select');
                const triggerSpan = select.querySelector('.custom-select__trigger span');
                const hiddenInput = select.closest('.custom-select-wrapper').querySelector('input[type=hidden]');
                
                triggerSpan.textContent = option.textContent;
                hiddenInput.value = option.dataset.value;
                
                select.querySelectorAll('.custom-option').forEach(o => o.classList.remove('selected'));
                option.classList.add('selected');
                
                // If it's the search/filter form, don't submit yet
                // But could auto-submit if desired: select.closest('form').submit();
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
