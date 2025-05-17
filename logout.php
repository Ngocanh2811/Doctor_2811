<?php
// logout.php

// Hiển thị lỗi khi debug (bỏ 2 dòng này sau khi chạy ổn)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
// Xóa hết session
$_SESSION = [];

// Xóa cookie session nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Chuyển về trang đăng nhập
header("Location: logindoctor.php");
exit;
