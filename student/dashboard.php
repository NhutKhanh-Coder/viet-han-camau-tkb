<?php
require_once '../config.php';
requireStudent();

$db    = getDB();
$sv_id = $_SESSION['student_id'] ?? 0;

if (!$sv_id) {
    session_destroy();
    header('Location: /tkb/login.php');
    exit;
}

$stmt = $db->prepare("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
if (!$stmt) {
    error_log("dashboard prepare error: " . $db->error);
    die("<p style='font-family:sans-serif;padding:40px;color:#c00'>Lỗi hệ thống: không thể tải dữ liệu sinh viên. Vui lòng liên hệ quản trị viên.<br><small>" . htmlspecialchars($db->error) . "</small></p>");
}

$stmt->bind_param("i", $sv_id);
$stmt->execute();
$sv = $stmt->get_result()->fetch_assoc();

if (!$sv) {
    $sv = [
        'ho_ten'   => $_SESSION['username'] ?? 'Sinh viên',
        'ma_sv'    => $_SESSION['username'] ?? '',
        'lop'      => '',
        'khoa'     => '',
        'email'    => '',
        'avatar'   => '',
        'username' => $_SESSION['username'] ?? '',
    ];
}

// === Lấy lịch học hôm nay ===
$khoa = $sv['khoa'] ?? '';
$thu_hien_tai = (int)date('N') + 1;

$tkb_hom_nay = [];
if (!empty($khoa)) {
    $st2 = $db->prepare(
        "SELECT t.*, m.ten_mon, g.ho_ten AS ten_gv
         FROM thoi_khoa_bieu t
         JOIN mon_hoc m ON t.mon_hoc_id = m.id
         LEFT JOIN giang_vien g ON t.giang_vien_id = g.id
         WHERE t.khoa = ? AND t.thu = ?
         ORDER BY t.tiet_bat_dau ASC"
    );
    if ($st2) {
        $st2->bind_param("si", $khoa, $thu_hien_tai);
        $st2->execute();
        $tkb_hom_nay = $st2->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// === Lấy thông báo mới nhất ===
$thong_bao = [];
$tb_res = $db->query("SELECT * FROM thong_bao WHERE trang_thai='Đã xuất bản' ORDER BY ngay_dang DESC, id DESC LIMIT 5");
if ($tb_res) {
    $thong_bao = $tb_res->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard - Sinh Viên</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
</head>
<body>
<?php include '../includes/student_nav.php'; ?>

        <div class="welcome-banner">
            <div class="wb-left">
                <p>Cao đẳng Nghề VN-HQ Cà Mau / Sinh viên</p>
                <h2><i class="fa-regular fa-address-card"></i> CHÀO MỪNG, <?= mb_strtoupper(htmlspecialchars($sv['ho_ten'])) ?>!</h2>
                <p>HÔM NAY LÀ <?= date('d/m/Y') ?>. CHÚC BẠN MỘT NGÀY HỌC TẬP HỨNG KHỞI VÀ HIỆU QUẢ.</p>
            </div>
            <div class="wb-badge">
                <i class="fa-solid fa-shield"></i>
                <div>
                    <strong>LỚP SINH HOẠT</strong>
                    <span><?= htmlspecialchars($sv['lop'] ?: 'Chưa cập nhật') ?></span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="d-main">
                <a href="/tkb/student/hoc_phan.php" class="menu-card">
                    <i class="fa-solid fa-book mc-icon" style="color: var(--accent);"></i>
                    <div class="mc-title">HỌC PHẦN</div>
                    <div class="mc-sub">Chương trình đào tạo</div>
                </a>
                <a href="/tkb/student/tkb.php" class="menu-card">
                    <i class="fa-regular fa-calendar-days mc-icon" style="color: #22c55e;"></i>
                    <div class="mc-title">THỜI KHÓA BIỂU</div>
                    <div class="mc-sub">Lịch học chi tiết</div>
                </a>
                <a href="/tkb/student/diem.php" class="menu-card">
                    <i class="fa-solid fa-graduation-cap mc-icon" style="color: #eab308;"></i>
                    <div class="mc-title">BẢNG ĐIỂM</div>
                    <div class="mc-sub">Kết quả học tập</div>
                </a>
                <a href="/tkb/student/diem_danh.php" class="menu-card">
                    <i class="fa-solid fa-user-check mc-icon" style="color: var(--accent);"></i>
                    <div class="mc-title">ĐIỂM DANH</div>
                    <div class="mc-sub">Chuyên cần & Check-in</div>
                </a>
                <a href="/tkb/student/tai_chinh.php" class="menu-card">
                    <i class="fa-solid fa-money-bill mc-icon" style="color: #ef4444;"></i>
                    <div class="mc-title">TÀI CHÍNH</div>
                    <div class="mc-sub">Học phí & Lệ phí</div>
                </a>
                <a href="/tkb/student/profile.php" class="menu-card">
                    <i class="fa-solid fa-id-badge mc-icon" style="color: #333;"></i>
                    <div class="mc-title">HỒ SƠ</div>
                    <div class="mc-sub">Cài đặt tài khoản</div>
                </a>

                <!-- Thông báo từ Khoa -->
                <div class="card" style="grid-column: 1 / -1; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(217, 27, 67, 0.08); padding-bottom: 15px;">
                        <h3 style="font-size: 18px; font-weight: 800; font-family: 'Playfair Display', serif; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--text);">
                            <i class="fa-solid fa-bullhorn" style="color: #eab308;"></i> Thông báo từ Khoa
                        </h3>
                        <a href="#" style="font-size: 11px; font-weight: 700; color: var(--accent); text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px;">Xem tất cả <i class="fa-solid fa-arrow-right" style="margin-left: 3px;"></i></a>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (empty($thong_bao)): ?>
                        <div style="text-align:center; color:#999; padding:10px;">Chưa có thông báo nào</div>
                        <?php else: foreach ($thong_bao as $tb): ?>
                        <div style="border-bottom: 1px solid rgba(217, 27, 67, 0.04); padding-bottom: 20px;">
                            <div style="font-size: 10px; color: var(--text2); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                <?= !empty($tb['ngay_dang']) ? date('d THÁNG m, Y', strtotime($tb['ngay_dang'])) : 'Chưa rõ ngày' ?>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 700; color: var(--text); margin: 0 0 8px 0;"><?= htmlspecialchars($tb['tieu_de']) ?></h4>
                            <p style="font-size: 13px; color: var(--text2); margin: 0; line-height: 1.5;"><?= nl2br(htmlspecialchars($tb['noi_dung'])) ?></p>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-side">
                <div class="side-card">
                    <div class="sc-header"><i class="fa-regular fa-clock"></i> Lịch học hôm nay</div>
                    <?php if (empty($tkb_hom_nay)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-calendar-xmark"></i>
                        <p>Hôm nay bạn không có lịch học.</p>
                    </div>
                    <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:12px; margin-top: 15px;">
                        <?php foreach ($tkb_hom_nay as $t): ?>
                        <div style="background: #f8f9fa; border-radius: 10px; padding: 15px; border-left: 4px solid var(--accent);">
                            <div style="font-weight: 700; font-size: 14px; color: #222; margin-bottom: 5px;"><?= htmlspecialchars($t['ten_mon']) ?></div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 3px;"><i class="fa-solid fa-clock" style="width:16px;color:#888"></i> Tiết <?= $t['tiet_bat_dau'] ?> - <?= $t['tiet_ket_thuc'] ?></div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 3px;"><i class="fa-solid fa-location-dot" style="width:16px;color:#888"></i> P. <?= htmlspecialchars($t['phong_hoc']) ?></div>
                            <div style="font-size: 12px; color: #666;"><i class="fa-solid fa-chalkboard-user" style="width:16px;color:#888"></i> GV: <?= htmlspecialchars($t['ten_gv'] ?? 'TBA') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="side-card">
                    <div class="sc-header"><i class="fa-regular fa-id-card"></i> Mã định danh SV</div>
                    <div style="background: #ffffff; border: 1px solid rgba(217, 27, 67, 0.15); border-radius: 12px; padding: 20px; text-align: center; position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                        <div style="color: #d91b43; font-size: 10px; font-weight: 800; margin-bottom: 15px; letter-spacing: 0.5px;">CAO ĐẲNG NGHỀ VN-HQ CÀ MAU</div>
                        <?php $avatar_url = !empty($sv['avatar']) ? '/tkb/assets/img/avatars/' . $sv['avatar'] : '/tkb/assets/img/logo_vkc.jpg'; ?>
                        <img src="<?= htmlspecialchars($avatar_url) ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 10px; background: #fff;">
                        <h4 style="margin: 5px 0; color: #222; font-size: 16px; font-weight: 800; font-family: 'Playfair Display', serif;"><?= mb_strtoupper(htmlspecialchars($sv['ho_ten'])) ?></h4>
                        <div style="background: #e2e8f0; color: #334155; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; margin-bottom: 15px; letter-spacing: 1px;">
                            MSV: <?= htmlspecialchars($sv['ma_sv'] ?? '-') ?>
                        </div>
                        <?php if (!empty($sv['ma_sv'])): ?>
                        <div>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($sv['ma_sv']) ?>" width="90" style="border-radius: 4px; mix-blend-mode: multiply;">
                        </div>
                        <p style="font-size: 10px; color: #888; margin-top: 15px; font-style: italic;">Quét mã QR để điểm danh nhanh</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<style>
.cfab{position:fixed;bottom:24px;right:24px;width:58px;height:58px;border-radius:50%;background:#d91b43;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:9999;box-shadow:0 8px 24px rgba(217,27,67,0.45);border:1.5px solid rgba(255,255,255,0.3);transition:transform 0.25s, box-shadow 0.25s}
.cfab:hover{transform:scale(1.1) rotate(5deg);box-shadow:0 12px 32px rgba(217,27,67,0.6)}
.cfab svg{width:26px;height:26px;fill:#fff}
.cbox{position:fixed;bottom:94px;right:24px;width:365px;background:rgba(255,255,255,0.78);backdrop-filter:blur(30px);-webkit-backdrop-filter:blur(30px);border:1px solid rgba(255,255,255,0.5);border-radius:24px;display:none;flex-direction:column;z-index:9998;box-shadow:0 16px 48px rgba(15,23,42,0.12);overflow:hidden;max-height:540px;animation:cslide .25s cubic-bezier(0.34, 1.56, 0.64, 1)}
@keyframes cslide{from{opacity:0;transform:translateY(18px) scale(.97)}to{opacity:1;transform:none}}
.cbox.open{display:flex}
.chdr{display:flex;align-items:center;gap:10px;padding:16px 20px;background:#d91b43}
.chdr-ic{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center}
.chdr-ic svg{width:17px;height:17px;fill:#fff}
.chdr-info{flex:1}
.chdr-info p{font-size:13px;font-weight:700;color:#fff;margin:0}
.chdr-info span{font-size:11px;color:rgba(255,255,255,0.7)}
.cclose{background:none;border:none;color:rgba(255,255,255,0.8);cursor:pointer;font-size:18px;padding:0 4px}
.cclose:hover{color:#fff}
.cmsgs{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;min-height:240px;max-height:350px;background:rgba(244, 246, 252, 0.6)}
.cmsgs::-webkit-scrollbar{width:4px}
.cmsgs::-webkit-scrollbar-thumb{background:rgba(217,27,67,0.25);border-radius:2px}
.cmsg{display:flex;flex-direction:column;max-width:84%}
.cmsg.user{align-self:flex-end;align-items:flex-end}
.cmsg.bot{align-self:flex-start;align-items:flex-start}
.cbubble{padding:10px 14px;border-radius:16px;font-size:13px;line-height:1.55;word-break:break-word}
.cmsg.user .cbubble{background:#d91b43;color:#fff;border-bottom-right-radius:4px;box-shadow:0 4px 12px rgba(217, 27, 67, 0.15)}
.cmsg.bot .cbubble{background:rgba(255,255,255,0.9);color:#1e293b;border-bottom-left-radius:4px;border:1px solid rgba(255,255,255,0.6);box-shadow:0 4px 12px rgba(0,0,0,0.03)}
.ctime{font-size:10px;color:#94a3b8;margin-top:3px;padding:0 3px}
.ptag{font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700;margin-left:5px;vertical-align:middle;background:rgba(217,27,67,0.1);color:#d91b43}
.ctyping{display:flex;align-items:center;gap:4px;padding:10px 14px;background:rgba(255,255,255,0.9);border-radius:14px;border-bottom-left-radius:4px;border:1px solid rgba(255,255,255,0.6);width:fit-content}
.ctyping span{width:6px;height:6px;background:#d91b43;border-radius:50%;animation:cdot 1.2s infinite}
.ctyping span:nth-child(2){animation-delay:.2s}
.ctyping span:nth-child(3){animation-delay:.4s}
@keyframes cdot{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
.cinrow{display:flex;gap:8px;padding:12px 16px;border-top:1px solid rgba(255,255,255,0.4);background:rgba(255,255,255,0.5)}
.cinput{flex:1;background:rgba(255,255,255,0.6);border:1.5px solid rgba(255,255,255,0.5);border-radius:12px;padding:10px 14px;color:#0f172a;font-size:13px;outline:none;resize:none;max-height:80px;font-family:inherit;transition:border-color 0.18s, background-color 0.18s;line-height:1.4}
.cinput::placeholder{color:#94a3b8}
.cinput:focus{border-color:rgba(217, 27, 67, 0.4);background:#fff}
.csend{width:40px;height:40px;border-radius:12px;background:#d91b43;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s, transform .1s;flex-shrink:0;align-self:flex-end}
.csend:hover{transform:scale(1.05)}
.csend svg{width:16px;height:16px;fill:#fff}
.csend:disabled{background:#cbd5e1;cursor:not-allowed;opacity:.4;transform:none}
</style>

<button class="cfab" onclick="cToggle()">
  <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
</button>

<div class="cbox" id="cbox">
  <div class="chdr">
    <div class="chdr-ic">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
    </div>
    <div class="chdr-info">
      <p>Trợ lý AI - Cao đẳng KT&CN</p>
      <span>Powered by Groq / Llama 3.3</span>
    </div>
    <button class="cclose" onclick="cToggle()">✕</button>
  </div>
  <div class="cmsgs" id="cmsgs">
    <div class="cmsg bot">
      <div class="cbubble">Xin chào <?= htmlspecialchars($sv['ho_ten']) ?>! Tôi có thể giúp gì cho bạn hôm nay? 👋</div>
      <div class="ctime">Vừa xong</div>
    </div>
  </div>
  <div class="cinrow">
    <textarea class="cinput" id="cinput" placeholder="Nhập tin nhắn..." rows="1"
      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();cSend()}"
      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
    <button class="csend" id="csend" onclick="cSend()">
      <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
    </button>
  </div>
</div>

<script>
var GROQ_KEY='gsk_' + 'RQc8Lf9ro9hKAfMkltkcWGdyb3FY8DX77y7FuqQhcY88iLQ6K0nB';
var _hist=[],_busy=false;
var SYS='Bạn là trợ lý AI của Cao Đẳng Nghề Việt Nam - Hàn Quốc Cà Mau. Trả lời bằng tiếng Việt, thân thiện, ngắn gọn và hữu ích. Hỗ trợ sinh viên về thông tin tuyển sinh, chương trình học, thời khóa biểu và các dịch vụ của trường.';
function cToggle(){var b=document.getElementById('cbox');b.classList.toggle('open');if(b.classList.contains('open'))document.getElementById('cinput').focus();}
function cTime(){return new Date().toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});}
function cAppend(role,text){
  var c=document.getElementById('cmsgs'),d=document.createElement('div');
  d.className='cmsg '+role;
  var tag=role==='bot'?'<span class="ptag">GROQ</span>':'';
  var safe=text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
  d.innerHTML='<div class="cbubble">'+safe+tag+'</div><div class="ctime">'+cTime()+'</div>';
  c.appendChild(d);c.scrollTop=c.scrollHeight;
}
function cShowTyping(){var c=document.getElementById('cmsgs'),d=document.createElement('div');d.className='cmsg bot';d.id='ctyp';d.innerHTML='<div class="ctyping"><span></span><span></span><span></span></div>';c.appendChild(d);c.scrollTop=c.scrollHeight;}
function cRemoveTyping(){var e=document.getElementById('ctyp');if(e)e.remove();}
async function cSend(){
  var inp=document.getElementById('cinput'),txt=inp.value.trim();
  if(!txt||_busy)return;
  inp.value='';inp.style.height='auto';
  _busy=true;document.getElementById('csend').disabled=true;
  cAppend('user',txt);cShowTyping();
  var msgs=[{role:'system',content:SYS}];
  _hist.forEach(function(m){msgs.push({role:m.role,content:m.content});});
  msgs.push({role:'user',content:txt});
  var reply='';
  try{
    var r=await fetch('https://api.groq.com/openai/v1/chat/completions',{
      method:'POST',
      headers:{'Content-Type':'application/json','Authorization':'Bearer '+GROQ_KEY},
      body:JSON.stringify({model:'llama-3.3-70b-versatile',messages:msgs,max_tokens:800,temperature:0.7})
    });
    var data=await r.json();
    if(data.error){reply='Lỗi: '+(data.error.message||'Không xác định');}
    else{reply=data.choices&&data.choices[0]&&data.choices[0].message&&data.choices[0].message.content||'Xin lỗi, tôi không hiểu.';_hist.push({role:'user',content:txt});_hist.push({role:'assistant',content:reply});}
  }catch(e){reply='Không kết nối được. Vui lòng kiểm tra mạng.';}
  cRemoveTyping();cAppend('bot',reply);
  _busy=false;document.getElementById('csend').disabled=false;
  document.getElementById('cinput').focus();
}
</script>
<!-- ===== KẾT THÚC CHATBOT ===== -->
  </div> <!-- content-pad -->
</div> <!-- main-content -->
</body>
</html>