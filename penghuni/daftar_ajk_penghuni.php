<?php
require_once '../db_connect.php';

$formStatus = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $ic = trim($_POST['ic']);
    $dorm = trim($_POST['dorm']);
    $kohort = intval($_POST['kohort']);
    $kelas = trim($_POST['kelas']);
    $jawatan = trim($_POST['jawatan']);

    if ($nama && $ic && $dorm && $kohort > 0 && $kelas && $jawatan) {
        $check = $conn->prepare("SELECT no_kp FROM pengguna WHERE no_kp = ?");
        $check->bind_param("s", $ic);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $formStatus = "Pendaftaran gagal: No. KP sudah wujud.";
        } else {
            $password_raw = substr($ic, -4);
            $stmt = $conn->prepare("
                INSERT INTO pengguna 
                (no_kp, nama_penuh, kata_laluan, dorm, kohort, kelas, jawatan, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')
            ");
            $stmt->bind_param("ssssiss", $ic, $nama, $password_raw, $dorm, $kohort, $kelas, $jawatan);

            if ($stmt->execute()) {
                $formStatus = "Pendaftaran berjaya untuk $nama.";
            } else {
                $formStatus = "Ralat semasa mendaftar: " . $stmt->error;
            }
        }
    } else {
        $formStatus = "Sila lengkapkan semua medan.";
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Penghuni - Resonance Log</title>
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
        .logout-btn { background: #fee2e2; color: #ef4444; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }

        /* Card & Form */
        .card { background: white; border-radius: 24px; padding: 2.5rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; max-width: 900px; margin: 0 auto; }
        .card-header { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
        .card-header h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .full-width { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.9rem; font-weight: 600; color: #64748b; }
        .form-group input { padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 1rem; outline: none; background: #f8fafc; transition: all 0.2s; }
        .form-group input:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        /* Custom Select Styling */
        .custom-select-wrapper { position: relative; width: 100%; }
        .custom-select { position: relative; display: flex; flex-direction: column; }
        .custom-select__trigger {
            padding: 0 16px; height: 48px; display: flex; align-items: center; justify-content: space-between;
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s;
        }
        .custom-select.open .custom-select__trigger { background: white; border-color: #6366f1; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
        .custom-select-options {
            position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e2e8f0; border-top: 0;
            border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; box-shadow: var(--card-shadow);
            opacity: 0; visibility: hidden; z-index: 100; transition: all 0.2s; max-height: 200px; overflow-y: auto;
        }
        .custom-select.open .custom-select-options { opacity: 1; visibility: visible; }
        .custom-option { padding: 12px 16px; cursor: pointer; transition: all 0.2s; }
        .custom-option:hover { background: #f8fafc; color: #6366f1; }
        .custom-option.selected { background: #6366f1; color: white; }

        /* Buttons */
        .form-actions { display: flex; gap: 12px; margin-top: 2rem; flex-wrap: wrap; }
        .btn { padding: 12px 24px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; font-size: 0.95rem; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-outline { background: white; border: 1px solid #e2e8f0; color: #64748b; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }

        /* Status Box */
        .status-box { padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600; font-size: 0.95rem; }
        .status-success { background: #ecfdf5; color: #059669; border: 1px solid #bbf7d0; }
        .status-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

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
            .form-grid { grid-template_columns: 1fr; }
            .form-actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown Logic
            document.addEventListener('click', function(e) {
                const select = e.target.closest('.custom-select');
                if (select) {
                    select.classList.toggle('open');
                } else {
                    document.querySelectorAll('.custom-select').forEach(s => s.classList.remove('open'));
                }
                
                if (e.target.classList.contains('custom-option')) {
                    const option = e.target;
                    const select = option.closest('.custom-select');
                    const triggerSpan = select.querySelector('.custom-select__trigger span');
                    const hiddenInput = select.previousElementSibling; // input[type=hidden]
                    
                    triggerSpan.textContent = option.textContent;
                    hiddenInput.value = option.dataset.value;
                    
                    select.querySelectorAll('.custom-option').forEach(o => o.classList.remove('selected'));
                    option.classList.add('selected');
                }
            });

            // Sidebar Logic
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.style.display = 'none';
                });
            }
        });
    </script>
</head>
<body>

<button class="menu-toggle" id="menuToggle">
    <i class="fas fa-bars"></i>
</button>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-cube fa-lg" style="color: var(--primary-color);"></i>
        <h2>Resonance Log</h2>
    </div>
    <ul class="sidebar-nav">
        <li><a href="utama.php"><i class="fas fa-tachometer-alt"></i> <span>Utama</span></a></li>
        <li><a href="keberadaan.php"><i class="fas fa-chart-pie"></i> <span>Keberadaan</span></a></li> 
        <li><a href="laporan.php"><i class="fas fa-file-invoice"></i> <span>Laporan</span></a></li>
        <li class="active"><a href="daftar_ajk_penghuni.php"><i class="fas fa-user-plus"></i> <span>Pendaftaran</span></a></li>
        <li><a href="senarai_ajk_penghuni.php"><i class="fas fa-users"></i> <span>Senarai Penghuni</span></a></li>
    </ul>
</aside>

<main class="main-content">
    <header class="header">
        <h1>Pendaftaran Baru</h1>
        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i> <span>Keluar</span>
        </button>
    </header>

    <div class="card">
        <div class="card-header">
            <h2>Masukkan Maklumat Penghuni</h2>
        </div>

        <?php if ($formStatus): ?>
            <div class="status-box <?= strpos($formStatus, 'berjaya') !== false ? 'status-success' : 'status-error' ?>">
                <?= htmlspecialchars($formStatus) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Nama Penuh</label>
                    <input type="text" name="nama" required placeholder="Masukkan nama penuh penghuni">
                </div>

                <div class="form-group">
                    <label>No. Kad Pengenalan</label>
                    <input type="text" name="ic" required placeholder="Cth: 060101101234">
                </div>

                <div class="form-group">
                    <label>Kohort</label>
                    <input type="number" name="kohort" required placeholder="Cth: 2024">
                </div>

                <div class="form-group">
                    <label>Dorm</label>
                    <!-- Custom Dropdown for Dorm -->
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="dorm" required>
                        <div class="custom-select">
                            <div class="custom-select__trigger"><span>Pilih Dorm</span>
                                <div class="arrow"></div>
                            </div>
                            <div class="custom-select-options">
                                <?php foreach(['G1','G2','G3','G4','G5','G6','G7','G8'] as $d): ?>
                                    <span class="custom-option" data-value="<?= $d ?>"><?= $d ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Kelas</label>
                    <!-- Custom Dropdown for Kelas -->
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="kelas" required>
                        <div class="custom-select">
                            <div class="custom-select__trigger"><span>Pilih Kelas</span>
                                <div class="arrow"></div>
                            </div>
                            <div class="custom-select-options">
                                <?php foreach(['IPD','ISK','MTK 1','MTK 2','MPI 1','MPI 2'] as $k): ?>
                                    <span class="custom-option" data-value="<?= $k ?>"><?= $k ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Jawatan</label>
                    <!-- Custom Dropdown for Jawatan -->
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="jawatan" required>
                        <div class="custom-select">
                            <div class="custom-select__trigger"><span>Pilih Jawatan</span>
                                <div class="arrow"></div>
                            </div>
                            <div class="custom-select-options">
                                <span class="custom-option" data-value="Pelajar">Pelajar</span>
                                <span class="custom-option" data-value="AJK">AJK</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Daftar Penghuni
                </button>
                <button type="button" class="btn btn-success" id="importBtn">
                    <i class="fas fa-file-excel"></i> Import Excel
                </button>
                <a href="../assets/template_import.csv" class="btn btn-outline" download>
                    <i class="fas fa-download"></i> Templat CSV
                </a>
            </div>
        </form>
    </div>

    <!-- Hidden file input -->
    <input type="file" id="excelFile" accept=".xlsx, .xls, .csv" style="display: none;" />
</main>

<div class="popup-overlay" id="overlay" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; z-index:999;"></div>

<!-- Include SheetJS logic -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.getElementById('importBtn').addEventListener('click', () => {
    document.getElementById('excelFile').click();
});

document.getElementById('excelFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(evt) {
        const data = evt.target.result;
        const workbook = XLSX.read(data, { type: 'binary' });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        const jsonData = XLSX.utils.sheet_to_json(worksheet);

        if (jsonData.length === 0) {
            alert("Fail Excel kosong.");
            return;
        }

        if (confirm(`Adakah anda pasti untuk mengimport ${jsonData.length} data?`)) {
            fetch('save_import_ajk.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(jsonData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    if (data.errors && data.errors.length > 0) {
                        alert("Beberapa ralat berlaku:\n" + data.errors.join('\\n'));
                    }
                    window.location.reload();
                } else {
                    alert("Ralat: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Ralat semasa memproses fail.");
            });
        }
    };
    reader.readAsBinaryString(file);
    // Reset file input
    this.value = '';
});
</script>

</body>
</html>
