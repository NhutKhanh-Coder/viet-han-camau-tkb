<?php
require_once '../config.php';
requireAdmin();
$db   = getDB();
$user_id = $_SESSION['user_id'];
$msg  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_face') {
        $desc = trim($_POST['face_descriptor'] ?? '');
        if ($desc) {
            $st = $db->prepare("UPDATE users SET face_descriptor=? WHERE id=?");
            $st->bind_param("si", $desc, $user_id);
            $st->execute();
            $msg = 'success:Đã lưu dữ liệu khuôn mặt QTV thành công!';
        } else { $msg = 'error:Không có dữ liệu khuôn mặt!'; }
    }
    if ($_POST['action'] === 'update_info') {
        $email = trim($_POST['email'] ?? '');
        $sdt   = trim($_POST['sdt'] ?? '');
        $ho_ten = trim($_POST['ho_ten'] ?? '');
        $st = $db->prepare("UPDATE users SET email=?, sdt=?, ho_ten=? WHERE id=?");
        $st->bind_param("sssi", $email, $sdt, $ho_ten, $user_id);
        $st->execute();
        $msg = 'success:Cập nhật thông tin thành công!';
    }
    if ($_POST['action'] === 'upload_avatar') {
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newFilename = "admin_" . $user_id . "_" . time() . "." . $ext;
                $destDir = "../assets/img/avatars/";
                if (!is_dir($destDir)) { mkdir($destDir, 0777, true); }
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $destDir . $newFilename)) {
                    $db->query("UPDATE users SET avatar='$newFilename' WHERE id=$user_id");
                    $msg = 'success:Tải ảnh đại diện thành công!';
                } else { $msg = 'error:Không thể lưu tệp tin.'; }
            } else { $msg = 'error:Định dạng ảnh không hợp lệ.'; }
        } else { $msg = 'error:Lỗi khi tải lên.'; }
    }
    if ($_POST['action'] === 'update_security') {
        $new_user = trim($_POST['new_username']);
        $new_pass = $_POST['new_password'];
        
        // Kiểm tra xem username mới có bị trùng không
        $check = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $new_user, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $msg = 'error:Tên đăng nhập này đã có người sử dụng!';
        } else {
            if (!empty($new_pass)) {
                // Đổi cả tk và mk
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $st = $db->prepare("UPDATE users SET username=?, password=? WHERE id=?");
                $st->bind_param("ssi", $new_user, $hash, $user_id);
            } else {
                // Chỉ đổi tk
                $st = $db->prepare("UPDATE users SET username=? WHERE id=?");
                $st->bind_param("si", $new_user, $user_id);
            }
            if ($st->execute()) {
                $_SESSION['username'] = $new_user; // Cập nhật session
                $msg = 'success:Đổi thông tin bảo mật (Tài khoản/Mật khẩu) thành công!';
            } else {
                $msg = 'error:Không thể cập nhật!';
            }
        }
    }
}

$st2 = $db->prepare("SELECT * FROM users WHERE id=?");
$st2->bind_param("i", $user_id);
$st2->execute();
$admin_data = $st2->get_result()->fetch_assoc();
$hasFace = !empty($admin_data['face_descriptor']);
$db->close();

$msgType = ''; $msgText = '';
if ($msg) { [$msgType, $msgText] = explode(':', $msg, 2); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Hồ Sơ Quản Trị Viên</title>
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

/* Custom Profile CSS Admin */
.profile-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 30px; }
.prof-card { background: var(--bg2); border-radius: var(--r-md); padding: 30px; border: 1px solid var(--border); box-shadow: var(--shadow-md); }
.prof-avatar-wrap { text-align: center; margin-bottom: 20px; position: relative; display: inline-block; left: 50%; transform: translateX(-50%); }
.prof-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg2); box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.prof-avatar-btn { position: absolute; bottom: 5px; right: 15px; background: var(--accent); color: #fff; width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(198, 42, 71, 0.2); transition: background 0.2s; }
.prof-avatar-btn:hover { background: var(--accent2); }
.prof-name { text-align: center; font-size: 24px; font-weight: 800; margin-bottom: 5px; color: var(--text); }
.prof-msv { text-align: center; font-size: 11px; font-weight: 700; color: var(--accent); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
.prof-pill-blue { background: rgba(217, 27, 67, 0.08); color: var(--accent); padding: 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-align: center; display: block; border: 1px solid rgba(217, 27, 67, 0.12); margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; }
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
.prof-hint { font-size: 11px; color: var(--text2); margin-top: 8px; }

.prof-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border); }
.prof-notice { font-size: 11px; color: var(--text2); display: flex; align-items: flex-start; gap: 8px; max-width: 60%; line-height: 1.5; }
.prof-notice i { color: var(--accent); font-size: 14px; }
.prof-btn-save { background: linear-gradient(135deg, var(--accent), var(--accent2)); color: #fff; border: none; padding: 12px 25px; border-radius: var(--r-sm); font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; font-family: 'Outfit', sans-serif; box-shadow: 0 4px 12px rgba(217, 27, 67, 0.2); }
.prof-btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(217, 27, 67, 0.35); }

