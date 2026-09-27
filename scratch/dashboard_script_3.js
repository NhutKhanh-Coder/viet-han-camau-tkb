
        var svId = "";
        function handleTikTokFileUpload(input) {
            if (input.files && input.files[0]) {
                var file = input.files[0];
                var videoUrl = URL.createObjectURL(file);
                
                var containers = [document.getElementById('tiktokFrameContainer'), document.getElementById('tiktokFrameContainerMale')];
                containers.forEach(function(container) {
                    if (container) {
                        renderVideoInContainer(container, videoUrl);
                    }
                });

                var formData = new FormData();
                formData.append('action', 'save_tiktok_video');
                formData.append('is_ajax', '1');
                formData.append('video_file', file);
                fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.video_url) {
                        try { localStorage.setItem('st_saved_tiktok_video_' + svId, d.video_url); } catch(e){}
                    }
                })
                .catch(e => console.error(e));
            }
        }

        function changeTikTokVideo() {
            var urlInput = document.getElementById('inputTikTokUrl');
            if (!urlInput) return;
            var url = urlInput.value.trim();
            if (!url) return;

            var containers = [document.getElementById('tiktokFrameContainer'), document.getElementById('tiktokFrameContainerMale')];
            containers.forEach(function(container) {
                if (container) {
                    renderVideoInContainer(container, url);
                }
            });

            try { localStorage.setItem('st_saved_tiktok_video_' + svId, url); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_tiktok_video');
            formData.append('is_ajax', '1');
            formData.append('video_url', url);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function changeTikTokVideoMale() {
            var urlInput = document.getElementById('inputTikTokUrlMale');
            if (!urlInput) return;
            var url = urlInput.value.trim();
            if (!url) return;

            var containers = [document.getElementById('tiktokFrameContainer'), document.getElementById('tiktokFrameContainerMale')];
            containers.forEach(function(container) {
                if (container) {
                    renderVideoInContainer(container, url);
                }
            });

            try { localStorage.setItem('st_saved_tiktok_video_' + svId, url); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_tiktok_video');
            formData.append('is_ajax', '1');
            formData.append('video_url', url);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function toggleTikTokVideoFitMode() {
            var currentFit = localStorage.getItem('st_saved_video_fit_' + svId) || 'contain';
            var newFit = (currentFit === 'contain') ? 'cover' : 'contain';

            var vids = [document.getElementById('tiktokPlayerVideo'), document.getElementById('tiktokPlayerVideoMale')];
            vids.forEach(function(v) {
                if (v) v.style.objectFit = newFit;
            });

            var btns = [document.getElementById('btnVideoFitToggle'), document.getElementById('btnVideoFitToggleMale')];
            btns.forEach(function(b) {
                if (b) b.innerHTML = (newFit === 'contain') ? '🖼️ Vừa Khung' : '🔍 Lấp Đầy';
            });

            try { localStorage.setItem('st_saved_video_fit_' + svId, newFit); } catch(e){}
        }

        function renderVideoInContainer(container, url) {
            if (!container || !url) return;
            var fitMode = localStorage.getItem('st_saved_video_fit_' + svId) || 'cover';
            var vidId = (container.id === 'tiktokFrameContainerMale') ? 'tiktokPlayerVideoMale' : 'tiktokPlayerVideo';

            if (url.match(/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i) || url.indexOf('blob:') === 0 || url.indexOf('data:video') === 0 || url.indexOf('/uploads/videos/') !== -1) {
                container.innerHTML = '<video id="' + vidId + '" src="' + url + '" autoplay loop muted playsinline controls style="width:100%; height:100%; max-height:100%; object-fit:' + fitMode + '; border-radius:14px; display:block; background:#000;"></video>';
                return;
            }

            var ttMatch = url.match(/\/video\/(\d+)/) || url.match(/\/v\/(\d+)/) || url.match(/modal_id=(\d+)/);
            if (ttMatch && ttMatch[1]) {
                var videoId = ttMatch[1];
                container.innerHTML = '<iframe src="https://www.tiktok.com/embed/v2/' + videoId + '" style="width:100%; height:100%; max-height:100%; border:none; overflow:hidden; border-radius:14px;" scrolling="no" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture"></iframe>';
                return;
            }

            var ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/);
            if (ytMatch && ytMatch[1]) {
                var ytId = ytMatch[1];
                container.innerHTML = '<iframe src="https://www.youtube.com/embed/' + ytId + '?autoplay=1&rel=0" style="width:100%; height:100%; max-height:100%; border:none; overflow:hidden; border-radius:14px;" scrolling="no" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture"></iframe>';
                return;
            }

            container.innerHTML = '<video id="' + vidId + '" src="' + url + '" autoplay loop muted playsinline controls style="width:100%; height:100%; max-height:100%; object-fit:' + fitMode + '; border-radius:14px; display:block; background:#000;"></video>';
        }


        function toggleBannerFitMode() {
            var img = document.getElementById('ltHeroBannerImg');
            var btn = document.getElementById('btnBannerFitToggle');
            if (!img) return;

            var currentFit = img.style.objectFit || 'cover';
            var newFit = (currentFit === 'contain') ? 'cover' : 'contain';

            img.style.objectFit = newFit;
            if (btn) {
                btn.innerHTML = (newFit === 'contain') ? '🖼️ Vừa Khung (Full)' : '🔍 Lấp Đầy (Cover)';
            }

            try { localStorage.setItem('st_saved_banner_fit_' + svId, newFit); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_banner_fit');
            formData.append('is_ajax', '1');
            formData.append('banner_fit', newFit);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function setBannerPosPreset(pos) {
            var img = document.getElementById('ltHeroBannerImg');
            if (img) {
                img.style.objectPosition = pos;
            }
            try { localStorage.setItem('st_saved_banner_pos_' + svId, pos); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_banner_pos');
            formData.append('is_ajax', '1');
            formData.append('banner_pos', pos);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function updateLiveClockDisplay() {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            var seconds = String(now.getSeconds()).padStart(2, '0');
            var timeString = hours + ':' + minutes + ':' + seconds;

            var days = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
            var dayName = days[now.getDay()];
            var dateNum = String(now.getDate()).padStart(2, '0');
            var monthNum = String(now.getMonth() + 1).padStart(2, '0');
            var dateString = dayName + ', ' + dateNum + '/' + monthNum;

            var clockEls = [document.getElementById('ltLiveClock'), document.getElementById('ltLiveClockMale')];
            clockEls.forEach(function(el) {
                if (el) el.textContent = timeString;
            });

            var dateEls = [document.getElementById('ltLiveDate'), document.getElementById('ltLiveDateMale')];
            dateEls.forEach(function(el) {
                if (el) el.textContent = dateString;
            });
        }

        function sfSaveHeroEdits() {
            try {
                var nameEl = document.getElementById('sfHeroName');
                var quoteEl = document.getElementById('sfHeroQuote');
                var monEl = document.getElementById('sfStatMon');
                var baiEl = document.getElementById('sfStatBai');
                var diemEl = document.getElementById('sfStatDiem');

                if (nameEl) localStorage.setItem('sf_hero_name_' + "", nameEl.innerHTML);
                if (quoteEl) localStorage.setItem('sf_hero_quote_' + "", quoteEl.innerHTML);
                if (monEl) localStorage.setItem('sf_stat_mon_' + "", monEl.innerText);
                if (baiEl) localStorage.setItem('sf_stat_bai_' + "", baiEl.innerText);
                if (diemEl) localStorage.setItem('sf_stat_diem_' + "", diemEl.innerText);
            } catch(e){}
        }

        function sfLoadHeroEdits() {
            try {
                var svId = "";
                var name = localStorage.getItem('sf_hero_name_' + svId);
                var quote = localStorage.getItem('sf_hero_quote_' + svId);
                var mon = localStorage.getItem('sf_stat_mon_' + svId);
                var bai = localStorage.getItem('sf_stat_bai_' + svId);
                var diem = localStorage.getItem('sf_stat_diem_' + svId);

                var nameEl = document.getElementById('sfHeroName');
                var quoteEl = document.getElementById('sfHeroQuote');
                var monEl = document.getElementById('sfStatMon');
                var baiEl = document.getElementById('sfStatBai');
                var diemEl = document.getElementById('sfStatDiem');

                if (name && nameEl) nameEl.innerHTML = name;
                if (quote && quoteEl) quoteEl.innerHTML = quote;
                if (mon && monEl) monEl.innerText = mon;
                if (bai && baiEl) baiEl.innerText = bai;
                if (diem && diemEl) diemEl.innerText = diem;
            } catch(e){}
        }

        window.sfSocialBoxClick = function(platform) {
            var svId = "";
            var key = 'sf_social_link_' + platform + '_' + svId;
            var currentLink = localStorage.getItem(key) || '';

            if (!currentLink) {
                var name = platform === 'facebook' ? 'Facebook' : (platform === 'tiktok' ? 'TikTok' : 'Instagram');
                var newLink = prompt('Dán đường dẫn trang ' + name + ' của bạn vào đây:', '');
                if (newLink !== null && newLink.trim() !== '') {
                    newLink = newLink.trim();
                    if (newLink.indexOf('http') !== 0) newLink = 'https://' + newLink;
                    localStorage.setItem(key, newLink);
                    sfLoadSocialLinks();
                    if (typeof sfShowToast === 'function') sfShowToast('✅ Đã gắn link ' + name + '!');
                }
            } else {
                window.open(currentLink, '_blank');
            }
        };

        window.sfSocialPenClick = function(platform, e) {
            if (e) e.stopPropagation();
            var svId = "";
            var key = 'sf_social_link_' + platform + '_' + svId;
            var currentLink = localStorage.getItem(key) || '';
            var name = platform === 'facebook' ? 'Facebook' : (platform === 'tiktok' ? 'TikTok' : 'Instagram');
            var newLink = prompt('Sửa đường dẫn ' + name + ' của bạn:', currentLink);
            if (newLink !== null) {
                newLink = newLink.trim();
                if (newLink !== '' && newLink.indexOf('http') !== 0) newLink = 'https://' + newLink;
                localStorage.setItem(key, newLink);
                sfLoadSocialLinks();
                if (typeof sfShowToast === 'function') sfShowToast('✅ Đã cập nhật link ' + name + '!');
            }
        };

        window.openSocialLinksModal = function(platform) {
            var modal = document.getElementById('socialLinksModal');
            var svId = "";
            var fb = localStorage.getItem('sf_social_link_facebook_' + svId) || '';
            var tiktok = localStorage.getItem('sf_social_link_tiktok_' + svId) || '';
            var ig = localStorage.getItem('sf_social_link_instagram_' + svId) || '';

            var inFb = document.getElementById('inputSocialFb');
            var inTiktok = document.getElementById('inputSocialTiktok');
            var inIg = document.getElementById('inputSocialIg');

            if (inFb) inFb.value = fb;
            if (inTiktok) inTiktok.value = tiktok;
            if (inIg) inIg.value = ig;

            var btnFb = document.getElementById('btnGoFb');
            var btnTt = document.getElementById('btnGoTiktok');
            var btnIg = document.getElementById('btnGoIg');
            if (btnFb) { btnFb.style.display = fb ? 'inline-block' : 'none'; btnFb.href = fb; }
            if (btnTt) { btnTt.style.display = tiktok ? 'inline-block' : 'none'; btnTt.href = tiktok; }
            if (btnIg) { btnIg.style.display = ig ? 'inline-block' : 'none'; btnIg.href = ig; }

            if (modal) {
                modal.style.display = 'flex';
                setTimeout(function() {
                    if (platform === 'facebook' && inFb) inFb.focus();
                    else if (platform === 'tiktok' && inTiktok) inTiktok.focus();
                    else if (platform === 'instagram' && inIg) inIg.focus();
                }, 100);
            }
        };

        window.closeSocialLinksModal = function() {
            var modal = document.getElementById('socialLinksModal');
            if (modal) modal.style.display = 'none';
        };

        window.saveSocialLinksFromModal = function() {
            var svId = "";
            var fb = (document.getElementById('inputSocialFb')?.value || '').trim();
            var tiktok = (document.getElementById('inputSocialTiktok')?.value || '').trim();
            var ig = (document.getElementById('inputSocialIg')?.value || '').trim();

            if (fb && fb.indexOf('http') !== 0) fb = 'https://' + fb;
            if (tiktok && tiktok.indexOf('http') !== 0) tiktok = 'https://' + tiktok;
            if (ig && ig.indexOf('http') !== 0) ig = 'https://' + ig;

            localStorage.setItem('sf_social_link_facebook_' + svId, fb);
            localStorage.setItem('sf_social_link_tiktok_' + svId, tiktok);
            localStorage.setItem('sf_social_link_instagram_' + svId, ig);

            sfLoadSocialLinks();
            closeSocialLinksModal();
            if (typeof sfShowToast === 'function') sfShowToast('✅ Đã lưu thành công các liên kết Mạng Xã Hội!');
        };

        window.sfLoadSocialLinks = function() {
            try {
                var svId = "";
                var fb = localStorage.getItem('sf_social_link_facebook_' + svId);
                var tiktok = localStorage.getItem('sf_social_link_tiktok_' + svId);
                var ig = localStorage.getItem('sf_social_link_instagram_' + svId);

                var fbEl = document.getElementById('sfFbText');
                var ttEl = document.getElementById('sfTiktokText');
                var igEl = document.getElementById('sfIgText');

                if (fbEl) fbEl.textContent = fb ? 'Đã gắn link' : 'Gắn link FB';
                if (ttEl) ttEl.textContent = tiktok ? 'Đã gắn link' : 'Gắn link TikTok';
                if (igEl) igEl.textContent = ig ? 'Đã gắn link' : 'Gắn link Insta';
            } catch(e){}
        };

        document.addEventListener('DOMContentLoaded', function() {
            sfLoadHeroEdits();
            sfLoadSocialLinks();
            updateLiveClockDisplay();
            setInterval(updateLiveClockDisplay, 1000);

            try {
                var savedVid = localStorage.getItem('st_saved_tiktok_video_' + svId);
                if (savedVid) {
                    var maleContainer = document.getElementById('tiktokFrameContainerMale');
                    if (maleContainer) {
                        renderVideoInContainer(maleContainer, savedVid);
                    }
                }
            } catch(e){}


            // Kéo Rê Chuột Căn Chỉnh Vị Trí Ảnh Banner Trực Tiếp
            var img = document.getElementById('ltHeroBannerImg');
            if (img) {
                var savedPos = localStorage.getItem('st_saved_banner_pos_' + svId);
                if (savedPos) {
                    img.style.objectPosition = savedPos;
                }
                var savedFit = localStorage.getItem('st_saved_banner_fit_' + svId);
                if (savedFit) {
                    img.style.objectFit = savedFit;
                    var btn = document.getElementById('btnBannerFitToggle');
                    if (btn) {
                        btn.innerHTML = (savedFit === 'contain') ? '🖼️ Vừa Khung (Full)' : '🔍 Lấp Đầy (Cover)';
                    }
                }

                var isDragging = false;
                var startY = 0;
                var currentYPercent = 50;

                var currentPosStr = img.style.objectPosition || 'center center';
                var parts = currentPosStr.split(' ');
                if (parts.length >= 2 && parts[1].indexOf('%') !== -1) {
                    currentYPercent = parseFloat(parts[1]) || 50;
                } else if (parts[1] === 'top') {
                    currentYPercent = 0;
                } else if (parts[1] === 'bottom') {
                    currentYPercent = 100;
                }

                var startX = 0;
                var hasMoved = false;

                img.addEventListener('mousedown', function(e) {
                    isDragging = true;
                    hasMoved = false;
                    startY = e.clientY;
                    startX = e.clientX;
                    img.style.cursor = 'grabbing';
                });

                window.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;
                    var dx = Math.abs(e.clientX - startX);
                    var dy = Math.abs(e.clientY - startY);
                    if (dx > 5 || dy > 5) {
                        hasMoved = true;
                    }
                    var deltaY = e.clientY - startY;
                    var newY = Math.max(0, Math.min(100, currentYPercent - (deltaY * 0.35)));
                    img.style.objectPosition = 'center ' + newY.toFixed(1) + '%';
                });

                window.addEventListener('mouseup', function(e) {
                    if (isDragging) {
                        isDragging = false;
                        img.style.cursor = 'pointer';
                        if (!hasMoved) {
                            var fileInput = document.getElementById('sfBannerUploadMulti');
                            if (fileInput) fileInput.click();
                        } else {
                            var pos = img.style.objectPosition;
                            var parts = pos.split(' ');
                            if (parts.length >= 2 && parts[1].indexOf('%') !== -1) {
                                currentYPercent = parseFloat(parts[1]) || 50;
                            }
                            setBannerPosPreset(pos);
                        }
                    }
                });
            }
        });

        function openAvatarPreview(src, title, sub) {
            var modal = document.getElementById('avatarPreviewModal');
            var overlay = document.getElementById('avatarPreviewOverlay');
            var img = document.getElementById('avatarPreviewImg');
            var titleEl = document.getElementById('avatarPreviewTitle');
            var subEl = document.getElementById('avatarPreviewSub');

            if (img) img.src = src;
            if (titleEl) titleEl.innerText = title || 'Ảnh Đại Diện';
            if (subEl) subEl.innerText = sub || 'Góc Khoe Người Yêu 💕';

            if (modal && overlay) {
                modal.classList.add('active');
                overlay.classList.add('active');
            }
        }

        function closeAvatarPreviewModal() {
            var modal = document.getElementById('avatarPreviewModal');
            var overlay = document.getElementById('avatarPreviewOverlay');
            if (modal && overlay) {
                modal.classList.remove('active');
                overlay.classList.remove('active');
            }
        }

        // =========================================================
        // BANNER MANAGER MODAL JS HANDLERS
        // =========================================================
        var pendingModalFile = null;

        function openBannerManagerModal() {
            var modal = document.getElementById('bannerManagerModal');
            var overlay = document.getElementById('bannerManagerOverlay');
            if (modal && overlay) {
                modal.style.display = 'block';
                overlay.style.display = 'block';
            }
        }

        function closeBannerManagerModal() {
            var modal = document.getElementById('bannerManagerModal');
            var overlay = document.getElementById('bannerManagerOverlay');
            if (modal && overlay) {
                modal.style.display = 'none';
                overlay.style.display = 'none';
            }
        }

        function setModalBannerFit(fit) {
            var mainImg = document.getElementById('ltHeroBannerImg');
            var modalImg = document.getElementById('modalBannerImg');
            if (mainImg) mainImg.style.objectFit = fit;
            if (modalImg) modalImg.style.objectFit = fit;

            var btnContain = document.getElementById('modalBtnFitContain');
            var btnCover = document.getElementById('modalBtnFitCover');
            if (btnContain && btnCover) {
                if (fit === 'contain') {
                    btnContain.style.background = '#e0f2fe';
                    btnContain.style.borderColor = '#0284c7';
                    btnContain.style.color = '#0369a1';

                    btnCover.style.background = '#ffffff';
                    btnCover.style.borderColor = '#cbd5e1';
                    btnCover.style.color = '#475569';
                } else {
                    btnCover.style.background = '#e0f2fe';
                    btnCover.style.borderColor = '#0284c7';
                    btnCover.style.color = '#0369a1';

                    btnContain.style.background = '#ffffff';
                    btnContain.style.borderColor = '#cbd5e1';
                    btnContain.style.color = '#475569';
                }
            }
            try { localStorage.setItem('st_saved_banner_fit_' + svId, fit); } catch(e){}

            var formData = new FormData();
            formData.append('action', 'save_banner_fit');
            formData.append('is_ajax', '1');
            formData.append('banner_fit', fit);
            fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
        }

        function setModalBannerPos(pos) {
            setBannerPosPreset(pos);
            var modalImg = document.getElementById('modalBannerImg');
            if (modalImg) modalImg.style.objectPosition = pos;
        }

        function previewModalUploadedBanner(input) {
            if (input.files && input.files[0]) {
                var file = input.files[0];
                pendingModalFile = file;
                var url = URL.createObjectURL(file);
                var modalImg = document.getElementById('modalBannerImg');
                var modalBlur = document.getElementById('modalBannerBlurBg');
                var mainImg = document.getElementById('ltHeroBannerImg');
                var mainBlur = document.getElementById('ltHeroBannerBlurBg');
                var label = document.getElementById('modalFileNameLabel');

                if (modalImg) modalImg.src = url;
                if (modalBlur) modalBlur.src = url;
                if (mainImg) mainImg.src = url;
                if (mainBlur) mainBlur.src = url;
                if (label) label.innerText = '📁 Đã chọn tệp: ' + file.name;

                var reader = new FileReader();
                reader.onload = function(e) {
                    var base64El = document.getElementById('modalBannerBase64');
                    if (base64El) base64El.value = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function saveBannerManagerSettings() {
            var fileInput = document.getElementById('modalBannerFileInput');
            var form = document.getElementById('modalBannerUploadForm');
            var base64El = document.getElementById('modalBannerBase64');

            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                sfBannerAddFiles(fileInput);
                closeBannerManagerModal();
                return;
            }

            if (pendingModalFile || (base64El && base64El.value)) {
                if (form) form.submit();
                return;
            }

            closeBannerManagerModal();
            sfShowToast('✅ Đã lưu cài đặt Banner!');
        }

        