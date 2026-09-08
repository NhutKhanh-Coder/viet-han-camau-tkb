<?php
require_once 'config.php';

// Unset user variables
unset($_SESSION['user_id']);
unset($_SESSION['user_username']);
unset($_SESSION['user_fullname']);
unset($_SESSION['user_role']);

// Reset session arrays
$_SESSION['toast'] = [
    'message' => 'Bạn đã đăng xuất thành công.',
    'type' => 'success'
];

redirect('index.php');
?>
