<?php
require_once '../config.php';
requirePrincipal();
$db   = getDB();
$uid = $_SESSION['user_id'];
$msg  = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_face') {
        $desc = trim($_POST['face_descriptor'] ?? '');
        if ($desc) {
            $st = $db->prepare("UPDATE users SET face_descriptor=? WHERE id=?");
            $st->bind_param("si", $desc, $uid);
            $st->execute();
            $msg = 'success:Đã lưu dữ liệu khuôn mặt thành công!';
        } else { $msg = 'error:Không có dữ liệu khuôn mặt!'; }
    }
    if ($_POST['action'] === 'update_info') {
        $email = trim($_POST['email'] ?? '');
        $sdt   = trim($_POST['sdt'] ?? '');
        $ho_ten = trim($_POST['ho_ten'] ?? '');
        $st = $db->prepare("UPDATE users SET email=?, sdt=?, ho_ten=? WHERE id=?");
        $st->bind_param("sssi", $email, $sdt, $ho_ten, $uid);
        if ($st->execute()) {
            $_SESSION['ho_ten'] = $ho_ten;
            $msg = 'success:Cập nhật thông tin thành công!';
        } else {
            $msg = 'error:Lỗi hệ thống khi cập nhật.';
        }
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
        }
    }
    if ($_POST['action'] === 'upload_avatar') {
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newFilename = "principal_avatar_" . $uid . "_" . time() . "." . $ext;
                $destDir = "../assets/img/avatars/";
                if (!is_dir($destDir)) { mkdir($destDir, 0777, true); }
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $destDir . $newFilename)) {
                    $db->query("UPDATE users SET avatar='$newFilename' WHERE id=$uid");
                    $_SESSION['avatar'] = $newFilename;
                    $msg = 'success:Tải ảnh đại diện thành công!';
                } else { $msg = 'error:Không thể lưu tệp tin.'; }
            } else { $msg = 'error:Định dạng ảnh không hợp lệ.'; }
        } else { $msg = 'error:Lỗi khi tải lên.'; }
    }
}

// Fetch user details
$st2 = $db->prepare("SELECT * FROM users WHERE id=?");
$st2->bind_param("i", $uid);
$st2->execute();
$user = $st2->get_result()->fetch_assoc();
$hasFace = !empty($user['face_descriptor']);
$db->close();

