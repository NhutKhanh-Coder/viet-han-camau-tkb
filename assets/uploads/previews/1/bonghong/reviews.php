<?php
require_once 'config.php';

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
    redirect('reviews.php');
}

// Fetch all approved reviews
try {
    $stmt = $pdo->query("SELECT r.*, u.fullname, p.product_name, p.image 
                         FROM Reviews r 
                         JOIN Users u ON r.user_id = u.id 
                         JOIN Products p ON r.product_id = p.id 
                         WHERE r.status = 1 
                         ORDER BY r.review_date DESC");
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}

include 'header.php';
?>

<div class="container" style="max-width: 900px;">
    <h1 class="section-title">Khách Hàng Đánh Giá</h1>
    
    <div class="reviews-list" style="background: var(--bg-white); border-radius: var(--radius-lg); padding: 40px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $rev): ?>
                <div class="review-card" style="display: flex; gap: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color); margin-bottom: 24px;">
                    <img src="<?= sanitize($rev['image'] ?: 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600') ?>" 
                         alt="<?= sanitize($rev['product_name']) ?>" 
                         style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm);">
                         
                    <div style="flex: 1;">
                        <div class="review-header">
                            <div>
                                <span class="review-author" style="font-size: 1rem; color: var(--primary-dark);"><?= sanitize($rev['fullname']) ?></span>
                                <span style="font-size: 0.85rem; color: var(--text-muted);"> đã đánh giá sản phẩm </span>
                                <a href="detail.php?id=<?= $rev['product_id'] ?>" style="font-weight: 600; color: var(--primary); text-decoration: underline;">
                                    <?= sanitize($rev['product_name']) ?>
                                </a>
                            </div>
                            <div class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?= $i <= $rev['rating'] ? '&#9733;' : '&#9734;' ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-date" style="margin-top: 4px;">
                            <?= date('d/m/Y H:i', strtotime($rev['review_date'])) ?>
                            <?php if (isAdmin()): ?>
                                <a href="reviews.php?action=delete_review&id=<?= $rev['id'] ?>" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?');" 
                                   style="color: var(--danger); font-size: 0.85rem; margin-left: 12px; font-weight: 600;">
                                    [Xóa đánh giá]
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="review-comment" style="margin-top: 12px; font-style: italic; font-size: 0.95rem;">
                            "<?= sanitize($rev['comment'] ?: 'Không có bình luận.') ?>"
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); font-style: italic;">Chưa có đánh giá nào trên hệ thống.</p>
        <?php endif; ?>
    </div>
</div>

<?php
include 'footer.php';
?>
