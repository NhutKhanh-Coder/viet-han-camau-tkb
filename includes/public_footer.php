    <!-- FOOTER SECTION -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand-col">
                    <a href="/tkb/index.php" class="footer-brand">
                        <img src="/tkb/assets/img/logo_vkc.jpg" alt="Logo" class="footer-logo">
                        <div class="footer-brand-text">
                            <span class="f-brand-title">CAO ĐẲNG NGHỀ VIỆT NAM - HÀN QUỐC CÀ MAU</span>
                            <span class="f-brand-subtitle">KIẾN TẠO TƯƠNG LAI</span>
                        </div>
                    </a>
                    <p class="footer-desc">
                        Trường Cao đẳng Nghề Việt Nam - Hàn Quốc Cà Mau là cơ sở đào tạo nghề nghiệp chất lượng cao hàng đầu, cung cấp nguồn nhân lực kỹ thuật xuất sắc, có kỹ năng tay nghề đạt chuẩn quốc tế.
                    </p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" aria-label="Globe"><i class="fas fa-globe"></i></a>
                    </div>
                </div>
                <div class="footer-links-col">
                    <h3 class="footer-title">LIÊN KẾT NHANH</h3>
                    <ul class="footer-links">
                        <li><a href="/tkb/index.php">Trang chủ</a></li>
                        <li><a href="/tkb/gioi_thieu.php">Giới thiệu</a></li>
                        <li><a href="/tkb/dao_tao.php">Đào tạo</a></li>
                        <li><a href="/tkb/tuyen_sinh.php">Tuyển sinh</a></li>
                        <li><a href="/tkb/lien_he.php">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="footer-links-col">
                    <h3 class="footer-title">DỊCH VỤ</h3>
                    <ul class="footer-links">
                        <li><a href="/tkb/login.php">Cổng thông tin sinh viên</a></li>
                        <li><a href="/tkb/tai_lieu.php">Thư viện tài liệu</a></li>
                        <li><a href="/tkb/su_kien.php">Tin tức &amp; Sự kiện</a></li>
                        <li><a href="/tkb/thu_vien.php">Thư viện số</a></li>
                    </ul>
                </div>
                <div class="footer-contact-col">
                    <h3 class="footer-title">THÔNG TIN LIÊN HỆ</h3>
                    <p class="footer-text"><i class="fas fa-map-marker-alt"></i> Số 08, đường Mậu Thân, Khóm 6, Phường 9, TP. Cà Mau</p>
                    <p class="footer-text"><i class="fas fa-phone-alt"></i> 0290 3838 234 - 0290 3598 836</p>
                    <p class="footer-text"><i class="fas fa-envelope"></i> tuyensinh@vkc.edu.vn</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Cao đẳng Nghề Việt Nam - Hàn Quốc Cà Mau. Tất cả các quyền được bảo lưu. Thiết kế bởi Lê Nhựt Khánh.</p>
                <a href="#top" class="back-to-top"><i class="fas fa-chevron-up"></i></a>
            </div>
        </div>
    </footer>

<!-- ================================================================
     CHATBOT AI - CAO ĐẲNG NGHỀ VIỆT NAM - HÀN QUỐC CÀ MAU
     Dán đoạn này vào trước </body> của trang login.php
     ================================================================ -->

<style>
/* ---- FAB ---- */
.cfab{
  position:fixed;bottom:26px;right:26px;z-index:9999;
  width:62px;height:62px;border-radius:50%;border:1.5px solid rgba(255, 255, 255, 0.35);cursor:pointer;
  background:#d91b43;
  box-shadow:0 8px 25px rgba(217, 27, 67, 0.45);
  display:flex;align-items:center;justify-content:center;
  transition:transform .25s,box-shadow .25s;
}
.cfab:hover{transform:scale(1.1) rotate(5deg);box-shadow:0 12px 32px rgba(217, 27, 67, 0.6)}
.cfab svg{width:28px;height:28px;fill:#fff}
.cfab-ping{
  position:absolute;top:2px;right:2px;width:14px;height:14px;
  border-radius:50%;background:#22c55e;border:2px solid #fff;
  animation:cfping 2s ease-in-out infinite;
}
@keyframes cfping{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.4);opacity:.6}}

