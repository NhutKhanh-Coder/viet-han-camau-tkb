<?php
require_once '../config.php';
requireStudent();
$db   = getDB();
$sv_id = $_SESSION['student_id'];
$msg  = '';

// Lưu face descriptor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_face') {
        $desc = trim($_POST['face_descriptor'] ?? '');
        if ($desc) {
            $st = $db->prepare("UPDATE students SET face_descriptor=? WHERE id=?");
            $st->bind_param("si", $desc, $sv_id);
            $st->execute();
            $msg = 'success:Đã lưu dữ liệu khuôn mặt thành công!';
        } else { $msg = 'error:Không có dữ liệu khuôn mặt!'; }
    }
    if ($_POST['action'] === 'update_info') {
        $email = trim($_POST['email'] ?? '');
        $sdt   = trim($_POST['sdt'] ?? '');
        $dia_chi = trim($_POST['dia_chi'] ?? ''); // Thêm địa chỉ nếu có field này
        $st = $db->prepare("UPDATE students SET email=?, sdt=? WHERE id=?");
        $st->bind_param("ssi", $email, $sdt, $sv_id);
        $st->execute();
        $msg = 'success:Cập nhật thông tin thành công!';
    }
    if ($_POST['action'] === 'change_password') {
        $curr_pass = $_POST['current_password'] ?? '';
        $new_pass  = $_POST['new_password'] ?? '';
        $conf_pass = $_POST['confirm_new_password'] ?? '';
        
        if (strlen($new_pass) < 6) {
            $msg = 'error:Mật khẩu mới phải có ít nhất 6 ký tự!';
        } elseif ($new_pass !== $conf_pass) {
            $msg = 'error:Mật khẩu nhập lại không khớp!';
        } else {
            // Get user_id associated with student
            $st_uid = $db->prepare("SELECT user_id FROM students WHERE id = ?");
            $st_uid->bind_param("i", $sv_id);
            $st_uid->execute();
            $user_row = $st_uid->get_result()->fetch_assoc();
            
            if ($user_row) {
                $uid = $user_row['user_id'];
                
                // Fetch current hashed password
                $st_pass = $db->prepare("SELECT password FROM users WHERE id = ?");
                $st_pass->bind_param("i", $uid);
                $st_pass->execute();
                $db_pass = $st_pass->get_result()->fetch_assoc()['password'] ?? '';
                
                if (password_verify($curr_pass, $db_pass)) {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $st_up = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $st_up->bind_param("si", $hashed, $uid);
                    if ($st_up->execute()) {
                        $msg = 'success:Đổi mật khẩu thành công!';
                    } else {
                        $msg = 'error:Lỗi hệ thống khi cập nhật mật khẩu!';
                    }
                } else {
                    $msg = 'error:Mật khẩu hiện tại không chính xác!';
                }
            } else {
                $msg = 'error:Không tìm thấy tài khoản liên kết!';
            }
        }
    }
    if ($_POST['action'] === 'upload_avatar') {
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newFilename = "avatar_" . $sv_id . "_" . time() . "." . $ext;
                $destDir = "../assets/img/avatars/";
                if (!is_dir($destDir)) { mkdir($destDir, 0777, true); }
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $destDir . $newFilename)) {
                    $db->query("UPDATE students SET avatar='$newFilename' WHERE id=$sv_id");
                    $_SESSION['avatar'] = $newFilename; // Cập nhật session nếu cần
                    $msg = 'success:Tải ảnh đại diện thành công!';
                } else { $msg = 'error:Không thể lưu tệp tin.'; }
            } else { $msg = 'error:Định dạng ảnh không hợp lệ.'; }
        } else { $msg = 'error:Lỗi khi tải lên.'; }
    }
}

$st2 = $db->prepare("SELECT * FROM students WHERE id=?");
$st2->bind_param("i", $sv_id);
$st2->execute();
$sv = $st2->get_result()->fetch_assoc();
$hasFace = !empty($sv['face_descriptor']);
$db->close();

