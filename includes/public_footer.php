<style>
/* =========================================================
   LED 7-COLOR RAINBOW ANIMATION - BẢN QUYỀN LÊ NHỰT KHÁNH
   ========================================================= */
.copyright-led-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 10px 24px;
    background: rgba(15, 23, 42, 0.92);
    border-radius: 50px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    margin: 12px 0;
    backdrop-filter: blur(10px);
}
.led-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ff0055;
    box-shadow: 0 0 10px #ff0055, 0 0 20px #ff0055;
    animation: ledPulse 2s linear infinite;
    flex-shrink: 0;
}
.copyright-led-text {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 14px;
    letter-spacing: 0.8px;
    background: linear-gradient(90deg, 
        #ff0055, #ff5000, #ffcc00, #00ff66, #00ccff, #7000ff, #ff00cc, #ff0055);
    background-size: 400% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: rainbowGlow 4s linear infinite;
}
@keyframes rainbowGlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
@keyframes ledPulse {
    0% { background: #ff0055; box-shadow: 0 0 8px #ff0055, 0 0 16px #ff0055; }
    14% { background: #ff5000; box-shadow: 0 0 8px #ff5000, 0 0 16px #ff5000; }
    28% { background: #ffcc00; box-shadow: 0 0 8px #ffcc00, 0 0 16px #ffcc00; }
    42% { background: #00ff66; box-shadow: 0 0 8px #00ff66, 0 0 16px #00ff66; }
    57% { background: #00ccff; box-shadow: 0 0 8px #00ccff, 0 0 16px #00ccff; }
    71% { background: #7000ff; box-shadow: 0 0 8px #7000ff, 0 0 16px #7000ff; }
    85% { background: #ff00cc; box-shadow: 0 0 8px #ff00cc, 0 0 16px #ff00cc; }
    100% { background: #ff0055; box-shadow: 0 0 8px #ff0055, 0 0 16px #ff0055; }
}
</style>

    <!-- FOOTER SECTION -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand-col">
                    <a href="/tkb/index.php" class="footer-brand">
                        <img src="/tkb/assets/img/logo_vkc.jpg" alt="Logo" class="footer-logo">
                        <div class="footer-brand-text">
                            <span class="f-brand-title">TRƯỜNG CAO ĐẲNG CÀ MAU</span>
                            <span class="f-brand-subtitle">CỔNG THÔNG TIN HỌC TẬP & IDE</span>
                        </div>
                    </a>
                    <p class="footer-desc">
                        Hệ thống đào tạo nghề nghiệp và môi trường thực hành lập trình trực quan dành cho sinh viên và giảng viên.
                    </p>
                </div>
                <div class="footer-links-col">
                    <h3 class="footer-title">LIÊN KẾT NHANH</h3>
                    <ul class="footer-links">
                        <li><a href="/tkb/index.php">Trang chủ</a></li>
                        <li><a href="/tkb/student/dashboard.php">Cổng sinh viên</a></li>
                        <li><a href="/tkb/teacher/dashboard.php">Cổng giảng viên</a></li>
                        <li><a href="/tkb/student/code_ide.php">Thực hành IDE</a></li>
                    </ul>
                </div>
                <div class="footer-contact-col">
                    <h3 class="footer-title">THÔNG TIN LIÊN HỆ</h3>
                    <p class="footer-text"><i class="fas fa-map-marker-alt"></i> Số 08, đường Mậu Thân, Khóm 6, Phường 9, TP. Cà Mau</p>
                </div>
            </div>
            <div class="footer-bottom">
                <a href="#top" class="back-to-top"><i class="fas fa-chevron-up"></i></a>
            </div>
        </div>
    </footer>

<!-- ================================================================
     CHATBOT AI - TRƯỜNG CAO ĐẲNG CÀ MAU
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
      <strong>Tư vấn AI - Trường Cao đẳng Cà Mau</strong>
      <span>Cà Mau &bull; Gemini AI &bull; CSDL Trực Tuyến</span>
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
        👋 Xin chào! Tôi là trợ lý AI của <strong>Trường Cao đẳng Cà Mau</strong>.<br><br>
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

<?php
$_f_db = getDB();
$_total_sv = 0;
$_total_gv = 0;
$_total_mon = 0;
$_sv_names = [];

if ($_f_db && !$_f_db->connect_error) {
    $res_sv = @$_f_db->query("SELECT COUNT(*) as cnt FROM students");
    if ($res_sv) $_total_sv = (int)($res_sv->fetch_assoc()['cnt'] ?? 0);
    
    $res_gv = @$_f_db->query("SELECT COUNT(*) as cnt FROM giang_vien");
    if ($res_gv) $_total_gv = (int)($res_gv->fetch_assoc()['cnt'] ?? 0);
    
    $res_mon = @$_f_db->query("SELECT COUNT(*) as cnt FROM mon_hoc");
    if ($res_mon) $_total_mon = (int)($res_mon->fetch_assoc()['cnt'] ?? 0);

    $res_sv_list = @$_f_db->query("SELECT ho_ten FROM students ORDER BY id ASC LIMIT 10");
    if ($res_sv_list) {
        while ($r = $res_sv_list->fetch_assoc()) {
            $_sv_names[] = $r['ho_ten'];
        }
    }
}
if (empty($_total_sv)) $_total_sv = 2;
if (empty($_sv_names)) $_sv_names = ['Lê Nhựt Khánh', 'Vũ Nhật Tường Vi'];
$_sv_str = implode(" và ", $_sv_names);
?>
<script>
(function(){
  var AI_KEY = 'sk-xt-be5b4b10bf19ae39b6797fd77a983b74ab9c7ce7cd277a48';
  var MODEL  = 'deepseek/deepseek-chat-v3.1';

  /* ---- FAQ nội bộ - trả lời ngay không cần API ---- */
  var FAQ = [
    {
      keys: ['mấy sinh viên','bao nhiêu sinh viên','số sinh viên','so sinh vien','số lượng sinh viên','so luong sinh vien','mấy sv','bao nhieu sv','mấy học sinh','sinh viên','sinh vien','sv trong csdl'],
      ans:  'Trong cơ sở dữ liệu hệ thống hiện tại đang có chính xác <strong><?= $_total_sv ?> sinh viên</strong> (Gồm: <strong><?= htmlspecialchars($_sv_str, ENT_QUOTES, "UTF-8") ?></strong>).'
    },
    {
      keys: ['mấy giảng viên','bao nhiêu giảng viên','số giảng viên','so giang vien','thầy cô','giáo viên'],
      ans:  '👨‍🏫 Trong cơ sở dữ liệu hệ thống hiện đang quản lý <strong><?= $_total_gv ?> giảng viên</strong> giảng dạy.'
    },
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
      ans:  '💰 Học phí tham khảo (tùy theo ngành):<br>• Hệ Cao đẳng: Liên hệ phòng Đào tạo để biết mức học phí chính xác.<br>• Hệ Trung cấp: Được miễn học phí 100% đối với học sinh tốt nghiệp THCS.<br><br>Chi tiết xem tại mục Tuyển sinh trên website.'
    },
    {
      keys: ['tuyển sinh','tuyen sinh','xét tuyển','xet tuyen','đăng ký','dang ky','điều kiện','dieu kien'],
      ans:  '🎓 Trường xét tuyển học bạ 2 trình độ:<br>• **Cao đẳng** (2.5 năm): Tốt nghiệp THPT hoặc tương đương.<br>• **Trung cấp** (2 năm): Tốt nghiệp THCS trở lên.<br><br>Đăng ký trực tiếp tại trường hoặc qua Zalo tư vấn.'
    },
    {
      keys: ['ngành','nganh','khoa','chuyên ngành','chuyen nganh','học gì','hoc gi'],
      ans:  '📚 Các ngành đào tạo trọng điểm của trường:<br>• Chế biến & bảo quản thủy sản<br>• Công nghệ thông tin (Ứng dụng phần mềm)<br>• Công nghệ Ô tô<br>• Cơ điện tử<br>• Điện công nghiệp<br>• Kỹ thuật máy lạnh & điều hòa không khí<br><br>Xem đầy đủ tại mục <strong>Đào tạo</strong> trên website.'
    },
    {
      keys: ['ký túc xá','ky tuc xa','ktx','phòng ở','phong o','nội trú','noi tru'],
      ans:  '🏫 Trường có khu Ký túc xá khang trang dành cho sinh viên với chi phí ưu đãi. Liên hệ phòng Công tác HSSV để đăng ký.'
    },
    {
      keys: ['học bổng','hoc bong','miễn giảm','mien giam','hỗ trợ','ho tro'],
      ans:  '🏆 Các chính sách hỗ trợ:<br>• Miễn 100% học phí Trung cấp cho HS tốt nghiệp THCS.<br>• Học bổng khuyến khích học tập cho SV khá/giỏi.<br>• Hỗ trợ vay vốn Ngân hàng CSXH.<br>• Miễn giảm theo đối tượng chính sách.'
    },
    {
      keys: ['liên hệ','lien he','điện thoại','dien thoai','sdt','hotline','địa chỉ','dia chi','email'],
      ans:  '📞 Liên hệ Trường Cao đẳng Cà Mau:<br>• <strong>Địa chỉ:</strong> Số 08, đường Mậu Thân, Khóm 6, Phường 9, TP. Cà Mau<br>• <strong>Website:</strong> camauvkc.edu.vn<br>• Inbox Zalo/Fanpage của trường để được tư vấn nhanh nhất!'
    },
    {
      keys: ['thời khóa biểu','thoi khoa bieu','tkb','lịch học','lich hoc','lịch thi','lich thi'],
      ans:  '📅 Thời khóa biểu và lịch học được cập nhật trực tiếp trên hệ thống sau khi bạn đăng nhập.<br>Vào mục <strong>Thời khóa biểu</strong> trong trang cá nhân sinh viên để xem.'
    }
  ];

  var SYS = `Bạn là trợ lý AI tư vấn chính thức của Trường Cao Đẳng Cà Mau (camauvkc.edu.vn).
QUY TẮC TỐI THƯỢNG:
1. Bạn CHỈ ĐƯỢC PHÉP trả lời các câu hỏi nằm trong phạm vi thông tin của trường (camauvkc.edu.vn), thông tin tuyển sinh, ngành học, học phí, cơ sở dữ liệu của trang web này.
2. NẾU người dùng hỏi các chủ đề ngoài lề, BẠN PHẢI TỪ CHỐI một cách lịch sự.
3. KHÔNG bịa đặt thông tin. Dựa vào thông tin sau đây:
- Các ngành đào tạo: Chế biến & bảo quản thủy sản, Công nghệ thông tin (Ứng dụng phần mềm), Công nghệ Ô tô, Cơ điện tử, Điện công nghiệp, Kỹ thuật máy lạnh & điều hòa không khí.
- Trình độ: Cao đẳng (2.5 năm, yêu cầu bằng THPT), Trung cấp (2 năm, yêu cầu bằng THCS).
- Địa chỉ: Số 08, đường Mậu Thân, Khóm 6, Phường 9, TP. Cà Mau.

Dữ liệu thời gian thực từ Cơ sở dữ liệu hệ thống nhà trường:
- Số lượng sinh viên trong cơ sở dữ liệu hiện tại: chính xác <?= $_total_sv ?> sinh viên (Gồm: <?= htmlspecialchars($_sv_str, ENT_QUOTES, "UTF-8") ?>).
- Số lượng giảng viên: <?= $_total_gv ?> giảng viên.
- Số lượng môn học/học phần: <?= $_total_mon ?> môn học.

QUY TẮC BẮT BUỘC KHI ĐƯỢC HỎI VỀ SỐ LƯỢNG SINH VIÊN:
- Nếu ai hỏi "Trường có mấy sinh viên?", "Có bao nhiêu sinh viên?", "Trong CSDL có bao nhiêu sinh viên?", bạn BẮT BUỘC phải trả lời đúng nguyên văn:
"Trong cơ sở dữ liệu hệ thống hiện tại đang có chính xác <?= $_total_sv ?> sinh viên (Gồm: <?= htmlspecialchars($_sv_str, ENT_QUOTES, "UTF-8") ?>)."
- Tuyệt đối KHÔNG ĐƯỢC trả lời chung chung hoặc bảo liên hệ Phòng Đào Tạo!
- Trả lời bằng tiếng Việt thân thiện, rõ ràng.`;

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

    fetch('https://api.xkiro.com/v1/chat/completions',{
      method:'POST',
      headers:{'Content-Type':'application/json','Authorization':'Bearer '+AI_KEY},
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