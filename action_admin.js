function changeTab(tabName) {
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.style.display = 'none';
    });

    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });

    const selectedContent = document.getElementById(tabName);
    if (selectedContent) {
        selectedContent.style.display = 'block';
    }

    const selectedButton = document.querySelector(`.tab-button[onclick="changeTab('${tabName}')"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }

    history.pushState(null, '', `dashboard_admin.php?tab=${tabName}`);
}

function confirmDeletePengguna(id, nama) {
    if (confirm(`Hapus pengguna ID: ${id} (${nama})? Aksi tidak dapat dibatalkan.`)) {
        window.location.href = `dashboard_admin.php?action=delete_pengguna&id=${id}`;
    }
}

function confirmDeleteLowongan(id, pekerjaan) {
    if (confirm(`Hapus Lowongan ID: ${id} (${pekerjaan})? Aksi tidak dapat dibatalkan.`)) {
        window.location.href = `dashboard_admin.php?action=delete_lowongan&id=${id}`;
    }
}

function confirmUpdateLamaran(id, status) {
    let statusText = '';
    let confirmationMessage = '';

    switch (status) {
        case 'sukses':
            statusText = 'DITERIMA';
            confirmationMessage = `Ubah Lamaran ID ${id} menjadi DITERIMA (Sukses)?`;
            break;
        case 'ditolak':
            statusText = 'DITOLAK';
            confirmationMessage = `Ubah Lamaran ID ${id} menjadi DITOLAK?`;
            break;
        case 'pending':
            statusText = 'MENUNGGU';
            confirmationMessage = `Ubah Lamaran ID ${id} menjadi MENUNGGU (Pending)?`;
            break;
        default:
            return;
    }

    if (confirm(`Konfirmasi Status Lamaran\n\n${confirmationMessage}`)) {
        window.location.href = `dashboard_admin.php?action=update_lamaran_status&id=${id}&status=${status}`;
    }
}

function confirmToggleLowonganStatus(id, pekerjaan, actionText) {
    if (confirm(`Yakin ingin ${actionText} lowongan ID: ${id} (${pekerjaan})?`)) {
        window.location.href = `dashboard_admin.php?action=toggle_lowongan_status&id=${id}`;
    }
}

document.addEventListener('DOMContentLoaded', (event) => {
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || 'ringkasan';
    
    changeTab(initialTab);
});