
        (function() {
            // ═══════════ BANNER GALLERY ═══════════
            var sfBannerItems = [];
            var sfBannerIdx = 0;
            var sfBannerAutoTimer = null;

            // Default banner and DB server banners from PHP
            var sfDefaultBanner = "";
            var sfServerBannerItems = "";

            function sfBannerLoad() {
                var serverItems = Array.isArray(sfServerBannerItems) ? sfServerBannerItems : [];
                var localItems = [];
                try {
                    var svId = "";
                    var saved = localStorage.getItem('sf_banner_gallery_' + svId);
                    if (saved) {
                        var parsed = JSON.parse(saved);
                        if (Array.isArray(parsed)) localItems = parsed;
                    }
                } catch(e) {}

                var combined = serverItems.concat(localItems);
                var unique = [];
                combined.forEach(function(item) {
                    if (!item || typeof item !== 'string') return;
                    var u = item.trim();
                    if (u === '' || u.indexOf('blob:') === 0) return;
                    var lower = u.toLowerCase();
                    if (lower === 'banner.jpg' || lower === 'default.jpg' || lower === 'default.png' || lower === 'sample.jpg') return;
                    if (lower.indexOf('banner.jpg') !== -1 && lower.indexOf('banner_') === -1) return;
                    if (lower === '/tkb/assets/img/banners/' || lower === '/tkb/assets/img/banners/banner.jpg') return;
                    if (unique.indexOf(u) === -1) {
                        unique.push(u);
                    }
                });

                sfBannerItems = unique;
                if (sfBannerItems.length === 0) {
                    try {
                        var svId = "";
                        localStorage.removeItem('sf_banner_gallery_' + svId);
                        localStorage.removeItem('st_user_banner_url_' + svId);
                    } catch(e){}
                }

                sfBannerIdx = sfBannerItems.length > 0 ? sfBannerItems.length - 1 : 0;
                sfBannerShow();
                sfBannerResetAuto();
            }

            function sfBannerSave() {
                try {
                    var clean = sfBannerItems.filter(function(item) {
                        if (!item || typeof item !== 'string' || item.indexOf('blob:') === 0) return false;
                        var lower = item.toLowerCase().trim();
                        if (lower === 'banner.jpg' || lower === 'default.jpg' || lower === 'default.png') return false;
                        if (lower.indexOf('banner.jpg') !== -1 && lower.indexOf('banner_') === -1) return false;
                        return true;
                    });
                    if (clean.length > 0) {
                        localStorage.setItem('st_user_banner_url_' + "", clean[clean.length - 1]);
                        localStorage.setItem('st_user_banner_ts_' + "", Math.floor(Date.now() / 1000).toString());
                    } else {
                        localStorage.removeItem('st_user_banner_url_' + "");
                    }
                    localStorage.setItem('sf_banner_gallery_' + "", JSON.stringify(clean));

                    // Sync to MySQL Database so banner list NEVER dies on logout!
                    var formData = new FormData();
                    formData.append('action', 'save_banner_gallery');
                    formData.append('gallery_json', JSON.stringify(clean));
                    fetch('/tkb/api/upload_video.php', { method: 'POST', body: formData }).catch(function(){});
                } catch(e) {}
            }

            function sfBannerShow() {
                var img = document.getElementById('ltHeroBannerImg');
                var placeholder = document.getElementById('ltHeroBannerPlaceholder');
                var blur = document.getElementById('ltHeroBannerBlurBg');
                
                var counters = document.querySelectorAll('#sfBannerCounter');
                var prevBtns = document.querySelectorAll('.sf-banner-prev-btn');
                var nextBtns = document.querySelectorAll('.sf-banner-next-btn');
                var removeBtns = document.querySelectorAll('.sf-banner-remove-btn');
                var dotsEls = document.querySelectorAll('#sfBannerDots');

                var totalCount = (sfBannerItems && Array.isArray(sfBannerItems)) ? sfBannerItems.length : 0;
                counters.forEach(function(c) {
                    c.textContent = totalCount > 0 ? ((sfBannerIdx + 1) + ' / ' + totalCount) : '0 / 0';
                });

                if (totalCount === 0 || !sfBannerItems[0]) {
                    if (img) img.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'block';
                    removeBtns.forEach(function(b){ b.style.display = 'none'; });
                    prevBtns.forEach(function(b){ b.style.display = 'none'; });
                    nextBtns.forEach(function(b){ b.style.display = 'none'; });
                    dotsEls.forEach(function(d){ d.innerHTML = ''; });
                    return;
                }

                if (sfBannerIdx < 0) sfBannerIdx = totalCount - 1;
                if (sfBannerIdx >= totalCount) sfBannerIdx = 0;

                removeBtns.forEach(function(b){ b.style.display = 'inline-flex'; });
                if (totalCount > 1) {
                    prevBtns.forEach(function(b){ b.style.display = 'flex'; });
                    nextBtns.forEach(function(b){ b.style.display = 'flex'; });
                } else {
                    prevBtns.forEach(function(b){ b.style.display = 'none'; });
                    nextBtns.forEach(function(b){ b.style.display = 'none'; });
                }

                var src = sfBannerItems[sfBannerIdx];
                if (img) {
                    img.onerror = function() {
                        this.style.display = 'none';
                        if (placeholder) placeholder.style.display = 'block';
                        if (sfBannerItems && sfBannerItems.length > 0) {
                            sfBannerItems.splice(sfBannerIdx, 1);
                            sfBannerIdx = Math.max(0, sfBannerItems.length - 1);
                            sfBannerSave();
                            sfBannerShow();
                        }
                    };
                    if (src && src.trim() !== '') {
                        img.style.display = 'block';
                        if (placeholder) placeholder.style.display = 'none';
                        img.style.opacity = '0'; 
                        img.style.transition = 'opacity 0.35s ease'; 
                        setTimeout(function() { img.src = src; img.style.opacity = '1'; }, 50); 
                    } else {
                        img.style.display = 'none';
                        if (placeholder) placeholder.style.display = 'block';
                    }
                }
                if (blur && src) blur.src = src;

                sfBannerRenderDots();
            }

            function sfBannerRenderDots() {
                var dotsEl = document.getElementById('sfBannerDots');
                if (!dotsEl) return;
                if (sfBannerItems.length <= 1) { dotsEl.innerHTML = ''; return; }
                var maxDots = Math.min(sfBannerItems.length, 8);
                var html = '';
                for (var i = 0; i < maxDots; i++) {
                    var isActive = (i === sfBannerIdx);
                    html += '<span onclick="sfBannerGoTo(' + i + ')" style="width: ' + (isActive ? '20px' : '8px') + '; height: 8px; border-radius: 4px; background: ' + (isActive ? 'rgba(236,72,153,0.9)' : 'rgba(255,255,255,0.5)') + '; cursor: pointer; transition: all 0.3s; box-shadow: ' + (isActive ? '0 0 8px rgba(236,72,153,0.5)' : 'none') + ';"></span>';
                }
                if (sfBannerItems.length > maxDots) {
                    html += '<span style="color: rgba(255,255,255,0.6); font-size: 10px; font-weight: bold;">+' + (sfBannerItems.length - maxDots) + '</span>';
                }
                dotsEl.innerHTML = html;
            }

            window.sfBannerGoTo = function(idx) {
                sfBannerIdx = idx;
                sfBannerShow();
                sfBannerResetAuto();
            };

            window.sfBannerPrev = function() {
                sfBannerIdx--;
                sfBannerShow();
                sfBannerResetAuto();
            };

            window.sfBannerNext = function() {
                sfBannerIdx++;
                sfBannerShow();
                sfBannerResetAuto();
            };

            window.sfBannerAddFiles = function(input) {
                if (!input.files || input.files.length === 0) return;
                var files = Array.from(input.files);

                sfShowToast('⏳ Đang tải ' + files.length + ' ảnh banner...');

                files.forEach(function(file) {
                    var formData = new FormData();
                    formData.append('banner_file', file);

                    fetch('/tkb/api/upload_banner.php', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d && d.success) {
                            if (Array.isArray(d.gallery) && d.gallery.length > 0) {
                                sfBannerItems = d.gallery.slice();
                            } else if (d.banner_url && sfBannerItems.indexOf(d.banner_url) === -1) {
                                sfBannerItems.push(d.banner_url);
                            }
                            sfBannerIdx = sfBannerItems.length - 1;
                            sfBannerSave();
                            sfBannerShow();
                            sfBannerResetAuto();
                            sfShowToast('🖼️ Đã thêm ảnh banner!');
                        } else {
                            sfShowToast('⚠️ ' + (d ? (d.message || d.error) : 'Không thể tải ảnh banner!'));
                        }
                    })
                    .catch(function(err) {
                        console.warn('Banner upload fallback to DataURL:', err);
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            if (sfBannerItems.indexOf(e.target.result) === -1) {
                                sfBannerItems.push(e.target.result);
                            }
                            sfBannerIdx = sfBannerItems.length - 1;
                            sfBannerSave();
                            sfBannerShow();
                            sfBannerResetAuto();
                            sfShowToast('🖼️ Đã thêm ảnh banner!');
                        };
                        reader.readAsDataURL(file);
                    });
                });
                input.value = '';
            };

            window.sfBannerRemoveCurrent = function() {
                if (!sfBannerItems || sfBannerItems.length === 0) {
                    sfShowToast('⚠️ Chưa có ảnh banner nào trong bộ sưu tập!');
                    return;
                }
                sfBannerItems.splice(sfBannerIdx, 1);
                if (sfBannerIdx >= sfBannerItems.length) sfBannerIdx = sfBannerItems.length - 1;
                if (sfBannerIdx < 0) sfBannerIdx = 0;
                sfBannerSave();
                sfBannerShow();
                sfShowToast('🗑️ Đã xóa ảnh banner!');
            };

            function sfBannerResetAuto() {
                if (sfBannerAutoTimer) clearInterval(sfBannerAutoTimer);
                if (sfBannerItems.length > 1) {
                    sfBannerAutoTimer = setInterval(function() {
                        sfBannerIdx++;
                        sfBannerShow();
                    }, 6000);
                }
            }
            function saveVideoFileToIDB(key, fileBlob, callback) {
                try {
                    var req = indexedDB.open('TkbMediaDB', 1);
                    req.onupgradeneeded = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('videos')) {
                            db.createObjectStore('videos');
                        }
                    };
                    req.onsuccess = function(e) {
                        var db = e.target.result;
                        var tx = db.transaction('videos', 'readwrite');
                        var store = tx.objectStore('videos');
                        var pReq = store.put(fileBlob, key);
                        pReq.onsuccess = function() { if (callback) callback(true); };
                        pReq.onerror = function() { if (callback) callback(false); };
                    };
                    req.onerror = function() { if (callback) callback(false); };
                } catch(err) { if (callback) callback(false); }
            }

            function loadVideosFromIDB(svId, callback) {
                if (!svId) {
                    if (callback) callback([]);
                    return;
                }
                try {
                    var req = indexedDB.open('TkbMediaDB', 1);
                    req.onupgradeneeded = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('videos')) {
                            db.createObjectStore('videos');
                        }
                    };
                    req.onsuccess = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('videos')) {
                            if (callback) callback([]);
                            return;
                        }
                        var tx = db.transaction('videos', 'readonly');
                        var store = tx.objectStore('videos');
                        var cursorReq = store.openCursor();
                        var items = [];
                        var prefix = 'idb_vid_' + svId + '_';
                        cursorReq.onsuccess = function(ev) {
                            var cursor = ev.target.result;
                            if (cursor) {
                                if (typeof cursor.key === 'string' && cursor.key.indexOf(prefix) === 0 && cursor.value) {
                                    try {
                                        var blobUrl = URL.createObjectURL(cursor.value);
                                        items.push(blobUrl);
                                    } catch(err){}
                                }
                                cursor.continue();
                            } else {
                                if (callback) callback(items);
                            }
                        };
                        cursorReq.onerror = function() { if (callback) callback([]); };
                    };
                    req.onerror = function() { if (callback) callback([]); };
                } catch(err) { if (callback) callback([]); }
            }

            function clearVideosFromIDB(svId) {
                if (!svId) return;
                try {
                    var req = indexedDB.open('TkbMediaDB', 1);
                    req.onsuccess = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('videos')) return;
                        var tx = db.transaction('videos', 'readwrite');
                        var store = tx.objectStore('videos');
                        var cursorReq = store.openCursor();
                        var prefix = 'idb_vid_' + svId + '_';
                        cursorReq.onsuccess = function(ev) {
                            var cursor = ev.target.result;
                            if (cursor) {
                                if (typeof cursor.key === 'string' && cursor.key.indexOf(prefix) === 0) {
                                    cursor.delete();
                                }
                                cursor.continue();
                            }
                        };
                    };
                } catch(err) {}
            }

            // ═══════════ VIDEO GALLERY ═══════════
            var sfVideoItems = [];
            var sfVideoIdx = 0;

            var sfDefaultVideo = "";
            var sfServerVideoItems = "";

            function sfVideoLoad() {
                var serverItems = Array.isArray(sfServerVideoItems) ? sfServerVideoItems.slice() : [];
                var localItems = [];
                var svId = "";

                // Clear legacy un-namespaced keys to avoid sharing across accounts
                try {
                    localStorage.removeItem('st_saved_tiktok_video');
                    localStorage.removeItem('sf_video_gallery');
                    localStorage.removeItem('st_video_items');
                } catch(e){}

                try {
                    var saved = localStorage.getItem('sf_video_gallery_' + svId);
                    if (saved) {
                        var parsed = JSON.parse(saved);
                        if (Array.isArray(parsed)) localItems = parsed;
                    }
                    var singleSaved = localStorage.getItem('st_saved_tiktok_video_' + svId);
                    if (singleSaved && localItems.indexOf(singleSaved) === -1) {
                        localItems.push(singleSaved);
                    }
                } catch(e) {}

                loadVideosFromIDB(svId, function(idbItems) {
                    var combined = [];
                    if (serverItems.length > 0) {
                        combined = serverItems.concat(localItems);
                    } else if (localItems.length > 0) {
                        combined = localItems;
                    } else if (idbItems && idbItems.length > 0 && Array.isArray(sfServerVideoItems) && sfServerVideoItems.length > 0) {
                        combined = idbItems;
                    }

                    var clean = combined.filter(function(item) {
                        if (!item || typeof item !== 'string') return false;
                        var u = item.trim();
                        if (u === '') return false;
                        var lower = u.toLowerCase();
                        if (lower === 'video.mp4' || lower === 'default.mp4' || lower === 'sample.mp4' || lower === 'intro_video.mp4') return false;
                        return true;
                    });

                    var unique = [];
                    clean.forEach(function(item) {
                        if (unique.indexOf(item) === -1) {
                            unique.push(item);
                        }
                    });

                    sfVideoItems = unique;
                    if (sfVideoItems.length === 0) {
                        try {
                            localStorage.removeItem('sf_video_gallery_' + svId);
                            localStorage.removeItem('st_saved_tiktok_video_' + svId);
                            clearVideosFromIDB(svId);
                        } catch(e){}
                    }

                    sfVideoIdx = sfVideoItems.length > 0 ? sfVideoItems.length - 1 : 0;
                    sfVideoShow();
                });
            }

            function sfVideoSave() {
                try {
                    var svId = "";
                    var cleanItems = sfVideoItems.filter(function(item) {
                        return item && typeof item === 'string' &&
                               item.indexOf('blob:') !== 0 &&
                               item.trim() !== '';
                    });
                    if (cleanItems.length > 0) {
                        localStorage.setItem('sf_video_gallery_' + svId, JSON.stringify(cleanItems));
                        localStorage.setItem('st_saved_tiktok_video_' + svId, cleanItems[cleanItems.length - 1]);
                    } else {
                        localStorage.removeItem('sf_video_gallery_' + svId);
                        localStorage.removeItem('st_saved_tiktok_video_' + svId);
                        clearVideosFromIDB(svId);
                    }

                    // Always sync to MySQL database in both dashboard.php and api/upload_video.php
                    var formData = new FormData();
                    formData.append('action', 'save_video_gallery');
                    formData.append('gallery_json', JSON.stringify(cleanItems));
                    fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData, credentials: 'same-origin' }).catch(function(){});
                    fetch('/tkb/api/upload_video.php', { method: 'POST', body: formData, credentials: 'same-origin' }).catch(function(){});
                } catch(e) {
                    console.warn('sfVideoSave warning:', e);
                }
            }


            function sfVideoShow() {
                var container = document.getElementById('tiktokFrameContainer');
                var counters = document.querySelectorAll('#sfVideoCounter');
                var hCountFemale = document.getElementById('sfVideoHeaderCount');
                var hCountMale = document.getElementById('sfVideoHeaderCountMale');
                var prevBtns = document.querySelectorAll('.sf-video-prev-btn');
                var nextBtns = document.querySelectorAll('.sf-video-next-btn');
                var removeBtns = document.querySelectorAll('.sf-video-remove-btn');

                var totalCount = (sfVideoItems && Array.isArray(sfVideoItems)) ? sfVideoItems.length : 0;
                if (hCountFemale) hCountFemale.innerHTML = '<i class="fa-solid fa-layer-group" style="font-size: 9.5px;"></i> ' + totalCount + ' Video';
                if (hCountMale) hCountMale.innerHTML = '<i class="fa-solid fa-layer-group" style="font-size: 9.5px;"></i> ' + totalCount + ' Video';

                counters.forEach(function(c) {
                    c.textContent = totalCount > 0 ? ((sfVideoIdx + 1) + ' / ' + totalCount) : '0 / 0';
                });

                if (!sfVideoItems || totalCount === 0) {
                    removeBtns.forEach(function(b){ b.style.display = 'none'; });
                    prevBtns.forEach(function(b){ b.style.display = 'none'; });
                    nextBtns.forEach(function(b){ b.style.display = 'none'; });

                    if (container) {
                        var isFem = "";
                        var grad = isFem ? 'linear-gradient(135deg, #ec4899, #8b5cf6)' : 'linear-gradient(135deg, #a855f7, #7c3aed)';
                        var iconColor = isFem ? '#ec4899' : '#a855f7';
                        var uploadInputId = isFem ? 'sfVideoUploadMulti' : 'sfVideoUploadMultiMale';
                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.85); font-family: \'Outfit\', sans-serif;">' +
                            '<div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, rgba(236,72,153,0.2), rgba(139,92,246,0.2)); border: 1.5px solid rgba(236,72,153,0.4); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 22px; color: ' + iconColor + ';">' +
                                '<i class="fa-solid fa-film"></i>' +
                            '</div>' +
                            '<div style="font-weight: 800; font-size: 13.5px; margin-bottom: 4px; color: #ffffff;">Chưa có video trong bộ sưu tập</div>' +
                            '<div style="font-size: 10.5px; color: rgba(255,255,255,0.6); max-width: 220px; margin: 0 auto 12px; line-height: 1.4;">Dán link Video MP4 / YouTube hoặc bấm Tải Lên để thêm video!</div>' +
                            '<button onclick="var inp = document.getElementById(\'' + uploadInputId + '\') || document.getElementById(\'sfVideoUploadMulti\') || document.getElementById(\'sfVideoUploadMultiMale\'); if(inp) inp.click();" style="background: ' + grad + '; border: none; color: #fff; padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(236,72,153,0.3);">' +
                                '<i class="fa-solid fa-plus"></i> Thêm Video Ngay' +
                            '</button>' +
                        '</div>';
                    }
                    return;
                }

                removeBtns.forEach(function(b){ b.style.display = 'inline-flex'; });
                if (totalCount > 1) {
                    prevBtns.forEach(function(b){ b.style.display = 'inline-flex'; });
                    nextBtns.forEach(function(b){ b.style.display = 'inline-flex'; });
                } else {
                    prevBtns.forEach(function(b){ b.style.display = 'none'; });
                    nextBtns.forEach(function(b){ b.style.display = 'none'; });
                }

                if (sfVideoIdx < 0) sfVideoIdx = totalCount - 1;
                if (sfVideoIdx >= totalCount) sfVideoIdx = 0;

                var src = sfVideoItems[sfVideoIdx];
                if (container) {
                    sfRenderVideoItem(container, src);
                }
            }

            function sfRenderVideoItem(container, src) {
                if (!container || !src) return;
                var fitMode = localStorage.getItem('st_saved_video_fit_' + "") || 'cover';
                var isSingle = (!sfVideoItems || sfVideoItems.length <= 1);
                var loopAttr = isSingle ? ' loop ' : '';

                // YouTube & YouTube Shorts Embed
                var ytMatch = src.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/);
                if (ytMatch && ytMatch[1]) {
                    var ytId = ytMatch[1];
                    container.innerHTML = '<iframe src="https://www.youtube.com/embed/' + ytId + '?autoplay=1&loop=1&playlist=' + ytId + '&rel=0&enablejsapi=1" style="width:100%; height:100%; max-height:100%; border:none; overflow:hidden; border-radius:12px;" scrolling="no" allowfullscreen referrerpolicy="no-referrer" allow="autoplay; encrypted-media; picture-in-picture"></iframe>';
                    return;
                }

                // Native Video Player for ALL direct videos & uploaded files (MP4, WebM, MOV, DataURL, etc.)
                container.innerHTML = '<video id="tiktokPlayerVideo" src="' + src + '" autoplay ' + loopAttr + ' playsinline controls style="width:100%; height:100%; max-height:100%; object-fit:' + fitMode + '; border-radius:12px; display:block; background:#000;"></video>';

                setTimeout(function() {
                    var vid = document.getElementById('tiktokPlayerVideo');
                    if (vid) {
                        vid.muted = false;
                        vid.volume = 1.0;

                        var playPromise = vid.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(function(e) {
                                // Fallback to muted autoplay if browser restricts unmuted autoplay on initial load
                                vid.muted = true;
                                vid.play().catch(function(){});
                            });
                        }

                        // Continuous Infinite Playback Loop (Switch next video or replay single video)
                        vid.onended = function() {
                            if (sfVideoItems && sfVideoItems.length > 1) {
                                sfVideoNext();
                            } else {
                                vid.currentTime = 0;
                                vid.play().catch(function(){});
                            }
                        };
                    }
                }, 50);
            }

            // Auto unmute audio on first user click if muted by browser
            document.addEventListener('click', function() {
                var vid = document.getElementById('tiktokPlayerVideo');
                if (vid && vid.muted) {
                    vid.muted = false;
                    vid.volume = 1.0;
                }
            }, { once: true });

            window.sfVideoPrev = function() {
                if (!sfVideoItems || sfVideoItems.length === 0) return;
                sfVideoIdx--;
                if (sfVideoIdx < 0) sfVideoIdx = sfVideoItems.length - 1;
                sfVideoShow();
            };

            window.sfVideoNext = function() {
                if (!sfVideoItems || sfVideoItems.length === 0) return;
                sfVideoIdx++;
                if (sfVideoIdx >= sfVideoItems.length) sfVideoIdx = 0;
                sfVideoShow();
            };

            window.sfVideoAddUrl = function() {
                var input = document.getElementById('inputTikTokUrl');
                if (!input) return;
                var url = input.value.trim();
                if (!url) return;

                sfVideoItems.push(url);
                sfVideoIdx = sfVideoItems.length - 1;
                sfVideoSave();
                sfVideoShow();
                input.value = '';
                sfShowToast('🎬 Đã thêm video vào bộ sưu tập!');

                // Also save to server
                var formData = new FormData();
                formData.append('action', 'save_tiktok_video');
                formData.append('is_ajax', '1');
                formData.append('video_url', url);
                fetch('/tkb/student/dashboard.php', { method: 'POST', body: formData });
            };

            window.sfVideoAddFiles = function(input) {
                if (!input.files || input.files.length === 0) return;
                var files = Array.from(input.files);
                var svId = "";

                files.forEach(function(file) {
                    sfShowToast('⏳ Đang xử lý video "' + file.name + '"...');

                    // 1. Instantly create Blob URL & Save File to IndexedDB (100% Permanent in Browser storage!)
                    var blobUrl = URL.createObjectURL(file);
                    var idbKey = 'idb_vid_' + svId + '_' + Date.now() + '_' + file.name;
                    saveVideoFileToIDB(idbKey, file);

                    if (sfVideoItems.indexOf(blobUrl) === -1) {
                        sfVideoItems.push(blobUrl);
                    }
                    sfVideoIdx = sfVideoItems.length - 1;
                    sfVideoShow();
                    sfShowToast('🎬 Đã lưu video thành công!');

                    // 2. Try server upload if supported by host
                    var formData = new FormData();
                    formData.append('video_file', file);

                    fetch('/tkb/api/upload_video.php', { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(function(r) {
                        return r.text().then(function(text) {
                            try { return JSON.parse(text); }
                            catch(e) { return null; }
                        });
                    })
                    .then(function(d) {
                        if (d && d.success && d.video_url) {
                            var idx = sfVideoItems.indexOf(blobUrl);
                            if (idx !== -1) {
                                sfVideoItems[idx] = d.video_url;
                            } else if (sfVideoItems.indexOf(d.video_url) === -1) {
                                sfVideoItems.push(d.video_url);
                            }
                            sfVideoSave();
                            sfVideoShow();
                        }
                    })
                    .catch(function(err) {});
                });
                input.value = '';
            };







            window.sfVideoRemoveCurrent = function() {
                if (!sfVideoItems || sfVideoItems.length === 0) {
                    sfShowToast('⚠️ Chưa có video nào trong bộ sưu tập!');
                    return;
                }
                var svId = "";
                sfVideoItems.splice(sfVideoIdx, 1);
                if (sfVideoIdx >= sfVideoItems.length) sfVideoIdx = sfVideoItems.length - 1;
                if (sfVideoIdx < 0) sfVideoIdx = 0;
                if (sfVideoItems.length === 0) {
                    clearVideosFromIDB(svId);
                    try {
                        localStorage.removeItem('sf_video_gallery_' + svId);
                        localStorage.removeItem('st_saved_tiktok_video_' + svId);
                    } catch(e){}
                }
                sfVideoSave();
                sfVideoShow();
                sfShowToast('🗑️ Đã xóa video khỏi bộ sưu tập!');
            };

            // ═══════════ VIDEO MANAGER MODAL JS ═══════════
            window.openVideoManagerModal = function() {
                var modal = document.getElementById('videoManagerModal');
                var overlay = document.getElementById('videoManagerOverlay');
                var listEl = document.getElementById('videoManagerList');

                if (!modal || !overlay || !listEl) return;

                if (!sfVideoItems || sfVideoItems.length === 0) {
                    listEl.innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px; font-weight:600;">Chưa có video nào trong danh sách. Hãy thêm video mới!</div>';
                } else {
                    var html = '';
                    sfVideoItems.forEach(function(url, idx) {
                        var isCurrent = (idx === sfVideoIdx);
                        var displayTitle = url.length > 50 ? url.substring(0, 50) + '...' : url;
                        if (url.indexOf('/uploads/videos/') !== -1) {
                            var filename = url.split('/').pop();
                            displayTitle = '📹 ' + filename;
                        } else if (url.match(/youtube|youtu\.be/)) {
                            displayTitle = '🔴 YouTube: ' + displayTitle;
                        }

                        html += '<div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:' + (isCurrent ? '#fdf2f8' : '#f8fafc') + '; border:1.5px solid ' + (isCurrent ? '#ec4899' : '#e2e8f0') + '; border-radius:12px; gap:10px;">' +
                            '<div style="flex:1; min-width:0;">' +
                                '<div style="font-size:12px; font-weight:700; color:' + (isCurrent ? '#ec4899' : '#1e293b') + '; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + (idx + 1) + '. ' + displayTitle + '</div>' +
                                (isCurrent ? '<div style="font-size:10px; color:#ec4899; font-weight:800; margin-top:2px;">▶ Đang phát</div>' : '') +
                            '</div>' +
                            '<div style="display:flex; gap:6px; flex-shrink:0;">' +
                                '<button type="button" onclick="sfVideoSelectIndex(' + idx + ')" style="background:' + (isCurrent ? '#ec4899' : '#8b5cf6') + '; color:#fff; border:none; padding:5px 12px; border-radius:8px; font-size:11px; font-weight:700; cursor:pointer;">▶ Xem</button>' +
                                '<button type="button" onclick="sfVideoRemoveIndex(' + idx + ')" style="background:#fff1f2; color:#e11d48; border:1px solid #fecdd3; padding:5px 10px; border-radius:8px; font-size:11px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-trash-can"></i> Xóa</button>' +
                            '</div>' +
                        '</div>';
                    });
                    listEl.innerHTML = html;
                }

                modal.style.display = 'block';
                overlay.style.display = 'block';
            };

            window.closeVideoManagerModal = function() {
                var modal = document.getElementById('videoManagerModal');
                var overlay = document.getElementById('videoManagerOverlay');
                if (modal && overlay) {
                    modal.style.display = 'none';
                    overlay.style.display = 'none';
                }
            };

            window.sfVideoSelectIndex = function(idx) {
                sfVideoIdx = idx;
                sfVideoShow();
                openVideoManagerModal();
                sfShowToast('▶ Đã phát Video ' + (idx + 1));
            };

            window.sfVideoRemoveIndex = function(idx) {
                if (idx >= 0 && idx < sfVideoItems.length) {
                    var svId = "";
                    sfVideoItems.splice(idx, 1);
                    if (sfVideoIdx >= sfVideoItems.length) sfVideoIdx = sfVideoItems.length - 1;
                    if (sfVideoIdx < 0) sfVideoIdx = 0;
                    if (sfVideoItems.length === 0) {
                        clearVideosFromIDB(svId);
                        try {
                            localStorage.removeItem('sf_video_gallery_' + svId);
                            localStorage.removeItem('st_saved_tiktok_video_' + svId);
                        } catch(e){}
                    }
                    sfVideoSave();
                    sfVideoShow();
                    openVideoManagerModal();
                    sfShowToast('🗑️ Đã xóa video khỏi danh sách!');
                }
            };


            // ═══════════ TOAST NOTIFICATION ═══════════
            window.sfShowToast = function(msg) {
                var toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#fff;color:#1e293b;border:1.5px solid #e2e8f0;padding:12px 20px;border-radius:14px;font-family:"Outfit",sans-serif;font-weight:700;font-size:13px;box-shadow:0 8px 32px rgba(0,0,0,0.12);z-index:999999;transition:all 0.35s cubic-bezier(0.34,1.56,0.64,1);opacity:0;transform:translateY(-16px) scale(0.95);display:flex;align-items:center;gap:8px;';
                toast.innerHTML = msg;
                document.body.appendChild(toast);
                requestAnimationFrame(function() {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0) scale(1)';
                });
                setTimeout(function() {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-16px) scale(0.95)';
                    setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
                }, 2500);
            };

            // ═══════════ INIT ON DOM READY ═══════════
            function sfInitAll() {
                sfBannerLoad();
                sfVideoLoad();
                sfBannerResetAuto();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', sfInitAll);
            } else {
                sfInitAll();
            }
        })();
        