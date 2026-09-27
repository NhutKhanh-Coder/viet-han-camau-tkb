
function setStudentSchedMode(mode) {
    const isThi = (mode === 'thi');
    
    // Female theme elements
    const titleF = document.getElementById('stFemaleSchedTitle');
    const hocF = document.getElementById('stSchedHocContentFemale');
    const thiF = document.getElementById('stSchedThiContentFemale');
    const btnHocF = document.getElementById('btnTabHocFemale');
    const btnThiF = document.getElementById('btnTabThiFemale');

    if (titleF) {
        titleF.innerHTML = isThi ? '<i class="fa-solid fa-file-pen" style="color: #ef4444;"></i> LỊCH THI HÔM NAY' : '<i class="fa-solid fa-calendar-week"></i> LỊCH HỌC HÔM NAY';
    }
    if (hocF) hocF.style.display = isThi ? 'none' : 'flex';
    if (thiF) thiF.style.display = isThi ? 'flex' : 'none';
    if (btnHocF) {
        btnHocF.style.background = isThi ? '#fff' : '#8b5cf6';
        btnHocF.style.color = isThi ? '#8b5cf6' : '#fff';
    }
    if (btnThiF) {
        btnThiF.style.background = isThi ? '#ef4444' : '#fff';
        btnThiF.style.color = isThi ? '#fff' : '#ef4444';
    }

    // Male theme elements
    const titleM = document.getElementById('stMaleSchedTitle');
    const hocM = document.getElementById('stSchedHocContentMale');
    const thiM = document.getElementById('stSchedThiContentMale');
    const btnHocM = document.getElementById('btnTabHocMale');
    const btnThiM = document.getElementById('btnTabThiMale');

    if (titleM) {
        titleM.innerHTML = isThi ? '<i class="fa-solid fa-file-pen" style="color: #ef4444;"></i> LỊCH THI HÔM NAY' : '<i class="fa-solid fa-calendar-week"></i> LỊCH HỌC HÔM NAY';
    }
    if (hocM) hocM.style.display = isThi ? 'none' : 'flex';
    if (thiM) thiM.style.display = isThi ? 'flex' : 'none';
    if (btnHocM) {
        btnHocM.style.background = isThi ? 'transparent' : '#7c3aed';
        btnHocM.style.color = isThi ? '#7c3aed' : '#fff';
    }
    if (btnThiM) {
        btnThiM.style.background = isThi ? '#ef4444' : 'transparent';
        btnThiM.style.color = isThi ? '#fff' : '#ef4444';
    }
}

function openAnnModal(tb) {
    if (!tb) return;
    document.getElementById('stModalAnnTitle').innerText = tb.tieu_de || '';
    document.getElementById('stModalAnnContent').innerText = tb.noi_dung || '';
    document.getElementById('stModalAnnDate').innerText = tb.ngay_dang ? ('Ngày đăng: ' + tb.ngay_dang) : '';
    document.getElementById('stModalAnnBadge').innerText = tb.loai || 'Thông báo';
    document.getElementById('stModalAnnAuthor').innerText = tb.tac_gia ? ('Đăng bởi: ' + tb.tac_gia) : '';

    if (tb.loai === 'Lịch thi' || (tb.tieu_de && tb.tieu_de.includes('Lịch thi'))) {
        setStudentSchedMode('thi');
    }

    document.getElementById('annModalStudent').style.display = 'flex';
}
