
window.cToggle = function() {
    var cbox = document.getElementById('cbox');
    if (!cbox) return;
    cbox.classList.toggle('open');
    if (cbox.classList.contains('open')) {
        var inp = document.getElementById('cinput');
        if (inp) setTimeout(function() { inp.focus(); }, 100);
    }
};
var cChatHistory = [];

async function cSend() {
    var input = document.getElementById('cinput');
    var txt = input.value.trim();
    if (!txt) return;
    input.value = '';
    var msgs = document.getElementById('cmsgs');
    msgs.innerHTML += '<div class="cmsg user"><div class="cbubble">' + escapeHtml(txt) + '</div></div>';
    msgs.scrollTop = msgs.scrollHeight;
    
    var botMsg = document.createElement('div');
    botMsg.className = 'cmsg bot';
    botMsg.innerHTML = '<div class="cbubble"><i><i class="fa-solid fa-spinner fa-spin"></i> Đang suy nghĩ...</i></div>';
    msgs.appendChild(botMsg);
    msgs.scrollTop = msgs.scrollHeight;

    var reply = '';
    var studentName = "";
    var sysPrompt = 'Bạn là Trợ lý AI học tập thông minh, thân thiện của Trường Cao đẳng Cà Mau đang trò chuyện với sinh viên ' + studentName + '. Hãy trả lời bằng tiếng Việt thân thiện, rõ ràng, có cảm xúc, định dạng Markdown đẹp, hỗ trợ học tập tốt nhất.';

    cChatHistory.push({role: 'user', content: txt});
    if (cChatHistory.length > 8) cChatHistory = cChatHistory.slice(-8);

    var apiMsgs = [{role: 'system', content: sysPrompt}].concat(cChatHistory);

    // 1. Thử gọi trực tiếp từ trình duyệt qua xKiro API (Mistral Large)
    try {
        var xRes = await fetch('https://api.xkiro.com/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48'
            },
            body: JSON.stringify({
                model: 'mistralai/mistral-large-2512',
                messages: apiMsgs,
                max_tokens: 1024,
                temperature: 0.7
            })
        });
        if (xRes.ok) {
            var xD = await xRes.json();
            if (xD.choices && xD.choices[0] && xD.choices[0].message) {
                reply = xD.choices[0].message.content;
            }
        }
    } catch(err) {}

    // 2. Dự phòng 1: Gọi mô hình Qwen 3.7 Flash Free từ trình duyệt
    if (!reply) {
        try {
            var qRes = await fetch('https://api.xkiro.com/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48'
                },
                body: JSON.stringify({
                    model: 'qwen/qwen3.7-flash:free',
                    messages: apiMsgs,
                    max_tokens: 1024,
                    temperature: 0.7
                })
            });
            if (qRes.ok) {
                var qD = await qRes.json();
                if (qD.choices && qD.choices[0] && qD.choices[0].message) {
                    reply = qD.choices[0].message.content;
                }
            }
        } catch(err) {}
    }

    // 3. Dự phòng 2: Gọi qua backend proxy server
    if (!reply) {
        try {
            var sRes = await fetch('/tkb/api/login.php?groq', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({messages: apiMsgs})
            });
            var sD = await sRes.json();
            if (sD.choices && sD.choices[0] && sD.choices[0].message) {
                reply = sD.choices[0].message.content;
            }
        } catch(err) {}
    }

    if (!reply) {
        reply = 'Xin chào ' + studentName + '! Mình luôn sẵn sàng đồng hành và giải đáp bài tập, thông tin học tập cùng bạn tại Trường Cao đẳng Cà Mau nè! 🌸✨';
    }

    // Lọc bỏ các tag nội bộ nếu có
    reply = reply.replace(/<!--[\s\S]*?-->/g, '').trim();

    cChatHistory.push({role: 'assistant', content: reply});
    botMsg.querySelector('.cbubble').innerHTML = formatBotMsg(reply);
    msgs.scrollTop = msgs.scrollHeight;
}