/* ---- WINDOW ---- */
.cbox{
  position:fixed;bottom:100px;right:26px;width:375px;
  background:rgba(255,255,255,0.78);
  backdrop-filter:blur(30px);
  -webkit-backdrop-filter:blur(30px);
  border-radius:24px;overflow:hidden;
  border:1px solid rgba(255,255,255,0.5);
  box-shadow:0 16px 48px rgba(15,23,42,0.12);
  display:none;flex-direction:column;z-index:9998;
  max-height:580px;
  animation:cslide .25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes cslide{from{opacity:0;transform:translateY(18px) scale(.97)}to{opacity:1;transform:none}}
.cbox.open{display:flex}

/* ---- HEADER ---- */
.chdr{
  display:flex;align-items:center;gap:11px;
  padding:16px 20px;flex-shrink:0;
  background:#d91b43;
}
.chdr-logo{
  width:38px;height:38px;border-radius:50%;overflow:hidden;
  border:2px solid rgba(255,255,255,0.3);flex-shrink:0;
  background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;
}
.chdr-logo img{width:100%;height:100%;object-fit:cover}
.chdr-logo svg{width:20px;height:20px;fill:#fff}
.chdr-txt{flex:1}
.chdr-txt strong{display:block;color:#fff;font-size:13px;font-weight:700;line-height:1.3}
.chdr-txt span{color:rgba(255,255,255,0.7);font-size:11px}
.chdr-dot{width:8px;height:8px;background:#4ade80;border-radius:50%;flex-shrink:0;animation:cfping 2s infinite}
.cclose{
  background:rgba(255,255,255,0.12);border:none;color:#fff;
  width:28px;height:28px;border-radius:50%;cursor:pointer;
  font-size:15px;display:flex;align-items:center;justify-content:center;
  transition:background .15s;flex-shrink:0;
}
.cclose:hover{background:rgba(255,255,255,0.25)}

/* ---- MESSAGES ---- */
.cmsgs{
  flex:1;overflow-y:auto;padding:16px;
  display:flex;flex-direction:column;gap:12px;
  min-height:200px;max-height:320px;
  background:rgba(244, 246, 252, 0.6);
}
.cmsgs::-webkit-scrollbar{width:3px}
.cmsgs::-webkit-scrollbar-thumb{background:rgba(217, 27, 67, 0.25);border-radius:2px}
.cmsg{display:flex;flex-direction:column;max-width:86%}
.cmsg.user{align-self:flex-end;align-items:flex-end}
.cmsg.bot{align-self:flex-start;align-items:flex-start}
.cbubble{padding:10px 14px;border-radius:16px;font-size:13px;line-height:1.55;word-break:break-word}
.cmsg.user .cbubble{background:#d91b43;color:#fff;border-bottom-right-radius:4px;box-shadow:0 4px 12px rgba(217, 27, 67, 0.15);}
.cmsg.bot .cbubble{background:rgba(255,255,255,0.9);color:#0f172a;border-bottom-left-radius:4px;border:1px solid rgba(255,255,255,0.6);box-shadow:0 4px 12px rgba(15,23,42,0.03)}
.ctime{font-size:10px;color:#94a3b8;margin-top:3px;padding:0 3px}

/* ---- TYPING ---- */
.ctyping-wrap{align-self:flex-start}
.ctyping{display:flex;align-items:center;gap:5px;padding:10px 14px;background:rgba(255,255,255,0.9);border-radius:14px;border-bottom-left-radius:4px;border:1px solid rgba(255,255,255,0.6);width:fit-content}
.ctyping span{width:6px;height:6px;background:#d91b43;border-radius:50%;animation:cdot 1.1s infinite}
.ctyping span:nth-child(2){animation-delay:.18s}
.ctyping span:nth-child(3){animation-delay:.36s}
@keyframes cdot{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}

/* ---- QUICK BTNS ---- */
.cquick{
  padding:10px 16px 14px;display:flex;gap:6px;
  flex-wrap:wrap;flex-shrink:0;border-top:1px solid rgba(255,255,255,0.4);
  background:rgba(244, 246, 252, 0.6);
}
.cqbtn{
  font-size:11px;padding:5px 12px;border-radius:20px;
  border:1px solid rgba(217, 27, 67, 0.25);background:rgba(217, 27, 67, 0.04);
  color:#d91b43;cursor:pointer;font-family:inherit;transition:all .18s;white-space:nowrap;
  font-weight:600;
}
.cqbtn:hover{background:#d91b43;border-color:#d91b43;color:#fff;transform:translateY(-1px);}

/* ---- STATUS ---- */
.cstat{font-size:10px;text-align:center;padding:2px 12px;min-height:15px;flex-shrink:0}
.cstat.ok{color:#22c55e}.cstat.err{color:#f87171}

/* ---- INPUT ---- */
.cinrow{
  display:flex;gap:8px;padding:12px 16px;flex-shrink:0;
  border-top:1px solid rgba(255,255,255,0.4);background:rgba(255,255,255,0.5);
}
.cinput{
  flex:1;background:rgba(255,255,255,0.6);border:1.5px solid rgba(255,255,255,0.5);
  border-radius:12px;padding:10px 14px;color:#0f172a;font-size:13px;
  outline:none;resize:none;max-height:80px;font-family:inherit;
  transition:border-color .18s, background-color .18s;line-height:1.4;
}
.cinput::placeholder{color:#94a3b8}
.cinput:focus{border-color:rgba(217, 27, 67, 0.4);background:#fff;}
.csend{
  width:40px;height:40px;border-radius:12px;background:#d91b43;
  border:none;display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:background .15s,transform .1s;flex-shrink:0;align-self:flex-end;
}
.csend:hover{transform:scale(1.05);}
.csend:disabled{background:#cbd5e1;opacity:.4;cursor:not-allowed;transform:none;}
.csend svg{width:16px;height:16px;fill:#fff}

/* ---- RESPONSIVE ---- */
@media(max-width:480px){
  .cbox{width:calc(100vw - 20px);right:10px;bottom:86px;max-height:75vh}
  .cfab{bottom:16px;right:16px;width:54px;height:54px}
}
</style>

<!-- FAB Button -->
<button class="cfab" onclick="cToggle()" title="Chat với AI tư vấn">
  <span class="cfab-ping"></span>
  <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
</button>

<!-- Chat Window -->
<div class="cbox" id="cbox">

  <!-- Header -->
  <div class="chdr">
    <div class="chdr-logo">
      <!-- Nếu có logo trường, thay bằng: <img src="/path/to/logo.png"> -->
      <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
    </div>
    <div class="chdr-txt">
      <strong>Tư vấn AI - Cao đẳng Nghề VN-HQ</strong>
      <span>Cà Mau &bull; Groq / Llama 3.3</span>
    </div>
    <span class="chdr-dot"></span>
    <button class="cclose" onclick="cToggle()">✕</button>
  </div>

  <!-- Status bar -->
  <div class="cstat" id="cstat"></div>

  <!-- Messages -->
  <div class="cmsgs" id="cmsgs">
    <div class="cmsg bot">
      <div class="cbubble">
        👋 Xin chào! Tôi là trợ lý AI của <strong>Cao đẳng Nghề Việt Nam – Hàn Quốc Cà Mau</strong>.<br><br>
        Tôi có thể giúp bạn về:<br>
        📚 Thông tin tuyển sinh &amp; ngành học<br>
        💰 Học phí &amp; học bổng<br>
        🔑 Hỗ trợ đăng nhập hệ thống<br>
        📅 Thời khóa biểu &amp; lịch học
      </div>
      <div class="ctime">Vừa xong</div>
    </div>
  </div>

  <!-- Quick suggestions -->
  <div class="cquick" id="cquick">
    <button class="cqbtn" onclick="cQuick(this)">🎓 Ngành học 2025</button>
    <button class="cqbtn" onclick="cQuick(this)">💰 Học phí bao nhiêu?</button>
    <button class="cqbtn" onclick="cQuick(this)">🔑 Quên mật khẩu</button>
    <button class="cqbtn" onclick="cQuick(this)">📋 Điều kiện tuyển sinh</button>
    <button class="cqbtn" onclick="cQuick(this)">🏫 Ký túc xá</button>
    <button class="cqbtn" onclick="cQuick(this)">📞 Liên hệ tư vấn</button>
  </div>

  <!-- Input -->
  <div class="cinrow">
    <div class="cinput-container">
      <button class="voice-btn" id="cvoiceBtn" onclick="toggleVoiceInput()" title="Nói câu hỏi của bạn">
        <i class="fas fa-microphone"></i>
      </button>
      <textarea class="cinput" id="cinput" placeholder="Nhập câu hỏi... (Enter gửi)" rows="1"
        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();cSend()}"
        oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
      <button class="csend" id="csend" onclick="cSend()">
        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
      </button>
    </div>
  </div>
</div>

<script>
(function(){
  var GROQ_KEY = 'gsk_' + 'N9EgrjZEXGXLOB8fTf6tWGdyb3FY7HsmeaKFbwGCuFjeB1z6UwXl';
  var MODEL    = 'llama-3.3-70b-versatile';

  /* ---- FAQ nội bộ - trả lời ngay không cần API ---- */
  var FAQ = [
    {
      keys: ['mật khẩu','mat khau','quên','quen','forgot','password','đổi mật khẩu'],
      ans:  '🔑 Mật khẩu mặc định của sinh viên là <strong>ngày sinh</strong> theo định dạng <strong>ddmmyyyy</strong>.<br>Ví dụ sinh ngày 05/03/2005 → mật khẩu: <strong>05032005</strong><br><br>Nếu vẫn không đăng nhập được, hãy sử dụng liên kết <a href="/tkb/forgot_password.php" style="color:#d91b43;font-weight:bold;">Quên mật khẩu</a> tại màn hình đăng nhập để tự khôi phục hoặc liên hệ phòng Đào tạo.'
    },
    {
      keys: ['tài khoản','tai khoan','username','mã sv','ma sv','đăng nhập','dang nhap','login'],
      ans:  '👤 Tài khoản đăng nhập là <strong>Mã sinh viên</strong> của bạn (ghi trên thẻ SV hoặc giấy nhập học).<br>Mật khẩu mặc định: ngày sinh <strong>ddmmyyyy</strong>.<br><br>Nếu chưa có tài khoản, hãy liên hệ phòng Đào tạo.'
    },
    {
      keys: ['học phí','hoc phi','tiền học','tien hoc','phí','phi'],
      ans:  '💰 Học phí tham khảo năm 2025:<br>• Hệ Cao đẳng: ~6–9 triệu/năm<br>• Hệ Trung cấp: ~4–6 triệu/năm<br><br>Học phí cụ thể theo từng ngành. Tải file <em>Bảng Học Phí Các Ngành</em> tại trang chủ để xem chi tiết.'
    },
    {
      keys: ['tuyển sinh','tuyen sinh','xét tuyển','xet tuyen','đăng ký','dang ky','điều kiện','dieu kien'],
      ans:  '🎓 Điều kiện tuyển sinh 2025:<br>• Tốt nghiệp THPT hoặc tương đương (Cao đẳng)<br>• Tốt nghiệp THCS trở lên (Trung cấp)<br><br>Hồ sơ gồm: Bằng/Giấy chứng nhận TN, CMND/CCCD, ảnh 3×4.<br>Nộp trực tiếp tại trường hoặc đăng ký online tại trang Tuyển sinh.'
    },
    {
      keys: ['ngành','nganh','khoa','chuyên ngành','chuyen nganh','học gì','hoc gi'],
      ans:  '📚 Các ngành đào tạo chính:<br>• Công nghệ Ô tô<br>• Điện – Điện tử<br>• Hàn<br>• Công nghệ Thông tin<br>• Kế toán<br>• Quản trị Kinh doanh<br>• Chăm sóc Sắc đẹp<br><br>Xem đầy đủ tại mục <strong>Đào tạo</strong> trên website.'
    },
    {
      keys: ['ký túc xá','ky tuc xa','ktx','phòng ở','phong o','nội trú','noi tru'],
      ans:  '🏫 Trường có ký túc xá cho sinh viên với chi phí ưu đãi.<br>Liên hệ phòng Công tác Học sinh – Sinh viên để đăng ký KTX.<br>Mẫu đơn KTX tải tại trang chủ phần <em>Tài liệu</em>.'
    },
    {
      keys: ['học bổng','hoc bong','miễn giảm','mien giam','hỗ trợ','ho tro'],
      ans:  '🏆 Trường có các chính sách hỗ trợ:<br>• Học bổng khuyến học cho SV giỏi<br>• Miễn giảm học phí theo diện chính sách<br>• Hỗ trợ vay vốn sinh viên (Ngân hàng CSXH)<br><br>Liên hệ phòng Đào tạo để biết thêm.'
    },
    {
      keys: ['liên hệ','lien he','điện thoại','dien thoai','sdt','hotline','địa chỉ','dia chi','email'],
      ans:  '📞 Liên hệ Trường Cao đẳng Nghề VN – HQ Cà Mau:<br>• <strong>Địa chỉ:</strong> Cà Mau<br>• <strong>Website:</strong> vietnan.ct.ws<br>• Phòng Đào tạo: liên hệ trực tiếp tại trường<br><br>Hoặc inbox fanpage Facebook của trường để được tư vấn nhanh nhất!'
    },
    {
      keys: ['thời khóa biểu','thoi khoa bieu','tkb','lịch học','lich hoc','lịch thi','lich thi'],
      ans:  '📅 Thời khóa biểu và lịch thi được cập nhật trên hệ thống sau khi đăng nhập.<br>Vào mục <strong>Thời khóa biểu</strong> trong trang cá nhân để xem chi tiết.<br><br>Nếu chưa có tài khoản, liên hệ phòng Đào tạo.'
    }
  ];

  var SYS = `Bạn là trợ lý AI tư vấn của Cao Đẳng Nghề Việt Nam - Hàn Quốc Cà Mau (vietnan.ct.ws).
Hỗ trợ sinh viên và phụ huynh về: tuyển sinh, ngành học, học phí, đăng nhập hệ thống, thời khóa biểu, ký túc xá, học bổng.
Phong cách: thân thiện, ngắn gọn, dùng tiếng Việt. Dùng emoji phù hợp. Nếu không biết, hướng dẫn liên hệ phòng Đào tạo.`;

  var hist = [], busy = false;

  /* Tìm FAQ */
  function findFAQ(text) {
    var t = text.toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .replace(/đ/g,'d');
    for (var i = 0; i < FAQ.length; i++) {
      for (var j = 0; j < FAQ[i].keys.length; j++) {
        var k = FAQ[i].keys[j].normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d');
        if (t.indexOf(k) !== -1) return FAQ[i].ans;
      }
    }
    return null;
  }

  function cTime(){
    return new Date().toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
  }

  function appendMsg(role, html) {
    var c = document.getElementById('cmsgs');
    var d = document.createElement('div');
    d.className = 'cmsg ' + role;
    d.innerHTML = '<div class="cbubble">' + html + '</div><div class="ctime">' + cTime() + '</div>';
    c.appendChild(d);
    c.scrollTop = c.scrollHeight;
  }

  function showTyping() {
    var c = document.getElementById('cmsgs');
    var d = document.createElement('div');
    d.className = 'cmsg bot ctyping-wrap'; d.id = 'ctyp';
    d.innerHTML = '<div class="ctyping"><span></span><span></span><span></span></div>';
    c.appendChild(d); c.scrollTop = c.scrollHeight;
  }

  function removeTyping() { var e=document.getElementById('ctyp'); if(e) e.remove(); }

  function setStat(msg, cls) {
    var el = document.getElementById('cstat');
    el.innerHTML = msg; el.className = 'cstat ' + (cls||'');
    if (msg) setTimeout(function(){ el.innerHTML=''; el.className='cstat'; }, 3500);
  }

  function safeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
            .replace(/\n/g,'<br>');
  }

  window.cToggle = function() {
    var b = document.getElementById('cbox');
    b.classList.toggle('open');
    if (b.classList.contains('open')) {
      document.getElementById('cinput').focus();
      document.getElementById('cmsgs').scrollTop = 9999;
    }
  };

  window.cOpen = function() {
    var b = document.getElementById('cbox');
    if (!b.classList.contains('open')) { b.classList.add('open'); document.getElementById('cinput').focus(); }
  };

  window.cQuick = function(btn) {
    if (busy) return;
    var q = btn.textContent.replace(/^[^\w\u00C0-\u024F]+/,'').trim();
    /* Ẩn quick btns */
    var qk = document.getElementById('cquick');
    if (qk) qk.style.display = 'none';
    doSend(q);
  };

  window.cSend = function() {
    var inp = document.getElementById('cinput');
    var txt = inp.value.trim();
    if (!txt || busy) return;
    inp.value = ''; inp.style.height = 'auto';
    var qk = document.getElementById('cquick');
    if (qk) qk.style.display = 'none';
    doSend(txt);
  };

  function doSend(txt) {
    busy = true;
    document.getElementById('csend').disabled = true;
    appendMsg('user', safeHtml(txt));

    /* Kiểm tra FAQ trước */
    var faqAns = findFAQ(txt);
    if (faqAns) {
      setTimeout(function() {
        showTyping();
        setTimeout(function() {
          removeTyping();
          appendMsg('bot', faqAns);
          hist.push({role:'user',content:txt});
          hist.push({role:'assistant',content:faqAns});
          busy = false;
          document.getElementById('csend').disabled = false;
          document.getElementById('cinput').focus();
        }, 700);
      }, 100);
      return;
    }

    /* Gọi Groq API */
    showTyping();
    var msgs = [{role:'system',content:SYS}];
    hist.slice(-10).forEach(function(m){ msgs.push(m); });
    msgs.push({role:'user',content:txt});

    fetch('https://api.groq.com/openai/v1/chat/completions',{
      method:'POST',
      headers:{'Content-Type':'application/json','Authorization':'Bearer '+GROQ_KEY},
      body:JSON.stringify({model:MODEL,messages:msgs,max_tokens:800,temperature:0.7})
    })
    .then(function(r){return r.json();})
    .then(function(d){
      removeTyping();
      var reply;
      if(d.error){
        reply = '⚠️ Lỗi: '+(d.error.message||'Groq API lỗi');
        setStat('API lỗi','err');
      } else {
        reply = (d.choices&&d.choices[0]&&d.choices[0].message&&d.choices[0].message.content)||'Xin lỗi, tôi chưa hiểu câu hỏi.';
        hist.push({role:'user',content:txt});
        hist.push({role:'assistant',content:reply});
        setStat('','ok');
      }
      appendMsg('bot', safeHtml(reply));
    })
    .catch(function(e){
      removeTyping();
      appendMsg('bot','⚠️ Không kết nối được. Vui lòng kiểm tra mạng.');
      setStat('Mất kết nối','err');
    })
    .finally(function(){
      busy = false;
      document.getElementById('csend').disabled = false;
      document.getElementById('cinput').focus();
    });
  }

  /* Tự mở chatbot khi bấm nút Đăng nhập / Đăng ký */
  document.addEventListener('click', function(e){
    var el = e.target.closest('a,button,[role="button"]');
    if (!el) return;
    var txt = (el.innerText||el.textContent||el.getAttribute('href')||'').toLowerCase();
    var kws = ['đăng nhập','dang nhap','login','đăng ký','dang ky','register','sign in','sign up'];
    for (var i=0;i<kws.length;i++){
      if (txt.indexOf(kws[i])!==-1){
        setTimeout(function(){
          cOpen();
          var inp = document.getElementById('cinput');
          if(inp){ inp.value=''; inp.focus(); }
          /* Gợi ý tự động */
          setTimeout(function(){
            appendMsg('bot','💡 Bạn cần hỗ trợ đăng nhập? Mật khẩu mặc định là <strong>ngày sinh ddmmyyyy</strong>.<br>Ví dụ: sinh 05/03/2005 → <strong>05032005</strong><br><br>Tài khoản là <strong>Mã sinh viên</strong> của bạn.');
          }, 400);
        }, 300);
        break;
      }
    }
  });

  /* ---- Voice Input using Web Speech API ---- */
  var recognition;
  var isRecording = false;
  
  if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.lang = 'vi-VN';
    recognition.interimResults = false;
    
    recognition.onstart = function() {
      isRecording = true;
      var btn = document.getElementById('cvoiceBtn');
      if (btn) btn.classList.add('recording');
      setStat('Đang nghe giọng nói của bạn...','ok');
    };
    
    recognition.onresult = function(event) {
      var txt = event.results[0][0].transcript;
      var inp = document.getElementById('cinput');
      if (inp) {
        inp.value = txt;
        inp.dispatchEvent(new Event('input'));
      }
      setStat('Đã nhận diện xong','ok');
    };
    
    recognition.onerror = function(event) {
      console.error(event);
      setStat('Không nhận diện được giọng nói','err');
      stopRecording();
    };
    
    recognition.onend = function() {
      stopRecording();
    };
  } else {
    // Hide microphone button if not supported
    document.addEventListener('DOMContentLoaded', function() {
      var btn = document.getElementById('cvoiceBtn');
      if (btn) btn.style.display = 'none';
    });
  }
  
  function stopRecording() {
    isRecording = false;
    var btn = document.getElementById('cvoiceBtn');
    if (btn) btn.classList.remove('recording');
  }
  
  window.toggleVoiceInput = function() {
    if (!recognition) {
      alert("Trình duyệt của bạn không hỗ trợ nhận diện giọng nói!");
      return;
    }
    if (isRecording) {
      recognition.stop();
    } else {
      recognition.start();
    }
  };

})();
</script>
<!-- ================================================================ -->
</body>
</html>