$msgType = ''; $msgText = '';
if ($msg) { [$msgType, $msgText] = explode(':', $msg, 2); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Hồ Sơ Ban Giám Hiệu</title>
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
.prof-avatar-btn { position: absolute; bottom: 5px; right: 15px; background: #fbbf24; color: #000; width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.15); transition: background 0.2s; }
.prof-avatar-btn:hover { background: #fbbf24; opacity: 0.9; }
.prof-name { text-align: center; font-size: 24px; font-family: 'Playfair Display', serif; font-weight: 800; margin-bottom: 5px; color: var(--text); }
.prof-msv { text-align: center; font-size: 11px; font-weight: 700; color: var(--text2); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
.prof-pill-blue { background: rgba(251, 191, 36, 0.08); color: #fbbf24; padding: 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-align: center; display: block; text-decoration: none; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid rgba(251, 191, 36, 0.12); }
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
.prof-input:focus { border-color: #fbbf24 !important; background: var(--bg2) !important; box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.08); }
.prof-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border); }
.prof-notice { font-size: 11px; color: var(--text2); display: flex; align-items: flex-start; gap: 8px; max-width: 60%; line-height: 1.5; }
.prof-notice i { color: #fbbf24; font-size: 14px; }
.prof-btn-save { background: #fbbf24; color: #000; border: none; padding: 12px 25px; border-radius: var(--r-sm); font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; font-family: 'Outfit', sans-serif; }
.prof-btn-save:hover { transform: translateY(-1px); opacity: 0.95; }

.badge-face-yes { background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid #10b981; }
.badge-face-no { background: rgba(251, 191, 36, 0.1); color: #fbbf24; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; float: right; border: 1px solid rgba(251, 191, 36, 0.15); }
</style>
</head>
<body>
<?php include '../includes/principal_nav.php'; ?>
  <div class="page-header">
    <div><h1 class="page-title"><i class="fa-solid fa-id-card" style="color:#fbbf24"></i> Hồ Sơ Cá Nhân</h1></div>
  </div>

  <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType ?>" style="background:<?= $msgType=='success'?'rgba(16, 185, 129, 0.1)':'rgba(239, 68, 68, 0.1)' ?>; border: 1px solid <?= $msgType=='success'?'#10b981':'#ef4444' ?>; color: <?= $msgType=='success'?'#059669':'#dc2626' ?>; padding: 12px; border-radius: 10px; margin-bottom: 20px;">
        <?= htmlspecialchars($msgText) ?>
    </div>
  <?php endif; ?>

  <div class="profile-grid">
    <!-- Left Column: Avatar & Meta -->
    <div class="prof-card">
        <div class="prof-avatar-wrap">
            <?php $avatar_url = !empty($user['avatar']) ? '/tkb/assets/img/avatars/' . $user['avatar'] : '/tkb/assets/img/logo_vkc.jpg'; ?>
            <img src="<?= $avatar_url ?>" class="prof-avatar" alt="Avatar">
            <button class="prof-avatar-btn" onclick="document.getElementById('avatarInput').click()"><i class="fa-solid fa-camera"></i></button>
            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="action" value="upload_avatar">
                <input type="file" name="avatar_file" id="avatarInput" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
            </form>
        </div>
        <div class="prof-name"><?= htmlspecialchars($user['ho_ten'] ?? 'Chưa cập nhật') ?></div>
        <div class="prof-msv">Tên đăng nhập: <?= htmlspecialchars($user['username']) ?></div>
        
        <div class="prof-pill-blue"><i class="fa-solid fa-user-tie"></i> Chức vụ: Hiệu Trưởng</div>
        <div class="prof-pill-gray">BAN GIÁM HIỆU NHÀ TRƯỜNG</div>
        
        <div class="prof-divider"></div>
        
        <div class="prof-meta-item">
            <div class="prof-meta-label">PHÂN QUYỀN</div>
            <div class="prof-meta-val">Giám sát toàn trường</div>
        </div>
    </div>

    <!-- Right Column: Form & Face -->
    <div class="prof-card">
        <h3 class="prof-title"><i class="fa-solid fa-gears" style="color:#fbbf24; margin-right:8px;"></i> Thông Tin & Bảo Mật</h3>
        
        <form method="POST" style="margin-bottom: 40px;">
            <input type="hidden" name="action" value="update_info">
            <div class="prof-form-row">
                <div class="prof-input-wrap">
                    <label style="font-size:11px; font-weight:700; color:var(--text2); display:block; margin-bottom:8px;">HỌ VÀ TÊN</label>
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="ho_ten" class="prof-input" placeholder="Họ và tên hiệu trưởng" value="<?= htmlspecialchars($user['ho_ten'] ?? '') ?>" required>
                </div>
            </div>
            <div class="prof-form-row">
                <div class="prof-input-wrap">
                    <label style="font-size:11px; font-weight:700; color:var(--text2); display:block; margin-bottom:8px;">ĐỊA CHỈ EMAIL</label>
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" class="prof-input" placeholder="Nhập email của bạn" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                </div>
                <div class="prof-input-wrap">
                    <label style="font-size:11px; font-weight:700; color:var(--text2); display:block; margin-bottom:8px;">SỐ ĐIỆN THOẠI</label>
                    <i class="fa-solid fa-phone"></i>
                    <input type="text" name="sdt" class="prof-input" placeholder="Nhập số điện thoại" value="<?= htmlspecialchars($user['sdt'] ?? '') ?>">
                </div>
            </div>
            
            <div style="text-align: right;">
                <button type="submit" class="prof-btn-save"><i class="fa-solid fa-circle-check"></i> Cập Nhật Hồ Sơ</button>
            </div>
        </form>

        <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">

        <!-- Đổi Mật Khẩu -->
        <h3 class="prof-title"><i class="fa-solid fa-key" style="color:#fbbf24; margin-right:8px;"></i> Đổi Mật Khẩu</h3>
        <form method="POST" onsubmit="return validateProfilePasswords()" style="margin-bottom: 40px;">
            <input type="hidden" name="action" value="change_password">
            <div class="prof-form-row">
                <div class="prof-input-wrap">
                    <label style="font-size:11px; font-weight:700; color:var(--text2); display:block; margin-bottom:8px;">Mật khẩu hiện tại</label>
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="current_password" name="current_password" class="prof-input" placeholder="Nhập mật khẩu hiện tại" required>
                </div>
            </div>
            <div class="prof-form-row">
                <div class="prof-input-wrap">
                    <label style="font-size:11px; font-weight:700; color:var(--text2); display:block; margin-bottom:8px;">Mật khẩu mới</label>
                    <i class="fa-solid fa-key"></i>
                    <input type="password" id="new_password" name="new_password" class="prof-input" placeholder="Tối thiểu 6 ký tự" required>
                </div>
                <div class="prof-input-wrap">
                    <label style="font-size:11px; font-weight:700; color:var(--text2); display:block; margin-bottom:8px;">Nhập lại mật khẩu mới</label>
                    <i class="fa-solid fa-key"></i>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" class="prof-input" placeholder="Xác nhận mật khẩu mới" required>
                </div>
            </div>
            <div style="text-align: right;">
                <button type="submit" class="prof-btn-save"><i class="fa-solid fa-key"></i> Đổi Mật Khẩu</button>
            </div>
        </form>

        <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">

        <!-- Face ID section -->
        <h3 class="prof-title" style="margin-top:20px;">
            <i class="fa-solid fa-face-viewfinder" style="color:#fbbf24; margin-right:8px;"></i> Face ID Đăng Nhập
            <?php if ($hasFace): ?>
                <span class="badge-face-yes"><i class="fa-solid fa-shield-check"></i> Đang hoạt động</span>
            <?php else: ?>
                <span class="badge-face-no"><i class="fa-solid fa-shield-xmark"></i> Chưa đăng ký</span>
            <?php endif; ?>
        </h3>
        
        <p style="font-size:13px; color:var(--text2); line-height:1.6; margin-bottom:20px;">
            Đăng ký khuôn mặt giúp bạn đăng nhập nhanh chóng và bảo mật cao bằng camera của thiết bị mà không cần điền mật khẩu thông thường.
        </p>

        <div style="text-align: center;">
            <button class="btn btn-primary" onclick="openCamera()"><i class="fa-solid fa-camera"></i> Bắt đầu Quét & Đăng ký</button>
        </div>

        <div id="cameraModal" style="display:none; margin-top:20px; border-top:1px solid var(--border); padding-top:20px;">
            <div class="video-box">
                <video id="regVideo" autoplay muted playsinline></video>
                <canvas id="regCanvas"></canvas>
                <div class="scan-anim" id="scanAnim"></div>
            </div>
            <div class="face-status" id="faceStatus">Đang chờ khởi động camera...</div>
            <div class="face-captured" id="faceCaptured">Đã chụp thành công 3 ảnh!</div>
            
            <form id="faceForm" method="POST" style="display:none;">
                <input type="hidden" name="action" value="save_face">
                <input type="hidden" name="face_descriptor" id="faceDescriptorInput">
            </form>
        </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
let video = document.getElementById('regVideo');
let canvas = document.getElementById('regCanvas');
let status = document.getElementById('faceStatus');
let scanAnim = document.getElementById('scanAnim');
let stream = null;
let interval = null;
let modelsLoaded = false;

async function loadModels() {
    if (modelsLoaded) return true;
    status.textContent = 'Đang tải mô hình trí tuệ nhân tạo (AI)...';
    try {
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        modelsLoaded = true;
        return true;
    } catch (e) {
        status.textContent = 'Lỗi tải AI models: ' + e.message;
        return false;
    }
}

async function openCamera() {
    document.getElementById('cameraModal').style.display = 'block';
    let ok = await loadModels();
    if (!ok) return;

    status.textContent = 'Đang mở camera...';
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        video.srcObject = stream;
        await new Promise(r => video.onloadedmetadata = r);
        scanAnim.classList.add('on');
        detectFace();
    } catch (e) {
        status.textContent = 'Không thể kết nối camera: ' + e.message;
    }
}

async function detectFace() {
    status.textContent = 'Hãy giữ thẳng khuôn mặt trước màn hình...';
    let holdCount = 0;
    let detecting = false;

    interval = setInterval(async () => {
        if (detecting) return;
        detecting = true;

        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
        const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks().withFaceDescriptor();

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (detection) {
            const dims = faceapi.matchDimensions(canvas, video, true);
            const resized = faceapi.resizeResults(detection, dims);
            faceapi.draw.drawDetections(canvas, resized);
            faceapi.draw.drawFaceLandmarks(canvas, resized);

            holdCount++;
            status.textContent = 'Đang phân tích khuôn mặt... (' + holdCount + '/3)';

            if (holdCount >= 3) {
                clearInterval(interval);
                status.textContent = 'Chụp khuôn mặt thành công! Đang lưu...';
                document.getElementById('faceDescriptorInput').value = JSON.stringify(Array.from(detection.descriptor));
                
                // Stop camera
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                }
                
                document.getElementById('faceForm').submit();
            }
        } else {
            holdCount = 0;
            status.textContent = 'Không tìm thấy khuôn mặt. Hãy nhìn thẳng vào camera.';
        }
        detecting = false;
    }, 500);
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