$msgType = ''; $msgText = '';
if ($msg) { [$msgType, $msgText] = explode(':', $msg, 2); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Hồ Sơ & Khuôn Mặt</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/tkb/assets/style.css">
<style>
.video-box{position:relative;width:100%;max-width:300px;aspect-ratio:4/3;background:var(--bg);border:1px solid var(--border);border-radius:var(--r-sm);overflow:hidden;margin:0 auto 16px;}
#regVideo{width:100%;height:100%;object-fit:cover;}
#regCanvas{position:absolute;inset:0;width:100%;height:100%;}
.scan-anim{position:absolute;top:0;left:0;right:0;height:3px;background:var(--success);animation:scan 2s linear infinite;display:none;}
.scan-anim.on{display:block;}
@keyframes scan{0%{top:0}100%{top:100%}}
.face-status{text-align:center;font-size:13px;color:var(--text2);margin-bottom:12px;min-height:20px;font-weight:600;}
.face-captured{background:rgba(16, 185, 129, 0.1);border:1px solid var(--success);color:var(--success);border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:12px;display:none;text-align:center;font-weight:600;}

/* Custom Profile CSS */
.profile-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 30px; }
.prof-card { background: var(--bg2); border-radius: var(--r-md); padding: 30px; box-shadow: var(--shadow-md); border: 1px solid var(--border); }
.prof-avatar-wrap { text-align: center; margin-bottom: 20px; position: relative; display: inline-block; left: 50%; transform: translateX(-50%); }
.prof-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg2); box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.prof-avatar-btn { position: absolute; bottom: 5px; right: 15px; background: var(--accent); color: #fff; width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.15); transition: background 0.2s; }
.prof-avatar-btn:hover { background: var(--accent2); }
.prof-name { text-align: center; font-size: 24px; font-family: 'Playfair Display', serif; font-weight: 800; margin-bottom: 5px; color: var(--text); }
.prof-msv { text-align: center; font-size: 11px; font-weight: 700; color: var(--text2); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
.prof-pill-blue { background: rgba(217, 27, 67, 0.08); color: var(--accent); padding: 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-align: center; display: block; text-decoration: none; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid rgba(217, 27, 67, 0.12); }
.prof-pill-gray { background: var(--bg3); color: var(--text2); padding: 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-align: center; display: block; letter-spacing: 0.5px; }
.prof-divider { height: 1px; background: var(--border); margin: 25px 0; }
.prof-meta-item { margin-bottom: 15px; }
.prof-meta-label { font-size: 10px; color: var(--text2); font-weight: 700; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px; }
.prof-meta-val { font-size: 14px; color: var(--text); font-weight: 600; }

.prof-title { font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 800; color: var(--text); margin-bottom: 30px; text-transform: uppercase; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
.prof-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 25px; }
.prof-input-wrap { position: relative; }
.prof-input-wrap i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text2); }
.prof-input { width: 100%; padding: 15px 15px 15px 45px; border: 1.5px solid var(--border) !important; border-radius: var(--r-sm); background: var(--bg) !important; font-size: 14px; color: var(--text) !important; outline: none; transition: 0.2s; font-family: 'Outfit', sans-serif; }
.prof-input:focus { border-color: var(--accent) !important; background: var(--bg2) !important; box-shadow: 0 0 0 4px rgba(217, 27, 67, 0.08); }
.prof-textarea { padding-left: 45px; min-height: 100px; resize: vertical; top: 15px; transform: none; }
.prof-textarea-icon { top: 20px !important; transform: none !important; }
.prof-hint { font-size: 11px; color: var(--text2); margin-top: 8px; }

.prof-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border); }
.prof-notice { font-size: 11px; color: var(--text2); display: flex; align-items: flex-start; gap: 8px; max-width: 60%; line-height: 1.5; }
.prof-notice i { color: var(--accent); font-size: 14px; }
.prof-btn-save { background: linear-gradient(135deg, var(--accent), var(--accent2)); color: #fff; border: none; padding: 12px 25px; border-radius: var(--r-sm); font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; font-family: 'Outfit', sans-serif; box-shadow: 0 4px 12px rgba(217, 27, 67, 0.2); }
.prof-btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(217, 27, 67, 0.35); }

