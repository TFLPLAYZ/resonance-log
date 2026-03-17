# Responsive Design Update Script
# This file lists all pages that need responsive CSS added

## Pages Updated ✅
- [x] ajk/utama.php - Mobile menu + responsive CSS

## Pages Needing Update 📝

### AJK Module (5 pages)
- [ ] ajk/keberadaan.php
- [ ] ajk/daftar_pelajar.php
- [ ] ajk/senarai_pelajar.php
- [ ] ajk/daftar_fingerprint.php
- [ ] ajk/update_student.php

### Penghuni Module (7 pages)
- [ ] penghuni/utama.php
- [ ] penghuni/daftar_fingerprint.php
- [ ] penghuni/kemaskini_maklumat.php
- [ ] penghuni/laporan_keberadaan.php
- [ ] penghuni/imbas_kehadiran.php
- [ ] penghuni/proses_kehadiran.php
- [ ] penghuni/save_fingerprint.php

### Warden Module (7 pages)
- [ ] warden/utama.php
- [ ] warden/daftar_ajk_penghuni.php
- [ ] warden/senarai_ajk_penghuni.php
- [ ] warden/laporan.php
- [ ] warden/keberadaan.php
- [ ] warden/kemaskini_kehadiran.php
- [ ] warden/senarai_kehadiran_dorm.php

## Changes Needed Per Page

### 1. Add Responsive CSS Link
```html
<link rel="stylesheet" href="../assets/css/responsive.css" />
```

### 2. Add Mobile Menu Button (if has sidebar)
```html
<button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>
```

### 3. Add Sidebar ID
```html
<aside class="sidebar" id="sidebar">
```

### 4. Add Toggle JavaScript
```javascript
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    
    if (window.innerWidth <= 767) {
        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
});
```

### 5. Add Responsive Classes
- Tables: Wrap in `<div class="table-responsive">`
- Grids: Add class `grid`
- Buttons: Add class `btn-mobile-full` for full-width on mobile

## Manual Updates Required

Some pages may need custom responsive adjustments:
- Complex forms → Stack fields on mobile
- Wide tables → Enable horizontal scroll
- Charts → Adjust height for mobile
- Modals/Popups → Full screen on mobile
