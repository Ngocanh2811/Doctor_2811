<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: logindoctor.php');
    exit;
}
require_once __DIR__ . '/db_config.php';  // Kết nối DB
$doctorId = $_SESSION['linked_id'];

// 1) Tổng số bệnh nhân (dựa trên appointment)
$sqlPatients = "SELECT COUNT(DISTINCT PatientID) AS cnt FROM appointment WHERE DoctorID = ?";
$stmt = $conn->prepare($sqlPatients);
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$totalPatients = (int)$stmt->get_result()->fetch_assoc()['cnt'];

// 2) Đơn thuốc đang chờ xử lý
$sqlPres = "SELECT COUNT(*) AS cnt FROM medication WHERE PrescribedByID = ?";
$pstmt = $conn->prepare($sqlPres);
$pstmt->bind_param('i', $doctorId);
$pstmt->execute();
$pendingPrescriptions = (int)$pstmt->get_result()->fetch_assoc()['cnt'];

// 3) Cuộc hẹn sắp tới (AppointmentDate > hiện tại)
$sqlAppts = "SELECT COUNT(*) AS cnt FROM appointment WHERE DoctorID = ? AND (AppointmentDate > CURDATE() OR (AppointmentDate = CURDATE() AND AppointmentTime >= CURTIME()))";
$astmt = $conn->prepare($sqlAppts);
$astmt->bind_param('i', $doctorId);
$astmt->execute();
$pendingAppointments = (int)$astmt->get_result()->fetch_assoc()['cnt'];

// 4) Phản hồi bệnh nhân (question)
$sqlFb = "SELECT COUNT(*) AS cnt FROM question WHERE DoctorID = ?";
$fstmt = $conn->prepare($sqlFb);
$fstmt->bind_param('i', $doctorId);
$fstmt->execute();
$patientFeedbacks = (int)$fstmt->get_result()->fetch_assoc()['cnt'];

// 5) Thống kê hẹn trong ngày theo khung giờ
$times = ['08:00','10:00','12:00','14:00','16:00'];
$timeData = [];
foreach ($times as $t) {
    $q = "SELECT COUNT(*) AS cnt FROM appointment WHERE DoctorID = ? AND AppointmentDate = CURDATE() AND AppointmentTime BETWEEN ? AND ADDTIME(?, '01:59:59')";
    $ts = $conn->prepare($q);
    $ts->bind_param('iss', $doctorId, $t, $t);
    $ts->execute();
    $timeData[] = (int)$ts->get_result()->fetch_assoc()['cnt'];
}