.badge-face-yes { background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid var(--success); }
.badge-face-no { background: rgba(217, 27, 67, 0.1); color: var(--accent); padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid rgba(217, 27, 67, 0.15); }
</style>
</head>
<body>
<?php include '../includes/student_nav.php'; ?>
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-user-pen" style="color:var(--accent)"></i> Hồ Sơ Cá Nhân</h1></div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
  <?php endif; ?>

  <div class="profile-grid">
    <!-- Left Column: Avatar & Meta -->
    <div class="prof-card">
        <div class="prof-avatar-wrap">
            <?php $avatar_url = !empty($sv['avatar']) ? '/tkb/assets/img/avatars/' . $sv['avatar'] : '/tkb/assets/img/logo_vkc.jpg'; ?>
            <img src="<?= $avatar_url ?>" class="prof-avatar" alt="Avatar">
            <button class="prof-avatar-btn" onclick="document.getElementById('avatarInput').click()"><i class="fa-solid fa-camera"></i></button>
            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="action" value="upload_avatar">
                <input type="file" name="avatar_file" id="avatarInput" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
            </form>
        </div>
        <div class="prof-name"><?= htmlspecialchars($sv['ho_ten']) ?></div>
        <div class="prof-msv">MSV: <?= $sv['ma_sv'] ?></div>
        
        <div class="prof-pill-blue"><i class="fa-solid fa-graduation-cap"></i> <?= htmlspecialchars($sv['khoa']) ?></div>
        <div class="prof-pill-gray">ĐANG THEO HỌC</div>
        
        <div class="prof-divider"></div>
        
        <div class="prof-meta-item">
            <div class="prof-meta-label">NGÀNH HỌC</div>
            <div class="prof-meta-val">Chưa cập nhật</div>
        </div>
        <div class="prof-meta-item">
            <div class="prof-meta-label">NGÀY NHẬP HỌC</div>
            <div class="prof-meta-val">28/03/2026</div>
        </div>
    </div>

    <!-- Right Column: Form & Face -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Contact Form -->
        <div class="prof-card">
            <div class="prof-title">CHỈNH SỬA THÔNG TIN LIÊN LẠC</div>
            <form method="POST">
                <input type="hidden" name="action" value="update_info">
                <div class="prof-form-row">
                    <div>
                        <div class="prof-meta-label">ĐỊA CHỈ EMAIL</div>
                        <div class="prof-input-wrap">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" name="email" class="prof-input" value="<?= htmlspecialchars($sv['email'] ?? '') ?>" placeholder="your@email.com">
                        </div>
                        <div class="prof-hint">Dùng để nhận thông báo chính thức.</div>
                    </div>
                    <div>
                        <div class="prof-meta-label">SỐ ĐIỆN THOẠI</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-mobile-screen"></i>
                            <input type="text" name="sdt" class="prof-input" value="<?= htmlspecialchars($sv['sdt'] ?? '') ?>" placeholder="0919495912">
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="prof-meta-label">ĐỊA CHỈ THƯỜNG TRÚ</div>
                    <div class="prof-input-wrap">
                        <i class="fa-solid fa-location-dot prof-textarea-icon"></i>
                        <textarea name="dia_chi" class="prof-input prof-textarea" placeholder="Số nhà, đường, phường/xã..."></textarea>
                    </div>
                </div>

                <div class="prof-footer">
                    <div class="prof-notice">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Các thông tin mang tính định danh chính thức chỉ có thể được thay đổi bởi Phòng Đào tạo.</span>
                    </div>
                    <button type="submit" class="prof-btn-save"><i class="fa-solid fa-download"></i> LƯU THAY ĐỔI</button>
                </div>
            </form>
        </div>

        <!-- Đổi Mật Khẩu -->
        <div class="prof-card">
            <div class="prof-title">ĐỔI MẬT KHẨU</div>
            <form method="POST" onsubmit="return validateProfilePasswords()">
                <input type="hidden" name="action" value="change_password">
                <div class="prof-form-row">
                    <div>
                        <div class="prof-meta-label">Mật khẩu hiện tại</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" id="current_password" name="current_password" class="prof-input" placeholder="Nhập mật khẩu hiện tại" required>
                        </div>
                    </div>
                    <div>
                        <div class="prof-meta-label">Mật khẩu mới</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-key"></i>
                            <input type="password" id="new_password" name="new_password" class="prof-input" placeholder="Tối thiểu 6 ký tự" required>
                        </div>
                    </div>
                </div>
                <div class="prof-form-row" style="margin-bottom: 0;">
                    <div>
                        <div class="prof-meta-label">Nhập lại mật khẩu mới</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-key"></i>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" class="prof-input" placeholder="Xác nhận mật khẩu mới" required>
                        </div>
                    </div>
                </div>
                <div class="prof-footer" style="margin-top: 25px;">
                    <div class="prof-notice">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Mật khẩu của bạn được mã hóa an toàn theo tiêu chuẩn bảo mật.</span>
                    </div>
                    <button type="submit" class="prof-btn-save"><i class="fa-solid fa-key"></i> ĐỔI MẬT KHẨU</button>
                </div>
            </form>
        </div>

        <!-- Khuôn mặt -->
        <div class="prof-card">
            <div class="prof-title" style="margin-bottom: 20px;">
                BẢO MẬT KHUÔN MẶT
                <?php if ($hasFace): ?>
                <span class="badge-face-yes"><i class="fa-solid fa-circle-check"></i> Đã đăng ký</span>
                <?php else: ?>
                <span class="badge-face-no"><i class="fa-solid fa-circle-xmark"></i> Chưa đăng ký</span>
                <?php endif; ?>
            </div>
            
            <p style="font-size:13px;color:#666;margin-bottom:20px; line-height: 1.5;">Đăng ký khuôn mặt để đăng nhập nhanh chóng, an toàn không cần mật khẩu.</p>
            <div class="video-box">
              <video id="regVideo" autoplay muted playsinline></video>
              <canvas id="regCanvas"></canvas>
              <div class="scan-anim" id="scanAnim"></div>
            </div>
            <div class="face-status" id="faceStatus">Nhấn "Bắt đầu" để mở camera</div>
            <div class="face-captured" id="faceCaptured"><i class="fa-solid fa-circle-check"></i> Đã chụp khuôn mặt! Nhấn "Lưu" để xác nhận.</div>
            <form method="POST" id="faceForm">
              <input type="hidden" name="action" value="save_face">
              <input type="hidden" name="face_descriptor" id="faceDescInput">
            </form>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap; margin-top: 20px;">
              <button class="prof-btn-save" style="background:var(--accent)" id="btnStart" onclick="startCamera()"><i class="fa-solid fa-camera"></i> Bắt đầu quét</button>
              <button class="prof-btn-save" style="background:#8b5cf6; display:none;" id="btnCapture" onclick="captureface()"><i class="fa-solid fa-expand"></i> Chụp khuôn mặt</button>
              <button class="prof-btn-save" style="background:#16a34a; display:none;" id="btnSave" onclick="saveFace()"><i class="fa-solid fa-floppy-disk"></i> Lưu khuôn mặt</button>
              <button class="prof-btn-save" style="background:#ef4444; display:none;" onclick="stopCamera()" id="btnStop"><i class="fa-solid fa-stop"></i> Dừng</button>
            </div>
        </div>
    </div>
  </div>
