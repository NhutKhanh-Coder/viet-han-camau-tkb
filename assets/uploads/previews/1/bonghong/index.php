<?php
require_once 'config.php';

// Pagination settings
$limit = 8;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter settings
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_id = isset($_GET['cat_id']) ? $_GET['cat_id'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Handle specific URL params
// If $_GET['cat'] is set to 'all' or empty, we show all, else filter by categories
if (isset($_GET['cat']) && $_GET['cat'] !== 'all') {
    // If we want categories filtering via a quick cat link
    if (is_numeric($_GET['cat'])) {
        $cat_id = (int)$_GET['cat'];
    }
}

// Fetch all categories for filter dropdown
try {
    $catStmt = $pdo->query("SELECT * FROM Category_name ORDER BY category_name ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Build query
$queryStr = "SELECT p.*, c.category_name FROM Products p 
             JOIN Category_name c ON p.category_id = c.id 
             WHERE 1=1";
$params = [];

if ($search !== '') {
    $queryStr .= " AND p.product_name LIKE :search";
    $params['search'] = '%' . $search . '%';
}

if ($cat_id !== '' && $cat_id !== 'all') {
    $queryStr .= " AND p.category_id = :cat_id";
    $params['cat_id'] = $cat_id;
}

// Get total count for pagination before sorting and limit
try {
    $countQuery = str_replace("SELECT p.*, c.category_name", "SELECT COUNT(*)", $queryStr);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total_items = $countStmt->fetchColumn();
} catch (PDOException $e) {
    $total_items = 0;
}

$total_pages = ceil($total_items / $limit);
$page = min($page, max(1, $total_pages)); // ensure page is in bounds
$offset = ($page - 1) * $limit;

// Apply sorting
switch ($sort) {
    case 'price_asc':
        $queryStr .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $queryStr .= " ORDER BY p.price DESC";
        break;
    case 'newest':
        $queryStr .= " ORDER BY p.created_at DESC";
        break;
    case 'name_asc':
        $queryStr .= " ORDER BY p.product_name ASC";
        break;
    default:
        $queryStr .= " ORDER BY p.id ASC";
        break;
}

// Apply limit & offset
$queryStr .= " LIMIT :limit OFFSET :offset";

// Execute products fetch
try {
    $stmt = $pdo->prepare($queryStr);
    // Bind limit & offset as integers for PDO
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        $stmt->bindValue(':' . $key, $val);
    }
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    die("Query error: " . $e->getMessage());
}

include 'header.php';
?>

<div class="container">
    <h1 class="section-title">Sản Phẩm Hoa Tươi</h1>

    <!-- Filter Control Bar (Câu 2 Mockup) -->
    <form action="index.php" method="GET" class="filter-bar">
        <div class="filter-group">
            <input type="text" name="search" value="<?= sanitize($search) ?>" class="input-control" placeholder="Tìm sản phẩm...">
            
            <select name="cat_id" class="select-control">
                <option value="all">-- Tất cả danh mục --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>>
                        <?= sanitize($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="sort" class="select-control">
                <option value="default" <?= $sort == 'default' ? 'selected' : '' ?>>Sắp xếp mặc định</option>
                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá từ thấp đến cao</option>
                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá từ cao đến thấp</option>
                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Tên A - Z</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
    </form>

    <!-- Products Grid (Câu 2 Mockup) -->
    <?php if (count($products) > 0): ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <!-- Badge for stock -->
                    <?php if ($product['status'] === 'Hết hàng' || $product['quantity'] <= 0): ?>
                        <span class="product-badge out-of-stock">Hết hàng</span>
                    <?php endif; ?>
                    
                    <div class="product-img-wrapper">
                        <a href="detail.php?id=<?= $product['id'] ?>">
                            <img src="<?= sanitize($product['image'] ?: 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600') ?>" 
                                 alt="<?= sanitize($product['product_name']) ?>" 
                                 class="product-img">
                        </a>
                    </div>
                    
                    <div class="product-info">
                        <span class="product-category"><?= sanitize($product['category_name']) ?></span>
                        <h3 class="product-name">
                            <a href="detail.php?id=<?= $product['id'] ?>"><?= sanitize($product['product_name']) ?></a>
                        </h3>
                        <p class="product-price"><?= formatPrice($product['price']) ?></p>
                        
                        <div class="product-actions">
                            <a href="detail.php?id=<?= $product['id'] ?>" class="btn btn-secondary">Chi tiết</a>
                            
                            <?php if ($product['status'] === 'Còn hàng' && $product['quantity'] > 0): ?>
                                <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="btn btn-primary">Thêm giỏ</a>
                            <?php else: ?>
                                <button class="btn btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">Thêm giỏ</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php
                    // Build query string for page links
                    $linkParams = $_GET;
                    $linkParams['page'] = $i;
                    $linkStr = http_build_query($linkParams);
                    ?>
                    <a href="index.php?<?= $linkStr ?>" class="page-link <?= $page == $i ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div style="text-align: center; padding: 60px; background-color: var(--bg-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
            <p style="font-size: 1.2rem; color: var(--text-muted); font-style: italic;">Không tìm thấy sản phẩm nào khớp với tìm kiếm của bạn.</p>
            <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Xem tất cả sản phẩm</a>
        </div>
    <?php endif; ?>
</div>

<?php
include 'footer.php';
?>
