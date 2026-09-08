<?php
require_once 'config.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    redirect('index.php');
}

// Fetch product details
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM Products p 
                           JOIN Category_name c ON p.category_id = c.id 
                           WHERE p.id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    $product = null;
}

if (!$product) {
    $_SESSION['toast'] = [
        'message' => 'Sản phẩm không tồn tại.',
        'type' => 'error'
    ];
    redirect('index.php');
}

// Delete Review action for Admin
if (isAdmin() && isset($_GET['action']) && $_GET['action'] === 'delete_review') {
    $review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($review_id > 0) {
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM Reviews WHERE id = ?");
            $deleteStmt->execute([$review_id]);
            $_SESSION['toast'] = [
                'message' => 'Đã xóa đánh giá thành công.',
                'type' => 'success'
            ];
        } catch (PDOException $e) {
            $_SESSION['toast'] = [
                'message' => 'Xóa đánh giá thất bại: ' . $e->getMessage(),
                'type' => 'error'
            ];
        }
    }
    redirect("detail.php?id=$product_id");
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    if (!isLoggedIn()) {
        $_SESSION['toast'] = [
            'message' => 'Vui lòng đăng nhập để gửi đánh giá.',
            'type' => 'error'
        ];
        redirect("auth.php");
    }
    
    $user_id = $_SESSION['user_id'];
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }
    
    try {
        $reviewStmt = $pdo->prepare("INSERT INTO Reviews (user_id, product_id, rating, comment, status) VALUES (?, ?, ?, ?, 1)");
        $reviewStmt->execute([$user_id, $product_id, $rating, $comment]);
        
        $_SESSION['toast'] = [
            'message' => 'Cảm ơn bạn đã gửi đánh giá sản phẩm!',
            'type' => 'success'
        ];
    } catch (PDOException $e) {
        $_SESSION['toast'] = [
            'message' => 'Gửi đánh giá thất bại: ' . $e->getMessage(),
            'type' => 'error'
        ];
    }
    
    redirect("detail.php?id=$product_id");
}

