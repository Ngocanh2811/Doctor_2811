<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db_config.php';
// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Hiển thị lỗi nếu có
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);

    // 1) Lấy email từ bảng user
    $stmt = $conn->prepare("SELECT Email FROM `user` WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($email);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Tài khoản không tồn tại.";
        header('Location: forgot_password.php');
        exit;
    }
    $stmt->close();

    // 2) Xóa OTP cũ (nếu có) để tránh chèn trùng
    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->bind_param("s", $email);
    $del->execute();
    $del->close();

    // 3) Tạo OTP và lưu vào password_resets
    $otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $created_at = date('Y-m-d H:i:s');

    $ins = $conn->prepare(
      "INSERT INTO password_resets (email, token, created_at)
       VALUES (?, ?, ?)"
    );
    $ins->bind_param("sss", $email, $otp, $created_at);
    $ins->execute();
    $ins->close();

    // 4) Gửi email OTP
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME');
$mail->Password   = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT');

        $mail->setFrom('nguyentna2811@gmail.com', 'Your App Name');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Mã OTP lấy lại mật khẩu';
        $mail->Body    = sprintf(
          "<p>Xin chào <strong>%s</strong>,</p>\n" .
          "<p>Mã OTP của bạn là: <strong>%s</strong></p>\n" .
          "<p>Mã này sẽ hết hạn sau 10 phút.</p>",
          htmlspecialchars($username),
          htmlspecialchars($otp)
        );

        $mail->send();
        header('Location: reset_password.php?email=' . urlencode($email));
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Không gửi được email: {$mail->ErrorInfo}";
        header('Location: forgot_password.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password (OTP)</title>
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
      padding: 50px 35px;
      border-radius: 20px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.15);
      width: 400px;
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
    .btn-submit {
      background: linear-gradient(to right, #42a5f5, #1e88e5);
      color: white;
      border: none;
      border-radius: 30px;
      padding: 10px;
      font-weight: 600;
      width: 100%;
      transition: transform 0.3s ease;
    }
    .btn-submit:hover { transform: scale(1.02); }
    .form-label { font-weight: 600; }
  </style>
</head>
<body>
  <div class="form-container">
    <h3><i class="fas fa-envelope"></i> Forgot Password</h3>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="mt-3 text-start">
      <div class="mb-3">
        <label class="form-label">User Code</label>
        <input type="text" name="username" class="form-control" placeholder="SV2023001" required>
      </div>
      <button type="submit" class="btn-submit">
        <i class="fas fa-paper-plane me-1"></i> Send OTP via Email
      </button>
    </form>
  </div>
</body>
</html>
