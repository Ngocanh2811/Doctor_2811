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

        // Xử lý upload ảnh đại diện
        $avatarFileName = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
            $fileTmpPath = $_FILES['avatar']['tmp_name'];
            $fileName = $_FILES['avatar']['name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($fileExt, $allowedExts)) {
                $avatarFileName = 'avatar_doctor_' . $doctorId . '.' . $fileExt;
                $uploadDir = __DIR__ . '/uploads/avatars/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destPath = $uploadDir . $avatarFileName;
                if (!move_uploaded_file($fileTmpPath, $destPath)) {
                    $avatarFileName = null;
                }
            }
        }

        // Cập nhật database
        if ($avatarFileName) {
            $stmt = $conn->prepare(
                "UPDATE doctor
                 SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?, Avatar = ?
                 WHERE DoctorID = ?"
            );
            $stmt->bind_param('sssssi', $firstName, $lastName, $email, $phone, $avatarFileName, $doctorId);
        } else {
            $stmt = $conn->prepare(
                "UPDATE doctor
                 SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?
                 WHERE DoctorID = ?"
            );
            $stmt->bind_param('ssssi', $firstName, $lastName, $email, $phone, $doctorId);
        }

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

        if ($oldPass !== $currentHash) {
            $errorPass = 'Mật khẩu cũ không đúng.';
        } elseif ($newPass !== $confirmPass) {
            $errorPass = 'Mật khẩu mới và xác nhận không khớp.';
        } else {
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
       d.Avatar,
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Doctor Portal – Cài đặt</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: #f3edf7;
      color: #2f4f8a;
      min-height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
    }

    .d-flex {
      height: 100vh;
      overflow: hidden;
    }

    .sidebar {
      width: 240px;
      background: #c5dcff;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    .sidebar h3 {
      color: white;
      margin-bottom: 1.5rem;
      font-weight: 700;
      font-size: 1.5rem;
    }
    .sidebar .nav-link {
      color: #2f4f8a;
      margin-bottom: 0.3rem;
      border-radius: 0.375rem;
      font-weight: 600;
      transition: background-color 0.3s, color 0.3s;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: #95b8ff;
      color: #fff;
    }
    .sidebar .btn-outline-light {
      margin-top: auto;
      font-weight: 600;
      border-radius: 0.375rem;
    }

    .main-content {
      flex-grow: 1;
      padding: 1.5rem 2rem 2rem;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    .header-card {
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
      border-radius: 0.5rem;
      margin-bottom: 1.5rem;
      background: #fff;
      flex-shrink: 0;
    }
    .header-card .card-body {
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: 700;
      font-size: 1.2rem;
      color: #2f4f8a;
    }

    .content {
      max-width: 720px;
      margin: 0 auto;
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 3rem;
      padding-bottom: 3rem;
    }

    .card-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 1.5rem 2rem;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
    }

    /* Form Thông tin cá nhân */
    .card-profile form {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
      align-items: flex-start;
    }

    .avatar {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      border: 3px solid #95b8ff;
      flex-shrink: 0;
      margin-bottom: 1rem;
    }

    .profile-fields {
      flex: 1 1 350px;
      display: flex;
      flex-direction: column;
    }

    .profile-fields .row.g-3 {
      flex-grow: 1;
    }

    .btn-primary {
      background-color: #4a90e2;
      border-color: #4a90e2;
      font-weight: 600;
      transition: background-color 0.3s ease;
      width: 160px;
      align-self: flex-start;
      margin-top: 1rem;
    }

    .btn-primary:hover {
      background-color: #357abd;
      border-color: #357abd;
    }

    /* Form Đổi mật khẩu */
    .card-password {
      max-width: 400px;
      width: 100%;
      margin: 0 auto; /* căn giữa */
      padding-bottom: 1rem;
    }

    .card-password form {
      display: flex;
      flex-direction: column;
    }

    .card-password button {
      margin-top: 1rem;
    }

    /* Responsive nhỏ hơn */
    @media (max-width: 576px) {
      .card-profile form {
        flex-direction: column;
        align-items: center;
      }
      .profile-fields {
        width: 100%;
      }
      .avatar {
        margin-bottom: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <nav class="sidebar">
      <h3>Doctor Portal</h3>
      <ul class="nav nav-pills flex-column">
        <li class="nav-item"><a href="dashboard.php" class="nav-link">Tổng quan</a></li>
        <li class="nav-item"><a href="patient.php" class="nav-link">Bệnh nhân</a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link">Đơn thuốc</a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link">Cuộc hẹn</a></li>
        <li class="nav-item"><a href="question.php" class="nav-link">Phản hồi thắc mắc</a></li>
        <li class="nav-item"><a href="settings.php" class="nav-link active">Cài đặt</a></li>
      </ul>
      <div class="mt-auto">
        <a href="logout.php" class="btn btn-outline-light w-100 mt-3">Đăng xuất</a>
      </div>
    </nav>

    <!-- Main content -->
    <main class="main-content">
      <div class="header-card card">
        <div class="card-body">
          <span>Cài đặt</span>
          <span><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
      </div>

      <div class="content">
        <!-- Form Thông tin cá nhân -->
        <section class="card-container card-profile">
          <?php
            $avatarPath = 'uploads/avatars/default-avatar.jpg';
            if (!empty($profile['Avatar']) && file_exists(__DIR__ . '/uploads/avatars/' . $profile['Avatar'])) {
                $avatarPath = 'uploads/avatars/' . $profile['Avatar'];
            }
          ?>
          <form method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="form_type" value="profile" />
            <div style="display:flex; gap:2rem; flex-wrap: wrap; align-items:flex-start;">
              <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Ảnh đại diện" class="avatar" />
              <div class="profile-fields">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label" for="first_name">Họ</label>
                    <input id="first_name" name="first_name" type="text" class="form-control" value="<?= htmlspecialchars($profile['FirstName']) ?>" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="last_name">Tên</label>
                    <input id="last_name" name="last_name" type="text" class="form-control" value="<?= htmlspecialchars($profile['LastName']) ?>" required />
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" type="email" class="form-control" value="<?= htmlspecialchars($profile['Email']) ?>" required />
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input id="phone" name="phone" type="text" class="form-control" value="<?= htmlspecialchars($profile['PhoneNumber']) ?>" />
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="avatar">Ảnh đại diện</label>
                    <input id="avatar" name="avatar" type="file" class="form-control" accept="image/*" />
                  </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Lưu thông tin</button>
              </div>
            </div>
          </form>
        </section>

        <!-- Form Đổi mật khẩu -->
        <section class="card-container card-password">
          <h5 class="mb-4 text-center" style="font-weight:700;">Đổi mật khẩu</h5>

          <?php if ($errorPass || $successPass): ?>
            <div class="alert <?= $errorPass ? 'alert-danger' : 'alert-success' ?>" role="alert">
              <?= htmlspecialchars($errorPass ?: $successPass) ?>
            </div>
          <?php endif; ?>

          <form method="post" novalidate>
            <input type="hidden" name="form_type" value="password" />
            <div class="mb-3">
              <label class="form-label" for="old_password">Mật khẩu cũ</label>
              <input id="old_password" name="old_password" type="password" class="form-control" placeholder="Nhập mật khẩu cũ" required />
            </div>
            <div class="mb-3">
              <label class="form-label" for="new_password">Mật khẩu mới</label>
              <input id="new_password" name="new_password" type="password" class="form-control" placeholder="Nhập mật khẩu mới" required />
            </div>
            <div class="mb-4">
              <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
              <input id="confirm_password" name="confirm_password" type="password" class="form-control" placeholder="Nhập lại mật khẩu mới" required />
            </div>
            <button type="submit" class="btn btn-primary w-100">Đổi mật khẩu</button>
          </form>
        </section>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
