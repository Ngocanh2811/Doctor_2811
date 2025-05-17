<?php
session_start();
require_once __DIR__ . '/db_config.php';

// Xác định email từ GET (email hoặc username)
$email = '';
if (!empty($_GET['email'])) {
    $email = trim($_GET['email']);
} elseif (!empty($_GET['username'])) {
    // Nếu truyền username, lấy email từ bảng user
    $stmt0 = $conn->prepare("SELECT Email FROM `user` WHERE Username = ?");
    $stmt0->bind_param("s", $_GET['username']);
    $stmt0->execute();
    $stmt0->bind_result($email);
    $stmt0->fetch();
    $stmt0->close();
}

// Lấy thông báo thành công hoặc lỗi
$success = $_SESSION['message'] ?? '';
$error   = $_SESSION['error']   ?? '';
unset($_SESSION['message'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST luôn có email field
    $email    = trim($_POST['email']);
    $otp      = trim($_POST['otp']);
    $new_pass = trim($_POST['password']);

    // 1) Xác minh OTP và thời hạn (10 phút)
    $stmt = $conn->prepare(
      "SELECT created_at
         FROM password_resets
        WHERE email = ? AND token = ?"
    );
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $stmt->bind_result($created_at);

    if ($stmt->fetch() && (time() - strtotime($created_at) <= 600)) {
        $stmt->close();

        // 2) Cập nhật mật khẩu (nên hash thật sự nếu bcrypt)
        $upd = $conn->prepare(
          "UPDATE `user`
             SET PasswordHash = ?
           WHERE Email = ?"
        );
        $upd->bind_param("ss", $new_pass, $email);
        $upd->execute();
        $upd->close();

        // 3) Xóa OTP đã dùng
        $del = $conn->prepare(
          "DELETE FROM password_resets WHERE email = ?"
        );
        $del->bind_param("s", $email);
        $del->execute();
        $del->close();

        $_SESSION['message'] = "✅ Mật khẩu đã được thiết lập lại. Bạn có thể đăng nhập ngay.";
        header('Location: logindoctor.php');
        exit;
    } else {
        $_SESSION['error'] = "❌ OTP không hợp lệ hoặc đã hết hạn.";
        $stmt->close();
        // Chuyển hướng giữ GET param email hoặc username
        if (!empty($_GET['email'])) {
            header('Location: reset_password.php?email=' . urlencode($email));
        } else {
            header('Location: reset_password.php?username=' . urlencode($_GET['username'] ?? ''));
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #74ebd5, #9face6);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
    }
    .form-container {
      background: white;
      border-radius: 20px;
      padding: 50px 35px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.15);
      width: 400px;
      text-align: center;
      animation: fadeIn 0.8s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    h3 {
      color: #1976d2;
      font-weight: 700;
      margin-bottom: 20px;
    }
    .btn-reset {
      background: linear-gradient(to right, #42a5f5, #1e88e5);
      color: white;
      border: none;
      border-radius: 30px;
      padding: 10px;
      font-weight: 600;
      width: 100%;
      transition: transform 0.3s ease;
    }
    .btn-reset:hover {
      transform: scale(1.02);
    }
    .form-label { font-weight: 600; }
  </style>
</head>
<body>
  <div class="form-container">
    <h3><i class="fas fa-lock"></i> Reset Password</h3>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-3 text-start">
      <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">OTP Code</label>
        <input type="text" name="otp" class="form-control" placeholder="Enter OTP" required>
      </div>
      <div class="mb-4">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter new password" required>
      </div>
      <button type="submit" class="btn-reset">Confirm</button>
    </form>
  </div>
</body>
</html>