.badge-face-yes { background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid var(--success); }
.badge-face-no { background: rgba(217, 27, 67, 0.1); color: var(--accent); padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid rgba(217, 27, 67, 0.15); }1px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid rgba(198, 42, 71, 0.15); }
</style>
</head>
<body>
<?php include '../includes/admin_nav.php'; ?>
<div class="main-content">
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-user-shield" style="color:var(--accent)"></i> Hồ Sơ Quản Trị Viên</h1></div>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($msgText) ?>
  </div>
  <?php endif; ?>

  <div class="profile-grid">
    <!-- Left Column: Avatar & Meta -->
    <div class="prof-card">
        <div class="prof-avatar-wrap">
            <?php $avatar_url = !empty($admin_data['avatar']) ? '/tkb/assets/img/avatars/' . $admin_data['avatar'] : '/tkb/assets/img/logo_vkc.jpg'; ?>
            <img src="<?= $avatar_url ?>" class="prof-avatar" alt="Avatar">
            <button class="prof-avatar-btn" onclick="document.getElementById('avatarInput').click()"><i class="fa-solid fa-camera"></i></button>
            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="action" value="upload_avatar">
                <input type="file" name="avatar_file" id="avatarInput" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
            </form>
        </div>
        <div class="prof-name"><?= htmlspecialchars(!empty($admin_data['ho_ten']) ? mb_strtoupper($admin_data['ho_ten'], 'UTF-8') : 'SYSTEM ADMIN') ?></div>
        <div class="prof-msv">ID: <?= htmlspecialchars($admin_data['username']) ?></div>
        
        <div class="prof-pill-blue"><i class="fa-solid fa-shield-halved"></i> QUẢN TRỊ VIÊN CẤP CAO</div>
        <div class="prof-pill-gray">HOẠT ĐỘNG</div>
        
        <div class="prof-divider"></div>
        
        <div class="prof-meta-item">
            <div class="prof-meta-label">QUYỀN TRUY CẬP</div>
            <div class="prof-meta-val" style="color:var(--success); font-weight:700;">FULL ACCESS</div>
        </div>
        <div class="prof-meta-item">
            <div class="prof-meta-label">NGÀY TẠO TÀI KHOẢN</div>
            <div class="prof-meta-val"><?= date('d/m/Y', strtotime($admin_data['created_at'])) ?></div>
        </div>
    </div>

    <!-- Right Column: Form & Face -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Contact Form -->
        <div class="prof-card">
            <div class="prof-title">CHỈNH SỬA THÔNG TIN LIÊN LẠC & BẢO MẬT</div>
            <form method="POST">
                <input type="hidden" name="action" value="update_info">
                <div class="prof-form-row">
                    <div>
                        <div class="prof-meta-label">HỌ TÊN HIỂN THỊ</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-signature"></i>
                            <input type="text" name="ho_ten" class="prof-input" value="<?= htmlspecialchars($admin_data['ho_ten'] ?? '') ?>" placeholder="Tên hiển thị">
                        </div>
                    </div>
                </div>
                <div class="prof-form-row">
                    <div>
                        <div class="prof-meta-label">ĐỊA CHỈ EMAIL</div>
                        <div class="prof-input-wrap">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" name="email" class="prof-input" value="<?= htmlspecialchars($admin_data['email'] ?? '') ?>" placeholder="admin@domain.com">
                        </div>
                    </div>
                    <div>
                        <div class="prof-meta-label">SỐ ĐIỆN THOẠI</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-mobile-screen"></i>
                            <input type="text" name="sdt" class="prof-input" value="<?= htmlspecialchars($admin_data['sdt'] ?? '') ?>" placeholder="09xxxxxxx">
                        </div>
                    </div>
                </div>
                <div class="prof-footer">
                    <button type="submit" class="prof-btn-save"><i class="fa-solid fa-download"></i> LƯU THÔNG TIN</button>
                </div>
            </form>
            
            <hr style="border:0; border-top:1px solid var(--border); margin: 25px 0;">
            
            <form method="POST">
                <input type="hidden" name="action" value="update_security">
                <div class="prof-form-row">
                    <div>
                        <div class="prof-meta-label">TÊN ĐĂNG NHẬP MỚI</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-user-lock"></i>
                            <input type="text" name="new_username" required class="prof-input" value="<?= htmlspecialchars($admin_data['username']) ?>" placeholder="Nhập tên đăng nhập">
                        </div>
                    </div>
                    <div>
                        <div class="prof-meta-label">MẬT KHẨU MỚI (Bỏ trống nếu không đổi)</div>
                        <div class="prof-input-wrap">
                            <i class="fa-solid fa-key"></i>
                            <input type="password" name="new_password" class="prof-input" placeholder="Nhập mật khẩu mới">
                        </div>
                    </div>
                </div>
                <div class="prof-footer" style="margin-top: 10px; padding-top: 0; border: none; justify-content: flex-end;">
                    <button type="submit" class="prof-btn-save" style="background:var(--text); color:var(--bg2); border:1px solid var(--border);"><i class="fa-solid fa-shield-halved"></i> CẬP NHẬT TÀI KHOẢN & MẬT KHẨU</button>
                </div>
            </form>
        </div>

        <!-- Khuôn mặt -->
        <div class="prof-card">
            <div class="prof-title" style="margin-bottom: 20px;">
                BẢO MẬT KHUÔN MẶT QTV
                <?php if ($hasFace): ?>
                <span class="badge-face-yes"><i class="fa-solid fa-circle-check"></i> Đã đăng ký</span>
                <?php else: ?>
                <span class="badge-face-no"><i class="fa-solid fa-circle-xmark"></i> Chưa đăng ký</span>
                <?php endif; ?>
            </div>
            
            <p style="font-size:13px;color:var(--text2);margin-bottom:20px; line-height: 1.5;">Đăng ký khuôn mặt để đăng nhập vào trang quản trị nhanh chóng, an toàn không cần mật khẩu.</p>
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
              <button class="prof-btn-save" style="background:var(--accent);" id="btnStart" onclick="startCamera()"><i class="fa-solid fa-camera"></i> Bắt đầu quét</button>
              <button class="prof-btn-save" style="background:var(--accent2); display:none;" id="btnCapture" onclick="captureface()"><i class="fa-solid fa-expand"></i> Chụp khuôn mặt</button>
              <button class="prof-btn-save" style="background:var(--success); display:none;" id="btnSave" onclick="saveFace()"><i class="fa-solid fa-floppy-disk"></i> Lưu khuôn mặt</button>
              <button class="prof-btn-save" style="background:var(--text); display:none;" onclick="stopCamera()" id="btnStop"><i class="fa-solid fa-stop"></i> Dừng</button>
            </div>
        </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
