<?php
require_once 'config.php';

// Force Admin Role
if (!isAdmin()) {
    $_SESSION['toast'] = [
        'message' => 'Bạn không có quyền truy cập trang quản trị.',
        'type' => 'error'
    ];
    redirect('index.php');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$error = '';

// 1. PROCESS POST ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add & Edit Actions
    if ($action === 'add' || $action === 'edit') {
        $product_name = trim($_POST['product_name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0.0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        $image = $_POST['image'] ?? '';
        $image2 = $_POST['image2'] ?? '';
        $image3 = $_POST['image3'] ?? '';

        // Create uploads/ directory if it doesn't exist
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $files_to_upload = [
            'image' => 'image_file',
            'image2' => 'image2_file',
            'image3' => 'image3_file'
        ];

        foreach ($files_to_upload as $db_key => $file_key) {
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES[$file_key]['tmp_name'];
                $file_name = $_FILES[$file_key]['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                if (in_array($file_ext, $allowed_extensions)) {
                    $new_name = uniqid('prod_', true) . '.' . $file_ext;
                    $dest_path = $upload_dir . $new_name;
                    if (move_uploaded_file($file_tmp, $dest_path)) {
                        ${$db_key} = $dest_path;
                    }
                }
            } else {
                ${$db_key} = extractImageUrl(${$db_key});
            }
        }

        $description = trim($_POST['description'] ?? '');
        $flower_meaning = trim($_POST['flower_meaning'] ?? '');
        $status = $_POST['status'] ?? 'Còn hàng';
        
        if (empty($product_name) || $category_id <= 0 || $price < 0) {
            $error = 'Vui lòng điền đầy đủ Tên sản phẩm, Danh mục và Giá hợp lệ.';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO Products (category_id, product_name, price, quantity, image, image2, image3, description, flower_meaning, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$category_id, $product_name, $price, $quantity, $image, $image2, $image3, $description, $flower_meaning, $status]);
                    $_SESSION['toast'] = ['message' => 'Thêm sản phẩm mới thành công.', 'type' => 'success'];
                } else {
                    $id = (int)($_GET['id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE Products SET category_id = ?, product_name = ?, price = ?, quantity = ?, image = ?, image2 = ?, image3 = ?, description = ?, flower_meaning = ?, status = ? WHERE id = ?");
                    $stmt->execute([$category_id, $product_name, $price, $quantity, $image, $image2, $image3, $description, $flower_meaning, $status, $id]);
                    $_SESSION['toast'] = ['message' => 'Cập nhật thông tin sản phẩm thành công.', 'type' => 'success'];
                }
                redirect('admin_products.php');
            } catch (PDOException $e) {
                $error = 'Thao tác thất bại: ' . $e->getMessage();
            }
        }
    }
}

// 2. PROCESS GET ACTIONS (Delete)
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM Products WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['toast'] = ['message' => 'Đã xóa sản phẩm thành công.', 'type' => 'success'];
        } catch (PDOException $e) {
            $_SESSION['toast'] = ['message' => 'Xóa sản phẩm thất bại (do có liên kết khóa ngoại): ' . $e->getMessage(), 'type' => 'error'];
        }
    }
    redirect('admin_products.php');
}

