
    let currentProviderFilter = 'all';

    // Toggle 1 model cụ thể
    async function toggleModel(modelId, isEnabled, hashKey) {
        const card = document.querySelector(`.aim-model-card[data-id="${CSS.escape(modelId)}"]`);
        const label = document.getElementById(`label_${hashKey}`);
        
        // Optimistic UI
        if (isEnabled) {
            card?.classList.remove('disabled');
            if (label) {
                label.textContent = 'ĐANG MỞ';
                label.className = 'aim-status-label aim-status-open';
            }
        } else {
            card?.classList.add('disabled');
            if (label) {
                label.textContent = 'ĐÃ KHÓA';
                label.className = 'aim-status-label aim-status-closed';
            }
        }

        try {
            const res = await fetch('/tkb/api/ai_models_api.php?action=toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ model_id: modelId, enabled: isEnabled })
            });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (parseErr) {
                console.error('Toggle API response (not JSON):', text.substring(0, 300));
                showToast('❌ API trả về không hợp lệ. Có thể bạn chưa đăng nhập Admin hoặc phiên đã hết hạn.', true);
                revertToggle(hashKey, isEnabled, card, label);
                return;
            }
            if (data.success) {
                showToast(isEnabled ? `🟢 Đã MỞ mô hình "${modelId}" cho sinh viên!` : `🔴 Đã KHÓA mô hình "${modelId}" đối với sinh viên!`);
                updateStatCounts();
            } else {
                showToast('❌ Lỗi: ' + (data.error || 'Không thể lưu'), true);
                revertToggle(hashKey, isEnabled, card, label);
            }
        } catch (e) {
            console.error('Toggle fetch error:', e);
            showToast('❌ Lỗi kết nối mạng khi cập nhật trạng thái model', true);
            revertToggle(hashKey, isEnabled, card, label);
        }
    }

    function revertToggle(hashKey, isEnabled, card, label) {
        const cb = document.getElementById(`toggle_${hashKey}`);
        if (cb) cb.checked = !isEnabled;
        if (isEnabled) {
            card?.classList.add('disabled');
            if (label) { label.textContent = 'ĐÃ KHÓA'; label.className = 'aim-status-label aim-status-closed'; }
        } else {
            card?.classList.remove('disabled');
            if (label) { label.textContent = 'ĐANG MỞ'; label.className = 'aim-status-label aim-status-open'; }
        }
    }

    // Thao tác hàng loạt (Batch actions)
    async function batchAction(type, extra = {}) {
        try {
            const res = await fetch('/tkb/api/ai_models_api.php?action=batch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ type: type, ...extra })
            });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (parseErr) {
                console.error('Batch API response (not JSON):', text.substring(0, 300));
                showToast('❌ API trả về không hợp lệ. Có thể bạn chưa đăng nhập Admin hoặc phiên đã hết hạn.', true);
                return;
            }
            if (data.success) {
                showToast(`⚡ ${data.message}`);
                // Refresh danh sách switch trên trang
                const disabledList = data.disabled_models || [];
                document.querySelectorAll('.aim-model-card').forEach(card => {
                    const id = card.getAttribute('data-id');
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    const label = card.querySelector('.aim-status-label');
                    const isDis = disabledList.includes(id);

                    if (checkbox) checkbox.checked = !isDis;
                    if (isDis) {
                        card.classList.add('disabled');
                        if (label) {
                            label.textContent = 'ĐÃ KHÓA';
                            label.className = 'aim-status-label aim-status-closed';
                        }
                    } else {
                        card.classList.remove('disabled');
                        if (label) {
                            label.textContent = 'ĐANG MỞ';
                            label.className = 'aim-status-label aim-status-open';
                        }
                    }
                });
                updateStatCounts();
            } else {
                showToast('❌ Lỗi: ' + (data.error || 'Thao tác thất bại'), true);
            }
        } catch (e) {
            console.error('Batch fetch error:', e);
            showToast('❌ Lỗi kết nối khi gửi lệnh hàng loạt', true);
        }
    }

    // Cập nhật các ô số liệu thống kê trên header
    function updateStatCounts() {
        const total = document.querySelectorAll('.aim-model-card').length;
        const disabled = document.querySelectorAll('.aim-model-card.disabled').length;
        const enabled = total - disabled;
        const rate = total > 0 ? Math.round((enabled / total) * 100) : 100;

        document.getElementById('statTotal').textContent = total;
        document.getElementById('statEnabled').textContent = enabled;
        document.getElementById('statDisabled').textContent = disabled;
        document.getElementById('statRate').textContent = rate + '%';
    }

    // Lọc theo từ khóa tìm kiếm
    function filterModels() {
        const q = (document.getElementById('modelSearch').value || '').toLowerCase().trim();
        document.querySelectorAll('.aim-model-card').forEach(card => {
            const id = card.getAttribute('data-id').toLowerCase();
            const name = card.getAttribute('data-name').toLowerCase();
            const prov = card.getAttribute('data-provider').toLowerCase();

            const matchesSearch = !q || id.includes(q) || name.includes(q) || prov.includes(q);
            const matchesProv = currentProviderFilter === 'all' || prov.includes(currentProviderFilter);

            if (matchesSearch && matchesProv) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Lọc theo nhà cung cấp (Pills)
    function filterByProvider(prov, el) {
        currentProviderFilter = prov;
        document.querySelectorAll('.aim-pill').forEach(p => p.classList.remove('active'));
        if (el) el.classList.add('active');
        filterModels();
    }

    // Toast popup thông báo
    function showToast(text, isError = false) {
        const old = document.querySelector('.aim-toast');
        if (old) old.remove();

        const toast = document.createElement('div');
        toast.className = 'aim-toast';
        if (isError) toast.style.borderColor = 'rgba(239, 68, 68, 0.6)';
        toast.innerHTML = `<i class="fa-solid ${isError ? 'fa-triangle-exclamation' : 'fa-bell'}" style="color:${isError ? '#f87171' : '#c084fc'};"></i> <span>${text}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s, transform 0.3s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(15px)';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }
    