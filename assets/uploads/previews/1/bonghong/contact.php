<?php
require_once 'config.php';

$success_msg = '';
$error_msg = '';

// Fetch shop contact settings
try {
    $stmt = $pdo->query("SELECT * FROM Settings LIMIT 1");
    $settings = $stmt->fetch();
} catch (PDOException $e) {
    $settings = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($fullname) || empty($message)) {
        $error_msg = 'Họ tên và Lời nhắn là các trường bắt buộc.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Contacts (fullname, email, phone, message, status) VALUES (?, ?, ?, ?, 'Chưa trả lời')");
            $stmt->execute([$fullname, $email, $phone, $message]);
            
            $_SESSION['toast'] = [
                'message' => 'Gửi tin nhắn liên hệ thành công. Chúng tôi sẽ phản hồi sớm nhất!',
                'type' => 'success'
            ];
            redirect('contact.php');
        } catch (PDOException $e) {
            $error_msg = 'Gửi liên hệ thất bại: ' . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="container">
    <h1 class="section-title">Liên Hệ Với Chúng Tôi</h1>
    
    <div class="contact-layout">
        <!-- Contact details -->
        <div class="contact-info-panel">
            <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--primary-dark); margin-bottom: 16px;">Thông tin liên lạc</h2>
            <p style="color: var(--text-muted);">Hãy ghé thăm hoặc liên hệ với chúng tôi qua các kênh sau để nhận được dịch vụ cắm hoa tốt nhất Cà Mau.</p>
            
            <div class="contact-info-list">
                <?php if ($settings): ?>
                    <div class="contact-info-item">
                        <h4>Địa chỉ cửa hàng</h4>
                        <p><?= sanitize($settings['address']) ?></p>
                    </div>
                    
                    <div class="contact-info-item">
                        <h4>Số điện thoại</h4>
                        <p><?= sanitize($settings['phone']) ?></p>
                    </div>
                    
                    <div class="contact-info-item">
                        <h4>Địa chỉ Email</h4>
                        <p><?= sanitize($settings['email']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Contact submission form -->
        <div>
            <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--primary-dark); margin-bottom: 16px;">Gửi tin nhắn cho Roselia</h2>
            
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: var(--primary-light); color: var(--danger); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 500; font-size: 0.9rem;">
                    <?= sanitize($error_msg) ?>
                </div>
            <?php endif; ?>
            
            <form action="contact.php" method="POST">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="fullname">Họ tên của bạn <span style="color: var(--danger)">*</span></label>
                    <input type="text" id="fullname" name="fullname" class="input-control" required placeholder="Họ và tên">
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="email">Địa chỉ Email</label>
                    <input type="email" id="email" name="email" class="input-control" placeholder="example@mail.com">
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="phone">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone" class="input-control" placeholder="09xxxxxxxx">
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="message">Nội dung tin nhắn <span style="color: var(--danger)">*</span></label>
                    <textarea id="message" name="message" rows="5" class="input-control" required style="resize: vertical;" placeholder="Nhập lời nhắn hoặc câu hỏi của bạn tại đây..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="padding: 12px 30px;">Gửi tin nhắn</button>
            </form>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>
