$cssfix = @"
    <style>
        /* CRITICAL FIX: Ensure select dropdowns work */
        select {
            position: relative !important;
            z-index: 9999 !important;
            pointer-events: auto !important;
            cursor: pointer !important;
        }
        .main-content {
            position: relative !important;
            z-index: 100 !important;
        }
        @media (max-width: 767px) {
            .sidebar:not(.active) {
                pointer-events: none !important;
                z-index: 1 !important;
            }
            .sidebar.active {
                pointer-events: auto !important;
                z-index: 1000 !important;
            }
        }
    </style>
"@

$files = @(
    "warden\daftar_ajk_penghuni.php",
    "warden\senarai_ajk_penghuni.php",
    "warden\laporan.php",
    "warden\kemaskini_kehadiran.php",
    "warden\senarai_kehadiran_dorm.php",
    "ajk\daftar_pelajar.php",
    "ajk\senarai_pelajar.php",
    "ajk\kemaskini_keberadaan.php",
    "ajk\senarai_keberadaan_dorm.php",
    "ajk\senarai_fingerprint.php"
)

foreach ($file in $files) {
    $path = "c:\xampp\htdocs\resonance-log\$file"
    if (Test-Path $path) {
        $content = Get-Content $path -Raw
        
        # Check if fix already exists
        if ($content -notmatch "CRITICAL FIX: Ensure select dropdowns work") {
            # Find </head> and insert CSS before it
            $content = $content -replace "</head>", "$cssfix`n</head>"
            Set-Content $path -Value $content -NoNewline
            Write-Host "✓ Fixed: $file"
        } else {
            Write-Host "○ Already fixed: $file"
        }
    } else {
        Write-Host "✗ Not found: $file"
    }
}

Write-Host "`n=== DONE! All files have been fixed ===" -ForegroundColor Green
