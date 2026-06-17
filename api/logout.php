<?php
require_once '../config.php';
session_destroy();
header('Location: /tkb/login.php');
exit();
?>