function formatBotMsg(t) {
    var safe = escapeHtml(t);
    safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    safe = safe.replace(/\*(.*?)\*/g, '<em>$1</em>');
    safe = safe.replace(/`([^`]+)`/g, '<code style="background:rgba(0,0,0,0.1);padding:2px 5px;border-radius:4px;font-family:monospace;">$1</code>');
    safe = safe.replace(/\n/g, '<br>');
    return safe;
}

function escapeHtml(t) { return t.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"); }

function submitQuickBanner() {
    var input = document.getElementById('quickBannerInput');
    if (!input.files || !input.files[0]) return;
    
    var file = input.files[0];
    var btn = document.querySelector('.mc-btn-change-banner');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang nén & tải...';

    var reader = new FileReader();
    reader.onload = function(e) {
        var img = new Image();
        img.onerror = function() {
            document.getElementById('quickBannerForm').submit();
        };
        img.onload = function() {
            var canvas = document.createElement('canvas');
            var maxW = 1920;
            var width = img.width;
            var height = img.height;

            if (width > maxW) {
                height = Math.round(height * (maxW / width));
                width = maxW;
            }

            canvas.width = width;
            canvas.height = height;
            var ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, 0, 0, width, height);

            var base64 = canvas.toDataURL('image/jpeg', 0.92);
            try {
                localStorage.setItem('student_banner_cache', base64);
                var fullImg = document.getElementById('mcBannerFullImg');
                var blurBg = document.getElementById('mcBannerBlurBg');
                if (fullImg) fullImg.src = base64;
                if (blurBg) blurBg.src = base64;
            } catch(err) {}
            var base64El = document.getElementById('quickBannerBase64');
            if (base64El) base64El.value = base64;
            document.getElementById('quickBannerForm').submit();
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

document.addEventListener('DOMContentLoaded', function() {
    var cached = localStorage.getItem('student_banner_cache');
    var fullImg = document.getElementById('mcBannerFullImg');
    var blurBg = document.getElementById('mcBannerBlurBg');
    if (cached && fullImg) {
        fullImg.src = cached;
        if (blurBg) blurBg.src = cached;
    }
});

if (window.location.search.indexOf('upload=success') !== -1) {
    if (history.replaceState) {
        history.replaceState(null, null, window.location.pathname);
    }
    var toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#1e293b;color:#4ade80;border:2px solid #22c55e;padding:14px 24px;border-radius:8px;font-family:sans-serif;font-weight:bold;font-size:15px;box-shadow:0 10px 25px rgba(0,0,0,0.5);z-index:99999;transition:all 0.4s ease;opacity:0;transform:translateY(-20px);';
    toast.innerHTML = '<i class="fa-solid fa-circle-check"></i> 🎉 Cập nhật Banner trang chủ mới thành công!';
    document.body.appendChild(toast);
    setTimeout(function() { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; }, 50);
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 400);
    }, 3500);
}
function updateLhLiveClock() {
    const now = new Date();
    const hrs = String(now.getHours()).padStart(2, '0');
    const mins = String(now.getMinutes()).padStart(2, '0');
    const secs = String(now.getSeconds()).padStart(2, '0');
    
    const clockEl = document.getElementById('ltLiveClock');
    if (clockEl) {
        clockEl.innerText = `${hrs}:${mins}:${secs}`;
    }

    const days = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    const dayName = days[now.getDay()];
    const dateStr = String(now.getDate()).padStart(2, '0');
    const monthStr = String(now.getMonth() + 1).padStart(2, '0');

    const dateEl = document.getElementById('ltLiveDate');
    if (dateEl) {
        dateEl.innerText = `${dayName}, ${dateStr}/${monthStr}`;
    }
}
setInterval(updateLhLiveClock, 1000);
updateLhLiveClock();