let stream = null, modelsLoaded = false, capturedDesc = null;
const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';

async function startCamera() {
  const status = document.getElementById('faceStatus');
  status.textContent = 'Đang tải AI Core...';
  if (!modelsLoaded) {
    try {
      await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
      await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
      await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
      modelsLoaded = true;
    } catch(e) { status.textContent = 'Lỗi AI: ' + e.message; return; }
  }
  stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
  document.getElementById('regVideo').srcObject = stream;
  document.getElementById('scanAnim').classList.add('on');
  status.textContent = 'SYSTEM READY. Look at the camera.';
  document.getElementById('btnStart').style.display   = 'none';
  document.getElementById('btnCapture').style.display = 'inline-flex';
  document.getElementById('btnStop').style.display    = 'inline-flex';
}

async function captureface() {
  const video  = document.getElementById('regVideo');
  const canvas = document.getElementById('regCanvas');
  const status = document.getElementById('faceStatus');
  status.textContent = 'Scanning face...';
  const opts = new faceapi.TinyFaceDetectorOptions({ inputSize:224, scoreThreshold:0.5 });
  const det  = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
  if (!det) { status.textContent = '✗ Access Denied! No face detected.'; return; }
  const dims = faceapi.matchDimensions(canvas, video, true);
  faceapi.draw.drawDetections(canvas, faceapi.resizeResults(det, dims));
  faceapi.draw.drawFaceLandmarks(canvas, faceapi.resizeResults(det, dims));
  capturedDesc = Array.from(det.descriptor);
  document.getElementById('faceCaptured').style.display = 'block';
  document.getElementById('btnSave').style.display      = 'inline-flex';
  status.textContent = '✓ Scan complete. Identity verified.';
}

function saveFace() {
  if (!capturedDesc) return;
  document.getElementById('faceDescInput').value = JSON.stringify(capturedDesc);
  document.getElementById('faceForm').submit();
}

function stopCamera() {
  if (stream) { stream.getTracks().forEach(t=>t.stop()); stream=null; }
  document.getElementById('scanAnim').classList.remove('on');
  document.getElementById('faceStatus').textContent = 'Camera offline.';
  document.getElementById('btnStart').style.display   = 'inline-flex';
  document.getElementById('btnCapture').style.display = 'none';
  document.getElementById('btnStop').style.display    = 'none';
}
</script>
</body>
</html>
