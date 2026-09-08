<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'sql308.infinityfree.com';
$username = 'if0_41796593';
$password = 'T5v3vJeuvOxCI';
$dbname = 'if0_41796593_truong_caodang';

try {
    // 1. Connect to MySQL Server (without database)
    $pdo = new PDO("mysql:host=sql308.infinityfree.com;dbname=if0_41796593_truong_caodang;charset=utf8mb4", 'if0_41796593', 'T5v3vJeuvOxCI');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("// CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Database `$dbname` created or already exists.<br>";
    
    // Connect to the specific database
    $pdo->exec("USE `$dbname`;");
    
    // 2. Read and run database.sql
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file database.sql not found!");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute schema queries
    $pdo->exec($sql);
    echo "Database schema tables created successfully.<br>";
    
    // 3. Insert Seed Data
    
    // Settings
    $stmt = $pdo->prepare("INSERT INTO `Settings` (id, website_name, slogan, logo, address, phone, email, facebook, zalo, youtube) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Roselia',
        'Mỗi bó hoa, một câu chuyện',
        'Roselia',
        '123 Đường Phan Ngọc Hiển, Phường 9, TP. Cà Mau',
        '0912345678',
        'contact@roselia.com',
        'https://facebook.com/roselia.flowers',
        'https://zalo.me/0912345678',
        'https://youtube.com/c/roselia.flowers'
    ]);
    echo "Seed data for Settings inserted.<br>";
    
    // Users
    $adminPassword = password_hash('Admin@12345', PASSWORD_DEFAULT);
    $customerPassword = password_hash('Cường@12345', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO `Users` (fullname, username, email, phone, gender, birthday, address, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Admin
    $stmt->execute([
        'Administrator',
        'admin',
        'admin@roselia.com',
        '0900000001',
        'Nam',
        '1990-01-01',
        'Cà Mau',
        $adminPassword,
        'admin',
        1
    ]);
    
    // Customer
    $stmt->execute([
        'Phạm Duy Cường',
        'phamduycuong',
        'phamduycuong@camauvkc.edu.vn',
        '0918765432',
        'Nam',
        '1998-05-15',
        'Số 4 Hùng Vương, Phường 5, TP. Cà Mau',
        $customerPassword,
        'customer',
        1
    ]);
    echo "Seed data for Users (Admin and Customer) inserted.<br>";
    
    // Categories
    $stmt = $pdo->prepare("INSERT INTO `Category_name` (id, category_name, description) VALUES (?, ?, ?)");
    $categories = [
        [1, 'Hoa tình yêu', 'Các mẫu hoa dành tặng cho người yêu, ngày lễ kỷ niệm lãng mạn.'],
        [2, 'Hoa chúc mừng', 'Hoa khai trương, chúc mừng tốt nghiệp, thăng chức.'],
        [3, 'Hoa Đất Sét', 'Mẫu hoa làm bằng đất sét thủ công tinh xảo, trưng bày bền lâu.'],
        [4, 'Hoa Sáp', 'Hoa hồng sáp thơm quyến rũ, nhiều màu sắc độc đáo.']
    ];
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    echo "Seed data for Category_name inserted.<br>";
    
    // Products
    $stmt = $pdo->prepare("INSERT INTO `Products` (id, category_id, product_name, price, quantity, image, image2, image3, description, flower_meaning, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $products = [
        [
            1, 1, 'Hoa hồng xanh Only you', 580.00, 20,
            'https://images.unsplash.com/photo-1561181286-d3fee7d55364?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1520763185298-1b434c919102?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1533616688419-b7a585564566?w=600&auto=format&fit=crop&q=80',
            'Bó hoa hồng xanh nhập khẩu sang trọng, được bó kèm với các loại lá phụ nhập khẩu như lá bạc, hoa baby trắng tinh khôi.',
            'Hoa hồng xanh tượng trưng cho một tình yêu bất diệt, duy nhất và vĩnh cửu. Phù hợp tặng cho người thương duy nhất của bạn.',
            'Còn hàng'
        ],
        [
            2, 3, 'Hoa Đất Sét Gấu Bông', 475.00, 15,
            'https://images.unsplash.com/photo-1562240020-ce31ccb0fa7d?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format&fit=crop&q=80',
            'Chậu hoa đất sét handmade tinh xảo kết hợp chú gấu bông nhỏ xinh xắn. Đây là sản phẩm thủ công nghệ thuật độ bền trọn đời.',
            'Món quà dễ thương thể hiện sự kiên nhẫn, bền bỉ và tình cảm chân thành ấm áp không bao giờ phai nhạt.',
            'Còn hàng'
        ],
        [
            3, 3, 'Hoa Đất Sét Gà Con', 450000.00, 12,
            'https://images.unsplash.com/photo-1562240020-ce31ccb0fa7d?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600&auto=format&fit=crop&q=80',
            'Chậu hoa đất sét dễ thương đính kèm hình ảnh những chú gà con lông vàng ngộ nghĩnh, được nhào nặn hoàn toàn bằng tay.',
            'Ý nghĩa mang lại sự may mắn, bình an, ngập tràn sức sống tươi trẻ và niềm vui cho bàn làm việc hoặc góc học tập.',
            'Còn hàng'
        ],
        [
            4, 4, 'Hoa Hồng Sáp', 350000.00, 30,
            'https://images.unsplash.com/photo-1520763185298-1b434c919102?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1561181286-d3fee7d55364?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1533616688419-b7a585564566?w=600&auto=format&fit=crop&q=80',
            'Bó hoa hồng sáp thơm cao cấp với màu đỏ thắm quyến rũ, cánh hoa mềm mại chân thật kèm hương thơm nhẹ dịu quý phái.',
            'Hương thơm bền lâu tượng trưng cho tình cảm đong đầy, nồng nhiệt và sắc sảo của tuổi trẻ.',
            'Còn hàng'
        ],
        [
            5, 4, 'Hoa Tulip Sáp', 450000.00, 25,
            'https://images.unsplash.com/photo-1533616688419-b7a585564566?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1561181286-d3fee7d55364?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1520763185298-1b434c919102?w=600&auto=format&fit=crop&q=80',
            'Bó hoa tulip sáp màu hồng phấn thanh lịch, được gia công tỉ mỉ bằng sáp thơm nhập khẩu cao cấp, đóng gói trong hộp quà tinh tế.',
            'Tulip biểu tượng cho sự kiêu sa, lộng lẫy và một lời bày tỏ tình yêu ngọt ngào, tinh khiết.',
            'Còn hàng'
        ],
        [
            6, 2, 'Giỏ hoa chúc mừng', 910000.00, 8,
            'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1596436889106-be35e843f974?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format&fit=crop&q=80',
            'Giỏ hoa chúc mừng khai trương cỡ lớn bao gồm hoa lan, hoa hồng môn, hoa đồng tiền và hướng dương tươi rói.',
            'Mang ý nghĩa hồng phát, phát tài phát lộc, vạn sự hanh thông và khởi đầu tốt đẹp cho công việc kinh doanh.',
            'Còn hàng'
        ],
        [
            7, 2, 'Hoa tulip vàng chúc mừng', 600000.00, 10,
            'https://images.unsplash.com/photo-1596436889106-be35e843f974?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1533616688419-b7a585564566?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format&fit=crop&q=80',
            'Bó hoa tulip vàng tươi tắn, đại diện cho những lời chúc rực rỡ nhất gửi tới bạn bè, người thân nhân dịp đặc biệt.',
            'Màu vàng rạng rỡ của hoa tulip tượng trưng cho ánh nắng mặt trời, niềm vui, sự sung túc và tình bạn chân thành.',
            'Còn hàng'
        ],
        [
            8, 2, 'Hoa hồng vàng', 550000.00, 14,
            'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1596436889106-be35e843f974?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600&auto=format&fit=crop&q=80',
            'Bó hoa hồng vàng sang trọng gồm 20 cành hoa hồng đà lạt vàng rực, điểm xuyết hoa baby khô xinh xắn.',
            'Tượng trưng cho sự khởi đầu mới suôn sẻ, niềm tự hào và tình cảm chân thành vững bền.',
            'Còn hàng'
        ],
        [
            9, 2, 'Bó hoa hướng dương may mắn', 420000.00, 18,
            'https://images.unsplash.com/photo-1596436889106-be35e843f974?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format&fit=crop&q=80',
            'Bó hoa hướng dương 5 bông to tròn rực rỡ kết hợp với lá bạc trang trí sang trọng.',
            'Hướng dương luôn hướng về phía mặt trời, tượng trưng cho ý chí vươn lên, sự may mắn và tương lai tươi sáng.',
            'Còn hàng'
        ],
        [
            10, 1, 'Bó hoa cẩm tú cầu dịu dàng', 480000.00, 5,
            'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1561181286-d3fee7d55364?w=600&auto=format&fit=crop&q=80',
            'Bó cẩm tú cầu xanh pastel dịu mát, quấn quanh bởi giấy gói hàn quốc cao cấp tạo nên phong cách trang nhã thanh lịch.',
            'Cẩm tú cầu tượng trưng cho sự chân thành, biết ơn và những cảm xúc trọn vẹn dạt dào từ trái tim.',
            'Còn hàng'
        ],
        [
            11, 3, 'Hoa Đất Sét Heo Hồng', 380000.00, 0,
            'https://images.unsplash.com/photo-1562240020-ce31ccb0fa7d?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600&auto=format&fit=crop&q=80',
            'Mẫu chậu hoa đất sét dễ thương hình chú heo hồng nghịch ngợm, phù hợp làm quà tặng sinh nhật hoặc kỷ niệm đáng yêu.',
            'Chú heo nhỏ mang lại cảm giác sung túc, sung sướng, nhàn nhã và niềm vui mộc mạc trong cuộc sống.',
            'Hết hàng'
        ],
        [
            12, 1, 'Hoa hồng đỏ Valentine', 680000.00, 15,
            'https://images.unsplash.com/photo-1520763185298-1b434c919102?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1561181286-d3fee7d55364?w=600&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1533616688419-b7a585564566?w=600&auto=format&fit=crop&q=80',
            'Bó hoa gồm 99 đóa hoa hồng đỏ thắm tươi rực được nhập từ Đà Lạt, bọc trong giấy đen viền vàng sang trọng.',
            'Bày tỏ tình yêu mãnh liệt, nồng nàn và son sắc bền chặt qua thời gian.',
            'Còn hàng'
        ]
    ];
    
    foreach ($products as $prod) {
        $stmt->execute($prod);
    }
    echo "Seed data for Products (12 items) inserted.<br>";
    
    // Reviews
    $stmt = $pdo->prepare("INSERT INTO `Reviews` (user_id, product_id, rating, comment, status) VALUES (?, ?, ?, ?, ?)");
    $reviews = [
        [2, 1, 5, 'Hoa rất đẹp, màu xanh cực kỳ lạ mắt và đóng gói rất cẩn thận!', 1],
        [2, 2, 4, 'Sản phẩm đất sét làm rất tinh xảo, giống hình. Hơi nhỏ hơn tưởng tượng một chút nhưng rất đẹp.', 1]
    ];
    foreach ($reviews as $rev) {
        $stmt->execute($rev);
    }
    echo "Seed data for Reviews inserted.<br>";
    
    // News
    $stmt = $pdo->prepare("INSERT INTO `News` (title, thumbnail, content, created_at) VALUES (?, ?, ?, ?)");
    $newsList = [
        [
            'Bí quyết giữ hoa tươi lâu hơn cả tuần',
            'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?w=600',
            'Để giữ bình hoa tươi trong nhà lâu héo, bạn nên thay nước mỗi ngày, cắt vát gốc cành hoa dưới góc 45 độ, và pha thêm một chút đường hoặc nước cốt chanh vào nước cắm hoa. Ngoài ra, hãy đặt bình hoa ở nơi thoáng mát, tránh ánh nắng trực tiếp...',
            date('Y-m-d H:i:s')
        ],
        [
            'Ý nghĩa các loài hoa trong ngày cưới',
            'https://images.unsplash.com/photo-1520763185298-1b434c919102?w=600',
            'Trong lễ cưới, mỗi loài hoa được chọn đều gửi gắm những thông điệp yêu thương sâu sắc. Hoa hồng đỏ biểu thị tình yêu nồng cháy, hoa mẫu đơn gửi gắm sự giàu sang hạnh phúc, hoa tulip thể hiện tình yêu hoàn hảo, trong khi hoa baby tượng trưng cho sự trong sáng thanh khiết...',
            date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
    foreach ($newsList as $news) {
        $stmt->execute($news);
    }
    echo "Seed data for News inserted.<br>";
    
    // Carts seed for phamduycuong (user 2)
    $stmt = $pdo->prepare("INSERT INTO `Carts` (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([2, 2, 1]); // Hoa Đất Sét Gấu Bông, quantity 1
    $stmt->execute([2, 3, 1]); // Hoa Đất Sét Gà Con, quantity 1
    echo "Seed data for Carts (phamduycuong default items) inserted.<br>";
    
    echo "<h3>Setup Completed Successfully!</h3>";
    echo "<a href='index.php'>Go to Home Page</a>";
    
} catch (PDOException $e) {
    try {
            $f_h = "sql308.infinityfree.com";
            $f_u = "if0_41796593";
            $f_p = "T5v3vJeuvOxCI";
            $f_d = "if0_41796593_truong_caodang";
            $pdo = new PDO("mysql:host=$f_h;dbname=$f_d;charset=utf8mb4", $f_u, $f_p);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn = @new mysqli($f_h, $f_u, $f_p, $f_d);
        } catch(Throwable $ex){}
} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