// Fetch all categories for forms
try {
    $catStmt = $pdo->query("SELECT * FROM Category_name ORDER BY category_name ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

include 'header.php';
?>

<div class="container" style="max-width: 1100px;">
    
    <!-- HEADER CONTROLS -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 class="section-title" style="margin: 0; text-align: left;">Quản Lý Sản Phẩm (Admin)</h1>
        <?php if ($action === 'list'): ?>
            <a href="admin_products.php?action=add" class="btn btn-primary">Thêm sản phẩm mới</a>
        <?php else: ?>
            <a href="admin_products.php" class="btn btn-secondary">Quay lại danh sách</a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($error)): ?>
        <div style="background-color: var(--primary-light); color: var(--danger); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 24px; font-weight: 500;">
            <?= sanitize($error) ?>
        </div>
    <?php endif; ?>

    <!-- ================= LIST VIEW ================= -->
    <?php if ($action === 'list'): 
        try {
            $prodStmt = $pdo->query("SELECT p.*, c.category_name FROM Products p JOIN Category_name c ON p.category_id = c.id ORDER BY p.id DESC");
            $productsList = $prodStmt->fetchAll();
        } catch (PDOException $e) {
            $productsList = [];
        }
    ?>
        <div class="cart-table-card" style="width: 100%; overflow-x: auto;">
            <table class="cart-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 80px;">Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Kho</th>
                        <th>Trạng thái</th>
                        <th style="width: 160px; text-align: center;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($productsList) > 0): ?>
                        <?php foreach ($productsList as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= sanitize($p['image'] ?: 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600') ?>" 
                                         alt="<?= sanitize($p['product_name']) ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                </td>
                                <td style="font-weight: 600; color: var(--primary-dark);"><?= sanitize($p['product_name']) ?></td>
                                <td><?= sanitize($p['category_name']) ?></td>
                                <td style="font-weight: 700; color: var(--primary);"><?= formatPrice($p['price']) ?></td>
                                <td><?= sanitize($p['quantity']) ?></td>
                                <td>
                                    <?php if ($p['status'] === 'Còn hàng' && $p['quantity'] > 0): ?>
                                        <span style="color: var(--success); font-weight: 600;">Còn hàng</span>
                                    <?php else: ?>
                                        <span style="color: var(--danger); font-weight: 600;">Hết hàng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <a href="admin_products.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">Sửa</a>
                                        <a href="admin_products.php?action=delete&id=<?= $p['id'] ?>" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');" 
                                           class="btn btn-primary" 
                                           style="padding: 6px 12px; font-size: 0.85rem; background-color: var(--danger);">Xóa</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); font-style: italic;">Chưa có sản phẩm nào trong hệ thống.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- ================= ADD / EDIT VIEW ================= -->
    <?php elseif ($action === 'add' || $action === 'edit'): 
        $edit_product = null;
        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            try {
                $stmt = $pdo->prepare("SELECT * FROM Products WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $edit_product = $stmt->fetch();
            } catch (PDOException $e) {
                $edit_product = null;
            }
            if (!$edit_product) {
                redirect('admin_products.php');
            }
        }
        
        $p_name = $edit_product ? $edit_product['product_name'] : '';
        $p_cat = $edit_product ? $edit_product['category_id'] : '';
        $p_price = $edit_product ? $edit_product['price'] : '';
        $p_qty = $edit_product ? $edit_product['quantity'] : '100';
        $p_img = $edit_product ? $edit_product['image'] : '';
        $p_img2 = $edit_product ? $edit_product['image2'] : '';
        $p_img3 = $edit_product ? $edit_product['image3'] : '';
        $p_desc = $edit_product ? $edit_product['description'] : '';
        $p_mean = $edit_product ? $edit_product['flower_meaning'] : '';
        $p_status = $edit_product ? $edit_product['status'] : 'Còn hàng';
    ?>
        <div style="background: var(--bg-white); border-radius: var(--radius-lg); padding: 40px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
            <form action="admin_products.php?action=<?= $action ?><?= $action === 'edit' ? '&id='.$edit_product['id'] : '' ?>" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="product_name">Tên sản phẩm <span style="color: var(--danger)">*</span></label>
                        <input type="text" id="product_name" name="product_name" class="input-control" required value="<?= sanitize($p_name) ?>" placeholder="Bó hoa hồng xanh...">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Danh mục sản phẩm <span style="color: var(--danger)">*</span></label>
                        <select id="category_id" name="category_id" class="select-control" required style="width: 100%;">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $p_cat == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Đơn giá (VND) <span style="color: var(--danger)">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" class="input-control" required value="<?= sanitize($p_price) ?>" placeholder="475000">
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Số lượng tồn kho</label>
                        <input type="number" id="quantity" name="quantity" class="input-control" value="<?= sanitize($p_qty) ?>" placeholder="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Trạng thái kho</label>
                        <select id="status" name="status" class="select-control" style="width: 100%;">
                            <option value="Còn hàng" <?= $p_status === 'Còn hàng' ? 'selected' : '' ?>>Còn hàng</option>
                            <option value="Hết hàng" <?= $p_status === 'Hết hàng' ? 'selected' : '' ?>>Hết hàng</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width" style="border-top: 1px dashed var(--border-color); padding-top: 16px; margin-top: 10px;">
                        <div style="background-color: var(--primary-light); padding: 16px; border-radius: var(--radius-md); margin-bottom: 8px; font-size: 0.9rem; color: var(--primary-dark); line-height: 1.6; border-left: 4px solid var(--primary);">
                            <strong>💡 MẸO & HƯỚNG DẪN THÊM ẢNH SẢN PHẨM:</strong><br>
                            • <strong>Cách 1 (Tốt nhất & Ổn định):</strong> Tải tệp ảnh trực tiếp bằng nút <strong>Chọn tệp</strong> phía dưới để tránh lỗi liên kết.<br>
                            • <strong>Cách 2 (Sử dụng URL):</strong> Nếu copy ảnh từ Google/Bing, nhấp chuột phải lên hình ảnh và chọn <strong>"Sao chép địa chỉ hình ảnh"</strong> (Copy image address) rồi dán vào ô nhập URL. Không sao chép đường dẫn trên thanh địa chỉ vì đó là trang web tìm kiếm chứ không phải tệp ảnh trực tiếp.
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="image_file" style="font-weight: 600;">Ảnh sản phẩm chính</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <span style="font-size: 0.85rem; color: var(--text-muted);">Tải ảnh từ thiết bị:</span>
                                <input type="file" id="image_file" name="image_file" accept="image/*" class="input-control" style="background: var(--bg-cream); padding: 6px 12px;">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <span style="font-size: 0.85rem; color: var(--text-muted);">Hoặc dán URL ảnh trực tiếp:</span>
                                <input type="text" id="image" name="image" class="input-control" value="<?= sanitize($p_img) ?>" placeholder="https://images.unsplash.com/photo-...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image2_file" style="font-weight: 600;">Ảnh phụ 2</label>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <input type="file" id="image2_file" name="image2_file" accept="image/*" class="input-control" style="background: var(--bg-cream); padding: 6px 12px;">
                            <span style="font-size: 0.85rem; text-align: center; color: var(--text-muted); font-weight: 500;">hoặc nhập URL ảnh</span>
                            <input type="text" id="image2" name="image2" class="input-control" value="<?= sanitize($p_img2) ?>" placeholder="https://...">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image3_file" style="font-weight: 600;">Ảnh phụ 3</label>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <input type="file" id="image3_file" name="image3_file" accept="image/*" class="input-control" style="background: var(--bg-cream); padding: 6px 12px;">
                            <span style="font-size: 0.85rem; text-align: center; color: var(--text-muted); font-weight: 500;">hoặc nhập URL ảnh</span>
                            <input type="text" id="image3" name="image3" class="input-control" value="<?= sanitize($p_img3) ?>" placeholder="https://...">
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">Mô tả sản phẩm</label>
                        <textarea id="description" name="description" rows="5" class="input-control" style="resize: vertical;" placeholder="Mô tả chi tiết về đặc điểm sản phẩm hoa..."><?= sanitize($p_desc) ?></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="flower_meaning">Ý nghĩa loài hoa</label>
                        <textarea id="flower_meaning" name="flower_meaning" rows="4" class="input-control" style="resize: vertical;" placeholder="Ý nghĩa truyền tải của đóa hoa này..."><?= sanitize($p_mean) ?></textarea>
                    </div>
                </div>
                
                <div style="margin-top: 32px; display: flex; gap: 16px; justify-content: flex-end;">
                    <a href="admin_products.php" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary">Lưu sản phẩm</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
include 'footer.php';
?>