// 6) Thống kê hẹn trong 7 ngày gần nhất
$days = [];
$dayData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $days[] = date('D d/m', strtotime($date));
    $stmtDay = $conn->prepare("SELECT COUNT(*) AS cnt FROM appointment WHERE DoctorID = ? AND AppointmentDate = ?");
    $stmtDay->bind_param('is', $doctorId, $date);
    $stmtDay->execute();
    $dayData[] = (int)$stmtDay->get_result()->fetch_assoc()['cnt'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Doctor Dashboard – Tổng quan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Style giống mẫu bạn gửi */
    body {
      background: #f3edf7;
    }
    .sidebar {
      width: 240px;
      background: #c5dcff;
      min-height: 100vh;
      color: #1a1a1a;
      padding: 1rem;
      font-weight: 600;
    }
    .sidebar .nav-link {
      color: #2f4f8a;
      margin-bottom: 4px;
      font-weight: 500;
      transition: background-color 0.3s, color 0.3s;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: #95b8ff;
      color: white;
      border-radius: 6px;
      font-weight: 700;
    }
    .topbar {
      background: #fff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      padding: 0.75rem 1.5rem;
      margin-bottom: 1.5rem;
      border-radius: 8px;
      font-weight: 600;
    }
    .stat-card {
      border-radius: 0.75rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: box-shadow 0.3s;
    }
    .stat-card:hover {
      box-shadow: 0 6px 20px rgba(111,66,193,0.4);
    }
    .card h6 {
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: #6f42c1;
    }
    .text-primary { color: #7c3aed !important; }
    .text-success { color: #9f7aea !important; }
    .text-warning { color: #c4b5fd !important; }
    .text-danger { color: #d8b4fe !important; }
    .btn-outline-primary { color: #7c3aed; border-color: #7c3aed; }
    .btn-outline-primary:hover { background-color: #7c3aed; color: white; }
    .btn-outline-success { color: #9f7aea; border-color: #9f7aea; }
    .btn-outline-success:hover { background-color: #9f7aea; color: white; }
    .btn-outline-warning { color: #c4b5fd; border-color: #c4b5fd; }
    .btn-outline-warning:hover { background-color: #c4b5fd; color: white; }
    .btn-outline-danger { color: #d8b4fe; border-color: #d8b4fe; }
    .btn-outline-danger:hover { background-color: #d8b4fe; color: white; }
    .chart-card {
      background: #fff;
      border-radius: 0.75rem;
      padding: 1rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      margin-bottom: 1.5rem;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <nav class="sidebar d-flex flex-column">
    <h3 class="text-white mb-4">Doctor Portal</h3>
    <ul class="nav nav-pills flex-column">
      <li class="nav-item"><a href="dashboard.php" class="nav-link active">Tổng quan</a></li>
      <li class="nav-item"><a href="patient.php" class="nav-link">Bệnh nhân</a></li>
      <li class="nav-item"><a href="prescriptions.php" class="nav-link">Đơn thuốc</a></li>
      <li class="nav-item"><a href="appointments.php" class="nav-link">Cuộc hẹn</a></li>
      <li class="nav-item"><a href="question.php" class="nav-link">Phản hồi thắc mắc</a></li>
      <li class="nav-item"><a href="settings.php" class="nav-link">Cài đặt</a></li>
    </ul>
    <div class="mt-auto">
      <a href="logout.php" class="btn btn-outline-light w-100">Đăng xuất</a>
    </div>
  </nav>
  <div class="flex-grow-1 p-4">
    <div class="topbar d-flex justify-content-between align-items-center">
      <h4>Tổng quan</h4>
      <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
    </div>
    <div class="container mt-4">
      <div class="row g-4 mb-5">
        <div class="col-md-3">
          <div class="card stat-card p-4 text-center bg-white">
            <h6>Tổng bệnh nhân</h6>
            <h3 class="text-primary"><?= $totalPatients ?></h3>
            <a href="patient.php" class="btn btn-outline-primary mt-3">Xem chi tiết</a>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card p-4 text-center bg-white">
            <h6>Đơn thuốc</h6>
            <h3 class="text-success"><?= $pendingPrescriptions ?></h3>
            <a href="prescriptions.php" class="btn btn-outline-success mt-3">Xem chi tiết</a>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card p-4 text-center bg-white">
            <h6>Cuộc hẹn</h6>
            <h3 class="text-warning"><?= $pendingAppointments ?></h3>
            <a href="appointments.php" class="btn btn-outline-warning mt-3">Xem chi tiết</a>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card p-4 text-center bg-white">
            <h6> Phản hồi thắc mắc</h6>
            <h3 class="text-danger"><?= $patientFeedbacks ?></h3>
            <a href="question.php" class="btn btn-outline-danger mt-3">Xem chi tiết</a>
          </div>
        </div>
      </div>
      <div class="row g-4 mb-5">
        <div class="col-lg-6">
          <div class="chart-card">
            <h6>Số hẹn hôm nay</h6>
            <canvas id="todayChart"></canvas>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="chart-card">
            <h6>Hẹn trong 7 ngày</h6>
            <canvas id="weekChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const times = <?= json_encode($times) ?>;
  const timeData = <?= json_encode($timeData) ?>;
  new Chart(document.getElementById('todayChart'), {
    type: 'bar',
    data: {
      labels: times,
      datasets: [{
        label: 'Số hẹn',
        data: timeData,
        backgroundColor: '#7c3aed' // tím đậm
      }]
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true, stepSize: 1 } }
    }
  });

  const days = <?= json_encode($days) ?>;
  const dayData = <?= json_encode($dayData) ?>;
  new Chart(document.getElementById('weekChart'), {
    type: 'line',
    data: {
      labels: days,
      datasets: [{
        label: 'Số hẹn',
        data: dayData,
        borderColor: '#9f7aea',
        fill: false,
        tension: 0.3,
      }]
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true, stepSize: 1 } }
    }
  });
</script>
</body>
</html>