// Fetch Reviews for this product
try {
    $reviewListStmt = $pdo->prepare("SELECT r.*, u.fullname FROM Reviews r 
                                     JOIN Users u ON r.user_id = u.id 
                                     WHERE r.product_id = ? AND r.status = 1 
                                     ORDER BY r.review_date DESC");
    $reviewListStmt->execute([$product_id]);
    $reviews = $reviewListStmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}

include 'header.php';
?>

<!-- Breadcrumb -->
<div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 24px;">
    <a href="index.php" style="color: var(--primary);">Trang chủ</a> / 
    <a href="index.php?cat=<?= $product['category_id'] ?>" style="color: var(--primary);"><?= sanitize($product['category_name']) ?></a> / 
    <span><?= sanitize($product['product_name']) ?></span>
</div>

<div class="detail-container">
    <!-- Image Gallery (Câu 4 Left Column) -->
    <div class="gallery-container">
        <div class="main-image-view">
            <img id="main-product-image" 
                 src="<?= sanitize($product['image'] ?: 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600') ?>" 
                 alt="<?= sanitize($product['product_name']) ?>">
        </div>
        
        <div class="thumbnail-row">
            <?php if (!empty($product['image'])): ?>
                <div class="thumb-item active">
                    <img src="<?= sanitize($product['image']) ?>" alt="Thumbnail 1">
                </div>
            <?php endif; ?>
            <?php if (!empty($product['image2'])): ?>
                <div class="thumb-item">
                    <img src="<?= sanitize($product['image2']) ?>" alt="Thumbnail 2">
                </div>
            <?php endif; ?>
            <?php if (!empty($product['image3'])): ?>
                <div class="thumb-item">
                    <img src="<?= sanitize($product['image3']) ?>" alt="Thumbnail 3">
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Info (Câu 4 Right Column) -->
    <div class="detail-info">
        <h1 class="detail-name"><?= sanitize($product['product_name']) ?></h1>
        <div class="detail-price"><?= formatPrice($product['price']) ?></div>
        
        <div class="detail-meta-list">
            <div class="detail-meta-item">
                <span class="meta-label">Danh mục:</span>
                <span class="meta-value"><?= sanitize($product['category_name']) ?></span>
            </div>
            
            <div class="detail-meta-item">
                <span class="meta-label">Số lượng:</span>
                <span class="meta-value"><?= sanitize($product['quantity']) ?> sản phẩm</span>
            </div>
            
            <div class="detail-meta-item">
                <span class="meta-label">Tình trạng:</span>
                <span class="meta-value">
                    <?php if ($product['status'] === 'Còn hàng' && $product['quantity'] > 0): ?>
                        <span style="color: var(--success); font-weight: 600;">Còn hàng</span>
                    <?php else: ?>
                        <span style="color: var(--danger); font-weight: 600;">Hết hàng</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-meta-item" style="flex-direction: column; gap: 8px;">
                <span class="meta-label">Mô tả sản phẩm:</span>
                <span class="meta-value" style="font-weight: 400; line-height: 1.7;"><?= nl2br(sanitize($product['description'])) ?></span>
            </div>
            
            <?php if (!empty($product['flower_meaning'])): ?>
                <div class="detail-meta-item" style="flex-direction: column; gap: 8px; border-top: 1px dashed var(--border-color); padding-top: 16px; margin-top: 8px;">
                    <span class="meta-label">Ý nghĩa loài hoa:</span>
                    <span class="meta-value" style="font-weight: 400; line-height: 1.7; font-style: italic; color: var(--primary-dark);"><?= nl2br(sanitize($product['flower_meaning'])) ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="detail-actions">
            <?php if ($product['status'] === 'Còn hàng' && $product['quantity'] > 0): ?>
                <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="btn btn-secondary">Thêm vào giỏ hàng</a>
                <a href="cart.php?action=buy_now&id=<?= $product['id'] ?>" class="btn btn-primary">Mua ngay</a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">Hết hàng</button>
                <button class="btn btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">Mua ngay</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Product Reviews (Câu 4 Reviews section) -->
<div class="reviews-section">
    <h2 class="reviews-title">Đánh giá sản phẩm</h2>
    
    <!-- Submit Review Form -->
    <div class="review-form-box">
        <h3 style="font-size: 1.1rem; color: var(--primary-dark); margin-bottom: 12px; font-weight: 600;">Gửi đánh giá của bạn</h3>
        
        <?php if (isLoggedIn()): ?>
            <form action="detail.php?id=<?= $product['id'] ?>" method="POST">
                <input type="hidden" name="action" value="add_review">
                <input type="hidden" name="rating" id="rating-value" value="5">
                
                <div class="rating-picker">
                    <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-dark);">Số sao:</span>
                    <button class="star-btn active" data-value="1">&#9733;</button>
                    <button class="star-btn active" data-value="2">&#9733;</button>
                    <button class="star-btn active" data-value="3">&#9733;</button>
                    <button class="star-btn active" data-value="4">&#9733;</button>
                    <button class="star-btn active" data-value="5">&#9733;</button>
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="comment" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 6px;">Nhận xét:</label>
                    <textarea name="comment" id="comment" rows="4" class="input-control" style="width: 100%; resize: vertical;" placeholder="Nhập chia sẻ của bạn về sản phẩm hoa tươi tại đây..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
            </form>
        <?php else: ?>
            <p style="font-size: 0.95rem; color: var(--text-muted); font-style: italic;">
                Vui lòng <a href="auth.php" style="color: var(--primary); font-weight: 600; text-decoration: underline;">Đăng nhập</a> hoặc <a href="auth.php#register" style="color: var(--primary); font-weight: 600; text-decoration: underline;">Đăng ký</a> để gửi đánh giá sản phẩm.
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Customer Reviews List -->
    <h3 style="font-size: 1.2rem; color: var(--primary-dark); margin-bottom: 20px; font-weight: 600;">Đánh giá khách hàng</h3>
    <div class="reviews-list">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $rev): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="review-author"><?= sanitize($rev['fullname']) ?></span>
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?= $i <= $rev['rating'] ? '&#9733;' : '&#9734;' ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="review-date">
                        <?= date('d/m/Y H:i', strtotime($rev['review_date'])) ?>
                        <?php if (isAdmin()): ?>
                            <a href="detail.php?id=<?= $product_id ?>&action=delete_review&id=<?= $rev['id'] ?>" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?');" 
                               style="color: var(--danger); font-size: 0.85rem; margin-left: 12px; font-weight: 600;">
                                [Xóa đánh giá]
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="review-comment" style="margin-top: 8px; font-style: italic; line-height: 1.6;">
                        "<?= sanitize($rev['comment'] ?: 'Không có bình luận.') ?>"
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: var(--text-muted); font-style: italic;" class="no-reviews">Chưa có đánh giá nào cho sản phẩm này.</p>
        <?php endif; ?>
    </div>
</div>

<?php
include 'footer.php';
?>