</div> <!-- content-pad -->
</div> <!-- main-content -->

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
let stream = null, modelsLoaded = false, capturedDesc = null;
const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';

async function startCamera() {
  const status = document.getElementById('faceStatus');
  status.textContent = 'Đang tải mô hình AI...';
  if (!modelsLoaded) {
    try {
      await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
      await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
      await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
      modelsLoaded = true;
    } catch(e) { status.textContent = 'Lỗi tải AI: ' + e.message; return; }
  }
  stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
  document.getElementById('regVideo').srcObject = stream;
  document.getElementById('scanAnim').classList.add('on');
  status.textContent = 'Camera sẵn sàng. Nhìn thẳng vào camera.';
  document.getElementById('btnStart').style.display   = 'none';
  document.getElementById('btnCapture').style.display = 'inline-flex';
  document.getElementById('btnStop').style.display    = 'inline-flex';
}

async function captureface() {
  const video  = document.getElementById('regVideo');
  const canvas = document.getElementById('regCanvas');
  const status = document.getElementById('faceStatus');
  status.textContent = 'Đang phát hiện khuôn mặt...';
  const opts = new faceapi.TinyFaceDetectorOptions({ inputSize:224, scoreThreshold:0.5 });
  const det  = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
  if (!det) { status.textContent = '✗ Không phát hiện khuôn mặt! Thử lại.'; return; }
  const dims = faceapi.matchDimensions(canvas, video, true);
  faceapi.draw.drawDetections(canvas, faceapi.resizeResults(det, dims));
  faceapi.draw.drawFaceLandmarks(canvas, faceapi.resizeResults(det, dims));
  capturedDesc = Array.from(det.descriptor);
  document.getElementById('faceCaptured').style.display = 'block';
  document.getElementById('btnSave').style.display      = 'inline-flex';
  status.textContent = '✓ Phát hiện thành công!';
}

function saveFace() {
  if (!capturedDesc) return;
  document.getElementById('faceDescInput').value = JSON.stringify(capturedDesc);
  document.getElementById('faceForm').submit();
}

function stopCamera() {
  if (stream) { stream.getTracks().forEach(t=>t.stop()); stream=null; }
  document.getElementById('scanAnim').classList.remove('on');
  document.getElementById('faceStatus').textContent = 'Camera đã tắt.';
  document.getElementById('btnStart').style.display   = 'inline-flex';
  document.getElementById('btnCapture').style.display = 'none';
  document.getElementById('btnStop').style.display    = 'none';
}
function validateProfilePasswords() {
    var p = document.getElementById('new_password').value;
    var cp = document.getElementById('confirm_new_password').value;
    if (p.length < 6) {
        alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
        return false;
    }
    if (p !== cp) {
        alert('Mật khẩu nhập lại không khớp!');
        return false;
    }
    return true;
}
</script>
</body>
</html>
