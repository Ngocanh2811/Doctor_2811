<?php
// settings.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: logindoctor.php');
    exit;
}
require_once __DIR__ . '/db_config.php';
$doctorId = $_SESSION['linked_id'];

// Initialize messages
$successProfile = '';
$successPass    = '';
$errorPass      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['form_type']) && $_POST['form_type'] === 'profile') {
        // Cập nhật thông tin cá nhân
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');
        $phone     = trim($_POST['phone']      ?? '');

        $stmt = $conn->prepare(
            "UPDATE doctor
               SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?
             WHERE DoctorID = ?"
        );
        $stmt->bind_param('ssssi', $firstName, $lastName, $email, $phone, $doctorId);
        if ($stmt->execute()) {
            $successProfile = 'Cập nhật thông tin cá nhân thành công.';
        }
        $stmt->close();
    }

    if (!empty($_POST['form_type']) && $_POST['form_type'] === 'password') {
        // Đổi mật khẩu
        $oldPass     = $_POST['old_password']     ?? '';
        $newPass     = $_POST['new_password']     ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        // Lấy mật khẩu hiện tại (plain text)
        $pstmt = $conn->prepare(
            "SELECT u.PasswordHash
               FROM user u
              WHERE u.LinkedID = ? AND u.Role = 'doctor'"
        );
        $pstmt->bind_param('i', $doctorId);
        $pstmt->execute();
        $result       = $pstmt->get_result();
        $currentHash  = $result->fetch_assoc()['PasswordHash'] ?? '';
        $pstmt->close();

        // So sánh trực tiếp
        if ($oldPass !== $currentHash) {
            $errorPass = 'Mật khẩu cũ không đúng.';
        } elseif ($newPass !== $confirmPass) {
            $errorPass = 'Mật khẩu mới và xác nhận không khớp.';
        } else {
            // Cập nhật PasswordHash dưới dạng plain text
            $up = $conn->prepare(
                "UPDATE user
                   SET PasswordHash = ?
                 WHERE LinkedID = ? AND Role = 'doctor'"
            );
            $up->bind_param('si', $newPass, $doctorId);
            if ($up->execute()) {
                $successPass = 'Đổi mật khẩu thành công.';
            }
            $up->close();
        }
    }
}

// Lấy thông tin profile
$sql = <<<SQL
SELECT u.Username,
       d.FirstName,
       d.LastName,
       d.Email,
       d.PhoneNumber,
       u.LastLogin
  FROM doctor d
  JOIN user u ON u.LinkedID = d.DoctorID AND u.Role = 'doctor'
 WHERE d.DoctorID = ?
SQL;
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Doctor Portal – Cài đặt</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f3edf7; }
    .sidebar {
      width:240px;
      background:#c5dcff;
      min-height:100vh;
      padding:1rem;
      display:flex; flex-direction:column;
    }
    .sidebar .nav-link {
      color:#2f4f8a;
      margin-bottom:4px;
      border-radius:6px;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background:#95b8ff;
      color:#fff;
    }
    .content { padding:0 2rem 2rem; }
    .card-container {
      background:#fff;
      border-radius:12px;
      box-shadow:0 4px 12px rgba(0,0,0,.05);
      padding:1.5rem;
      margin-bottom:1.5rem;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <nav class="sidebar">
    <h3 class="text-white mb-4">Doctor Portal</h3>
    <ul class="nav nav-pills flex-column">
      <li class="nav-item"><a href="dashboard.php"        class="nav-link">Tổng quan</a></li>
      <li class="nav-item"><a href="patient.php"          class="nav-link">Bệnh nhân</a></li>
      <li class="nav-item"><a href="prescriptions.php"    class="nav-link">Đơn thuốc</a></li>
      <li class="nav-item"><a href="appointments.php"     class="nav-link">Cuộc hẹn</a></li>
      <li class="nav-item"><a href="question.php"         class="nav-link">Phản hồi thắc mắc</a></li>
      <li class="nav-item"><a href="settings.php"         class="nav-link active">Cài đặt</a></li>
    </ul>
    <div class="mt-auto">
      <a href="logout.php" class="btn btn-outline-light w-100 mt-3">Đăng xuất</a>
    </div>
  </nav>

  <!-- Main content -->
  <div class="flex-grow-1">
    <div class="container-fluid mt-3 mb-4">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
          <strong class="mb-0">Cài đặt</strong>
          <span class="fw-bold"><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
      </div>
    </div>

    <div class="content">
      <!-- Thông báo cập nhật Profile -->
      <?php if ($successProfile): ?>
        <div class="alert alert-success"><?= $successProfile ?></div>
      <?php endif; ?>

      <!-- Form Profile -->
      <div class="card-container">
        <h5 class="mb-4">Thông tin cá nhân</h5>
        <form method="post">
          <input type="hidden" name="form_type" value="profile">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Họ</label>
              <input name="first_name" type="text" class="form-control"
                     value="<?= htmlspecialchars($profile['FirstName']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Tên</label>
              <input name="last_name" type="text" class="form-control"
                     value="<?= htmlspecialchars($profile['LastName']) ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control"
                   value="<?= htmlspecialchars($profile['Email']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Số điện thoại</label>
            <input name="phone" type="text" class="form-control"
                   value="<?= htmlspecialchars($profile['PhoneNumber']) ?>">
          </div>
          <button type="submit" class="btn btn-primary">Lưu thông tin</button>
        </form>
      </div>

      <!-- Thông báo đổi mật khẩu -->
      <?php if ($errorPass || $successPass): ?>
        <div class="alert <?= $errorPass ? 'alert-danger' : 'alert-success' ?>">
          <?= $errorPass ?: $successPass ?>
        </div>
      <?php endif; ?>

      <!-- Form Đổi mật khẩu -->
      <div class="card-container">
        <h5 class="mb-4">Đổi mật khẩu</h5>
        <form method="post">
          <input type="hidden" name="form_type" value="password">
          <div class="mb-3">
            <label class="form-label">Mật khẩu cũ</label>
            <input name="old_password" type="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Mật khẩu mới</label>
            <input name="new_password" type="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Xác nhận mật khẩu</label>
            <input name="confirm_password" type="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
