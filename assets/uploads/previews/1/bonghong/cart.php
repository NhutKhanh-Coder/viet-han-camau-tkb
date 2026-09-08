<?php
require_once 'config.php';

// Force user login for cart interactions
if (!isLoggedIn()) {
    $_SESSION['toast'] = [
        'message' => 'Vui lòng đăng nhập để sử dụng giỏ hàng.',
        'type' => 'error'
    ];
    redirect('auth.php');
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. PROCESS CART ACTIONS
if ($action === 'add' || $action === 'buy_now') {
    $product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($product_id > 0) {
        try {
            // Check if product exists and is available
            $prodStmt = $pdo->prepare("SELECT status, quantity FROM Products WHERE id = ?");
            $prodStmt->execute([$product_id]);
            $prod = $prodStmt->fetch();
            
            if ($prod && $prod['status'] === 'Còn hàng' && $prod['quantity'] > 0) {
                // Check if already in cart
                $cartCheck = $pdo->prepare("SELECT id, quantity FROM Carts WHERE user_id = ? AND product_id = ?");
                $cartCheck->execute([$user_id, $product_id]);
                $cartItem = $cartCheck->fetch();
                
                if ($cartItem) {
                    // Update quantity
                    $newQty = $cartItem['quantity'] + 1;
                    $updateCart = $pdo->prepare("UPDATE Carts SET quantity = ? WHERE id = ?");
                    $updateCart->execute([$newQty, $cartItem['id']]);
                } else {
                    // Insert new
                    $insertCart = $pdo->prepare("INSERT INTO Carts (user_id, product_id, quantity) VALUES (?, ?, 1)");
                    $insertCart->execute([$user_id, $product_id]);
                }
                
                $_SESSION['toast'] = [
                    'message' => 'Đã thêm sản phẩm vào giỏ hàng.',
                    'type' => 'success'
                ];
            } else {
                $_SESSION['toast'] = [
                    'message' => 'Sản phẩm đã hết hàng hoặc không khả dụng.',
                    'type' => 'error'
                ];
            }
        } catch (PDOException $e) {
            $_SESSION['toast'] = [
                'message' => 'Lỗi thêm vào giỏ: ' . $e->getMessage(),
                'type' => 'error'
            ];
        }
    }
    
    if ($action === 'buy_now') {
        redirect('cart.php');
    } else {
        // Redirect back to referrer or index.php
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        redirect($referrer);
    }
}

if ($action === 'update') {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        try {
            $updateCart = $pdo->prepare("UPDATE Carts SET quantity = ? WHERE id = ? AND user_id = ?");
            $deleteCart = $pdo->prepare("DELETE FROM Carts WHERE id = ? AND user_id = ?");
            
            $warning_triggered = false;
            foreach ($_POST['quantities'] as $cart_id => $qty) {
                $cart_id = (int)$cart_id;
                $qty = (int)$qty;
                
                if ($qty <= 0) {
                    $deleteCart->execute([$cart_id, $user_id]);
                } else {
                    // Check stock
                    $checkStock = $pdo->prepare("SELECT p.quantity, p.product_name FROM Products p JOIN Carts c ON c.product_id = p.id WHERE c.id = ?");
                    $checkStock->execute([$cart_id]);
                    $stockData = $checkStock->fetch();
                    
                    if ($stockData) {
                        $stock = (int)$stockData['quantity'];
                        $pname = $stockData['product_name'];
                        if ($qty > $stock) {
                            $qty = $stock;
                            $warning_triggered = true;
                            $_SESSION['toast'] = [
                                'message' => "Sản phẩm '$pname' chỉ còn $stock sản phẩm trong kho.",
                                'type' => 'warning'
                            ];
                        }
                    }
                    $updateCart->execute([$qty, $cart_id, $user_id]);
                }
            }
            if (!$warning_triggered) {
                $_SESSION['toast'] = [
                    'message' => 'Cập nhật giỏ hàng thành công.',
                    'type' => 'success'
                ];
            }
        } catch (PDOException $e) {
            $_SESSION['toast'] = [
                'message' => 'Cập nhật thất bại: ' . $e->getMessage(),
                'type' => 'error'
            ];
        }
    }
    redirect('cart.php');
}

if ($action === 'remove') {
    $cart_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($cart_id > 0) {
        try {
            $deleteCart = $pdo->prepare("DELETE FROM Carts WHERE id = ? AND user_id = ?");
            $deleteCart->execute([$cart_id, $user_id]);
            $_SESSION['toast'] = [
                'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
                'type' => 'success'
            ];
        } catch (PDOException $e) {
            $_SESSION['toast'] = [
                'message' => 'Xóa sản phẩm thất bại: ' . $e->getMessage(),
                'type' => 'error'
            ];
        }
    }
    redirect('cart.php');
}

