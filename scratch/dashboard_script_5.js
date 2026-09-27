
        function openCoupleEditModal() {
            var modal = document.getElementById('coupleEditModal');
            var overlay = document.getElementById('coupleEditOverlay');
            if (modal && overlay) {
                modal.classList.add('active');
                overlay.classList.add('active');
            }
        }

        function closeCoupleEditModal() {
            var modal = document.getElementById('coupleEditModal');
            var overlay = document.getElementById('coupleEditOverlay');
            if (modal && overlay) {
                modal.classList.remove('active');
                overlay.classList.remove('active');
            }
        }

        function handlePartnerImageUpload(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('inputPartnerAvatar').value = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function calculateCoupleDaysFromDate() {
            var dateInput = document.getElementById('inputCoupleStartDate');
            var daysInput = document.getElementById('inputCoupleDays');
            if (dateInput && dateInput.value && daysInput) {
                var start = new Date(dateInput.value);
                var now = new Date();
                var diffTime = now.getTime() - start.getTime();
                var diffDays = Math.floor(diffTime / (1000 * 3600 * 24));
                if (!isNaN(diffDays) && diffDays >= 0) {
                    daysInput.value = diffDays + ' Ngày';
                }
            }
        }

        function saveCoupleInfo() {
            var pName = document.getElementById('inputPartnerName').value.trim() || 'Chưa thêm tên người yêu';
            var pAv = document.getElementById('inputPartnerAvatar').value.trim();
            var startDate = document.getElementById('inputCoupleStartDate').value;
            var cDays = document.getElementById('inputCoupleDays').value.trim() || '0 Ngày';
            var cStatus = document.getElementById('inputCoupleStatus').value.trim() || 'Hãy cập nhật Góc Khoe Người Yêu!';

            localStorage.setItem('student_couple_name', pName);
            if (pAv) localStorage.setItem('student_couple_av', pAv);
            if (startDate) localStorage.setItem('student_couple_start_date', startDate);
            localStorage.setItem('student_couple_days', cDays);
            localStorage.setItem('student_couple_status', cStatus);

            loadCoupleInfo();
            closeCoupleEditModal();

            var toast = document.createElement('div');
            toast.className = 'mc-theme-toast';
            toast.innerHTML = '<i class="fa-solid fa-heart" style="color: #f43f5e;"></i> 🎉 Đã cập nhật Góc Khoe Người Yêu!';
            document.body.appendChild(toast);
            setTimeout(function() { toast.classList.add('show'); }, 10);
            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() { toast.remove(); }, 300);
            }, 2000);
        }

        function loadCoupleInfo() {
            var pName = localStorage.getItem('student_couple_name') || 'Chưa thêm tên người yêu';
            var pAv = localStorage.getItem('student_couple_av');
            var startDate = localStorage.getItem('student_couple_start_date');
            var cDays = localStorage.getItem('student_couple_days') || '0 Ngày';
            var cStatus = localStorage.getItem('student_couple_status') || '"Hãy cập nhật Góc Khoe Người Yêu!"';

            var elName = document.getElementById('couplePartnerName');
            var elAv = document.getElementById('couplePartnerAvatar');
            var elDays = document.getElementById('coupleDays');
            var elStatus = document.getElementById('coupleStatus');

            if (elName) elName.innerText = pName;
            if (elAv && pAv) {
                elAv.src = pAv;
                elAv.style.filter = 'none';
            }

            // Tự động tính số ngày từ Ngày bắt đầu yêu
            if (startDate) {
                var start = new Date(startDate);
                var now = new Date();
                var diffTime = now.getTime() - start.getTime();
                var diffDays = Math.floor(diffTime / (1000 * 3600 * 24));
                if (!isNaN(diffDays) && diffDays >= 0) {
                    cDays = diffDays + ' Ngày';
                }
            }

            if (elDays) {
                var displayDays = cDays.startsWith('💖') ? cDays.replace('💖', '').trim() : cDays;
                elDays.innerHTML = '💖 ' + displayDays;
                elDays.title = startDate ? 'Bắt đầu yêu từ: ' + startDate : 'Số ngày kỷ niệm';
            }
            if (elStatus) elStatus.innerText = cStatus.startsWith('"') ? cStatus : '"' + cStatus + '"';

            var inputN = document.getElementById('inputPartnerName');
            var inputA = document.getElementById('inputPartnerAvatar');
            var inputDate = document.getElementById('inputCoupleStartDate');
            var inputD = document.getElementById('inputCoupleDays');
            var inputS = document.getElementById('inputCoupleStatus');

            if (inputN) inputN.value = pName;
            if (inputA && pAv) inputA.value = pAv;
            if (inputDate && startDate) inputDate.value = startDate;
            if (inputD) inputD.value = cDays;
            if (inputS) inputS.value = cStatus.replace(/^"|"$/g, '');
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadCoupleInfo();
            setInterval(loadCoupleInfo, 60000);
        });
        