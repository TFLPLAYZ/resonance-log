<?php
// Function to connect to the database
require_once '../db_connect.php';
// $conn is available from db_connect.php
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        /* Card & Students Info */
        .card { background: white; border-radius: 24px; padding: 2.5rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; max-width: 600px; margin: 0 auto; }
        .student-display { background: #f8fafc; padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid #e2e8f0; }
        .student-display p { margin-bottom: 0.5rem; font-size: 0.95rem; color: #475569; }
        .student-display p strong { color: #0f172a; }

        /* Form Styling */
        .form-group { margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 8px; }
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

        .btn { padding: 12px 24px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; font-size: 0.95rem; text-decoration: none; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }

        .form-actions { display: flex; gap: 12px; margin-top: 2rem; }

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
            .form-actions { flex-direction: column; }
            .btn { width: 100%; }
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
            <li class="active"><a href="keberadaan.php"><i class="fas fa-chart-pie"></i> <span>Keberadaan</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-file-invoice"></i> <span>Laporan</span></a></li>
            <li><a href="daftar_ajk_penghuni.php"><i class="fas fa-user-plus"></i> <span>Pendaftaran</span></a></li>
            <li><a href="senarai_ajk_penghuni.php"><i class="fas fa-users"></i> <span>Senarai Penghuni</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="header"><h1>Kemaskini Kehadiran</h1></header>

        <div class="card">
            <div id="editFormContainer">
                <form onsubmit="return false;">
                    <div class="student-display">
                        <p><strong>Nama:</strong> <span id="studentName">Memuatkan...</span></p>
                        <p><strong>No. Kad Pengenalan:</strong> <span id="studentIC"></span></p>
                    </div>
                    <div class="form-group">
                        <label>Status Kehadiran</label>
                        <!-- Custom Dropdown for Status -->
                        <div class="custom-select-wrapper">
                            <input type="hidden" id="statusSelect" name="status">
                            <div class="custom-select">
                                <div class="custom-select__trigger"><span>Pilih Status</span>
                                    <div class="arrow"></div>
                                </div>
                                <div class="custom-select-options">
                                    <span class="custom-option" data-value="Hadir">Hadir</span>
                                    <span class="custom-option" data-value="Tidak Hadir">Tidak Hadir</span>
                                    <span class="custom-option" data-value="Lewat">Lewat</span>
                                    <span class="custom-option" data-value="Kecemasan">Kecemasan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="catatanGroup" style="display: none;">
                        <label for="catatanInput">Catatan (jika kecemasan)</label>
                        <input type="text" id="catatanInput" placeholder="Cth: Demam">
                    </div>
                    <div class="form-actions">
                        <button type="button" id="saveBtn" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                        <a href="senarai_kehadiran_dorm.html" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
            <div id="notFoundMessage" style="display: none; text-align: center;">
                <h2>Penghuni Tidak Ditemui</h2>
                <a href="senarai_kehadiran_dorm.html" class="btn btn-primary">Kembali ke Senarai</a>
            </div>
        </div>
    </main>

    <script>
    // Updated JavaScript to work with Custom Dropdown
    document.addEventListener('DOMContentLoaded', () => {
        const studentData = [
            { ic: '060225160123', nama: 'Ammar Danish', status: 'Kecemasan', catatan: 'Demam' },
            { ic: '060311100451', nama: 'Ahmad Faizal', status: 'Lewat', catatan: '' },
            { ic: '060101100789', nama: 'Iskandar Zulkarnain', status: 'Hadir', catatan: '' }
        ];

        const urlParams = new URLSearchParams(window.location.search);
        const studentIcToEdit = urlParams.get('ic');
        const student = studentData.find(s => s.ic === studentIcToEdit);

        if (student) {
            document.getElementById('studentName').textContent = student.nama;
            document.getElementById('studentIC').textContent = student.ic;
            
            // Set Hidden Input Value
            const statusSelect = document.getElementById('statusSelect');
            statusSelect.value = student.status;
            
            // Update Visual Trigger Text
            const triggerSpan = document.querySelector('.custom-select__trigger span');
            if(triggerSpan) triggerSpan.textContent = student.status;
            
            // Update Selected Option Class
            document.querySelectorAll('.custom-option').forEach(opt => {
                if(opt.getAttribute('data-value') === student.status) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });

            document.getElementById('catatanInput').value = student.catatan;

            const toggleCatatan = () => {
                document.getElementById('catatanGroup').style.display = statusSelect.value === 'Kecemasan' ? 'block' : 'none';
            };
            toggleCatatan();
            
            // Listen to change event on the hidden input (dispatched by dropdown.js)
            statusSelect.addEventListener('change', toggleCatatan);

            document.getElementById('saveBtn').addEventListener('click', () => {
                alert('Maklumat Disimpan!');
                window.location.href = 'senarai_kehadiran_dorm.html';
            });
        } else {
            document.getElementById('editFormContainer').style.display = 'none';
            document.getElementById('notFoundMessage').style.display = 'block';
        }
    });
    </script>
    <div class="popup-overlay" id="overlay" onclick="toggleSidebar()" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; z-index:999;"></div>

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
