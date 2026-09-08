<?php
require_once 'config.php';

$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($news_id > 0) {
    // Show single news article
    try {
        $stmt = $pdo->prepare("SELECT * FROM News WHERE id = ? LIMIT 1");
        $stmt->execute([$news_id]);
        $article = $stmt->fetch();
    } catch (PDOException $e) {
        $article = null;
    }
} else {
    // Show list of news articles
    try {
        $stmt = $pdo->query("SELECT * FROM News ORDER BY created_at DESC");
        $newsList = $stmt->fetchAll();
    } catch (PDOException $e) {
        $newsList = [];
    }
}

include 'header.php';
?>

<div class="container" style="max-width: 900px;">
    <?php if ($news_id > 0 && $article): ?>
        <!-- Single Article View -->
        <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 24px;">
            <a href="index.php" style="color: var(--primary);">Trang chủ</a> / 
            <a href="news.php" style="color: var(--primary);">Tin tức</a> / 
            <span><?= sanitize($article['title']) ?></span>
        </div>
        
        <div style="background: var(--bg-white); border-radius: var(--radius-lg); padding: 40px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
            <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--primary-dark); line-height: 1.3; margin-bottom: 12px;"><?= sanitize($article['title']) ?></h1>
            
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                Đăng ngày <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?>
            </div>
            
            <?php if (!empty($article['thumbnail'])): ?>
                <img src="<?= sanitize($article['thumbnail']) ?>" alt="<?= sanitize($article['title']) ?>" style="width: 100%; max-height: 450px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: 30px; border: 1px solid var(--border-color);">
            <?php endif; ?>
            
            <div style="font-size: 1.05rem; line-height: 1.8; color: var(--text-dark); text-align: justify; white-space: pre-line;">
                <?= sanitize($article['content']) ?>
            </div>
            
            <div style="margin-top: 40px; border-top: 1px solid var(--border-color); padding-top: 24px;">
                <a href="news.php" class="btn btn-secondary">Quay lại tin tức</a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- List View -->
        <h1 class="section-title">Tin Tức - Chia Sẻ Kinh Nghiệm</h1>
        
        <div style="display: flex; flex-direction: column; gap: 32px;">
            <?php if (count($newsList) > 0): ?>
                <?php foreach ($newsList as $item): ?>
                    <div style="display: grid; grid-template-columns: 250px 1fr; gap: 24px; background: var(--bg-white); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); transition: var(--transition-normal);" class="product-card">
                        <img src="<?= sanitize($item['thumbnail'] ?: 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600') ?>" 
                             alt="<?= sanitize($item['title']) ?>" 
                             style="width: 100%; height: 100%; min-height: 180px; object-fit: cover;">
                             
                        <div style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h2 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--primary-dark); margin-bottom: 8px;">
                                    <a href="news.php?id=<?= $item['id'] ?>" style="color: inherit;"><?= sanitize($item['title']) ?></a>
                                </h2>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></p>
                                <p style="font-size: 0.95rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6; text-overflow: ellipsis; margin-bottom: 16px;">
                                    <?= sanitize($item['content']) ?>
                                </p>
                            </div>
                            <a href="news.php?id=<?= $item['id'] ?>" class="btn btn-secondary" style="align-self: flex-start; padding: 6px 16px; font-size: 0.85rem;">Đọc thêm</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted); font-style: italic;">Chưa có bài viết tin tức nào đăng trên hệ thống.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
include 'footer.php';
?>