// 2. CHECKOUT OPERATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'COD';
    
    if (empty($fullname) || empty($phone) || empty($address)) {
        $_SESSION['toast'] = [
            'message' => 'Vui lòng nhập đầy đủ thông tin giao hàng.',
            'type' => 'error'
        ];
    } else {
        try {
            // Get user's cart items
            $cartItemsStmt = $pdo->prepare("SELECT c.*, p.price, p.quantity AS stock, p.product_name, p.status FROM Carts c 
                                            JOIN Products p ON c.product_id = p.id 
                                            WHERE c.user_id = ?");
            $cartItemsStmt->execute([$user_id]);
            $cartItems = $cartItemsStmt->fetchAll();
            
            if (count($cartItems) === 0) {
                $_SESSION['toast'] = [
                    'message' => 'Giỏ hàng của bạn đang trống.',
                    'type' => 'error'
                ];
            } else {
                // Begin Transaction
                $pdo->beginTransaction();
                
                $total_amount = 0;
                foreach ($cartItems as $item) {
                    // Check stock availability
                    if ($item['status'] === 'Hết hàng' || $item['stock'] < $item['quantity']) {
                        throw new Exception("Sản phẩm '" . $item['product_name'] . "' đã hết hàng hoặc không đủ số lượng trong kho. Vui lòng cập nhật lại giỏ hàng.");
                    }
                    $total_amount += $item['price'] * $item['quantity'];
                }
                
                // Insert into Orders
                $orderStmt = $pdo->prepare("INSERT INTO Orders (user_id, fullname, phone, address, note, payment_method, total_amount, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận')");
                $orderStmt->execute([$user_id, $fullname, $phone, $address, $note, $payment_method, $total_amount]);
                $order_id = $pdo->lastInsertId();
                
                // Insert into Order_details & update product stock & status dynamically
                $detailStmt = $pdo->prepare("INSERT INTO Order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $updateStockStmt = $pdo->prepare("UPDATE Products SET quantity = quantity - ?, status = IF(quantity - ? <= 0, 'Hết hàng', 'Còn hàng') WHERE id = ?");
                
                foreach ($cartItems as $item) {
                    $detailStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                    $updateStockStmt->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
                }
                
                // Clear Cart
                $clearCartStmt = $pdo->prepare("DELETE FROM Carts WHERE user_id = ?");
                $clearCartStmt->execute([$user_id]);
                
                // Commit
                $pdo->commit();
                
                $_SESSION['toast'] = [
                    'message' => 'Đặt hàng thành công! Đơn hàng của bạn đang chờ xác nhận.',
                    'type' => 'success'
                ];
                redirect('index.php');
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['toast'] = [
                'message' => 'Đặt hàng thất bại: ' . $e->getMessage(),
                'type' => 'error'
            ];
        }
    }
}

// 3. FETCH USER CART
try {
    $cartQuery = "SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.product_name, p.price, p.image, p.status, p.quantity AS stock 
                  FROM Carts c 
                  JOIN Products p ON c.product_id = p.id 
                  WHERE c.user_id = ? 
                  ORDER BY c.created_at DESC";
    $cartStmt = $pdo->prepare($cartQuery);
    $cartStmt->execute([$user_id]);
    $cart_list = $cartStmt->fetchAll();
    
    // Fetch current user info for default shipping details
    $userStmt = $pdo->prepare("SELECT fullname, phone, address FROM Users WHERE id = ? LIMIT 1");
    $userStmt->execute([$user_id]);
    $user_info = $userStmt->fetch();
} catch (PDOException $e) {
    $cart_list = [];
    $user_info = ['fullname' => '', 'phone' => '', 'address' => ''];
}

include 'header.php';
?>

<h1 class="section-title">Giỏ Hàng Của Bạn</h1>

<?php if (count($cart_list) > 0): ?>
    <div style="display: flex; flex-direction: column; gap: 32px; max-width: 1000px; margin: 0 auto;">
        
        <!-- Cart Details Panel (Matches Câu 5 layout exactly) -->
        <div class="cart-table-card" style="width: 100%;">
            <form action="cart.php?action=update" method="POST">
                <table class="cart-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Ảnh</th>
                            <th>Sản phẩm</th>
                            <th>Giá</th>
                            <th style="width: 120px;">Số lượng</th>
                            <th>Thành tiền</th>
                            <th style="width: 80px;">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach ($cart_list as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $grand_total += $subtotal;
                        ?>
                            <tr class="cart-row" data-price="<?= $item['price'] ?>">
                                <td>
                                    <img src="<?= sanitize($item['image'] ?: 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600') ?>" 
                                         class="cart-item-img" 
                                         style="width: 70px; height: 70px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                </td>
                                <td>
                                    <a href="detail.php?id=<?= $item['product_id'] ?>" style="font-weight: 600; color: var(--primary-dark); font-size: 1rem;">
                                        <?= sanitize($item['product_name']) ?>
                                    </a>
                                </td>
                                <td style="font-weight: 500;"><?= formatPrice($item['price']) ?></td>
                                <td>
                                    <input type="number" 
                                           name="quantities[<?= $item['cart_id'] ?>]" 
                                           value="<?= $item['quantity'] ?>" 
                                           min="1" 
                                           max="<?= $item['stock'] ?>" 
                                           class="input-control qty-input" 
                                           style="width: 80px; text-align: center; padding: 6px; font-weight: 600; border-radius: var(--radius-sm);">
                                </td>
                                <td class="subtotal-val" style="font-weight: 700; color: var(--primary);"><?= formatPrice($subtotal) ?></td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?= $item['cart_id'] ?>" 
                                       class="btn btn-secondary" 
                                       style="padding: 6px 12px; font-size: 0.85rem; font-weight: 600; background-color: var(--primary-light); color: var(--primary); border: none;">
                                        Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="text-align: right; font-size: 1.2rem; font-weight: 700; margin-top: 24px; padding-top: 12px; color: var(--primary-dark);">
                    Tổng tiền <span id="grand-total-val"><?= formatPrice($grand_total) ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                    <button type="submit" class="btn btn-secondary" style="padding: 10px 24px;">Cập nhật giỏ hàng</button>
                    <button type="button" id="btn-show-checkout" class="btn btn-primary" style="padding: 10px 28px;">Đặt hàng</button>
                </div>
            </form>
        </div>
        
        <!-- Checkout Delivery Panel (Slides down when clicking "Đặt hàng") -->
        <div id="checkout-form-container" class="cart-checkout-card" style="display: none; width: 100%; animation: fadeIn 0.4s ease-out;">
            <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--primary-dark); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                Thông tin nhận hàng & Xác nhận thanh toán
            </h3>
            
            <form action="cart.php" method="POST">
                <input type="hidden" name="action" value="checkout">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="fullname">Họ tên người nhận <span style="color: var(--danger)">*</span></label>
                        <input type="text" id="fullname" name="fullname" class="input-control" required value="<?= sanitize($user_info['fullname']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Số điện thoại nhận <span style="color: var(--danger)">*</span></label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" class="input-control" required value="<?= sanitize($user_info['phone']) ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="address">Địa chỉ nhận hàng <span style="color: var(--danger)">*</span></label>
                        <textarea id="address" name="address" rows="3" class="input-control" required style="resize: vertical;"><?= sanitize($user_info['address']) ?></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="note">Ghi chú giao hàng</label>
                        <textarea id="note" name="note" rows="2" class="input-control" style="resize: vertical;" placeholder="Nhập ghi chú (nếu có)..."></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="payment_method">Phương thức thanh toán</label>
                        <select id="payment_method" name="payment_method" class="select-control" style="width: 100%;">
                            <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                            <option value="BANK">Chuyển khoản ngân hàng (BANK)</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 24px; text-align: right;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px;">Xác nhận đặt hàng</button>
                </div>
            </form>
        </div>
        
    </div>

    <!-- Script to toggle shipping form and scroll into view + real-time total recalculations -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnShowCheckout = document.getElementById('btn-show-checkout');
        const checkoutContainer = document.getElementById('checkout-form-container');
        
        if (btnShowCheckout && checkoutContainer) {
            btnShowCheckout.addEventListener('click', function() {
                checkoutContainer.style.display = 'block';
                checkoutContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        // Real-time cart calculations
        const qtyInputs = document.querySelectorAll('.qty-input');
        const grandTotalVal = document.getElementById('grand-total-val');
        
        function formatVND(amount) {
            return Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + " đ";
        }
        
        function updateTotals() {
            let total = 0;
            const rows = document.querySelectorAll('.cart-row');
            
            rows.forEach(row => {
                const price = parseFloat(row.getAttribute('data-price'));
                const qtyInput = row.querySelector('.qty-input');
                const subtotalCell = row.querySelector('.subtotal-val');
                
                let qty = parseInt(qtyInput.value);
                if (isNaN(qty) || qty < 1) {
                    qty = 1;
                }
                
                const subtotal = price * qty;
                total += subtotal;
                
                subtotalCell.innerHTML = formatVND(subtotal);
            });
            
            if (grandTotalVal) {
                grandTotalVal.innerHTML = formatVND(total);
            }
        }
        
        qtyInputs.forEach(input => {
            input.addEventListener('input', updateTotals);
            input.addEventListener('change', updateTotals);
        });
    });
    </script>
    
<?php else: ?>
    <div style="text-align: center; padding: 80px; background-color: var(--bg-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); max-width: 600px; margin: 0 auto;">
        <p style="font-size: 1.2rem; color: var(--text-muted); font-style: italic; margin-bottom: 20px;">Giỏ hàng của bạn đang trống.</p>
        <a href="index.php" class="btn btn-primary">Mua sắm ngay</a>
    </div>
<?php endif; ?>

<?php
include 'footer.php';
?>
