<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: logindoctor.php');
    exit;
}

require_once __DIR__ . '/db_config.php';

if (!$conn) {
    die("Kết nối DB thất bại: " . mysqli_connect_error());
}

$doctorId = $_SESSION['linked_id'];
$errors = [];
$success = '';

// === Xử lý thêm mới hoặc sửa đơn thuốc ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $patientId = intval($_POST['patient_id'] ?? 0);
    $medName = trim($_POST['medication_name'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    if (!$patientId) $errors[] = 'Chọn bệnh nhân';
    if ($medName === '') $errors[] = 'Nhập tên thuốc';
    if ($dosage === '') $errors[] = 'Nhập liều dùng';
    if ($startDate === '') $errors[] = 'Chọn ngày bắt đầu';

    if (!$errors) {
        if ($action === 'add') {
            $sql = "INSERT INTO medication (PatientID, PrescribedByID, MedicationName, Dosage, Instructions, StartDate, EndDate)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) die("Prepare lỗi: " . $conn->error);
            $endDateVal = $endDate ?: null;
            $stmt->bind_param('iisssss', $patientId, $doctorId, $medName, $dosage, $instructions, $startDate, $endDateVal);
            if ($stmt->execute()) {
                $success = 'Thêm đơn thuốc thành công.';
            } else {
                $errors[] = 'Lỗi khi thêm đơn thuốc: ' . $stmt->error;
            }
        } elseif ($action === 'edit') {
            $medId = intval($_POST['medication_id'] ?? 0);
            if (!$medId) {
                $errors[] = 'ID đơn thuốc không hợp lệ.';
            } else {
                $sql = "UPDATE medication SET PatientID=?, MedicationName=?, Dosage=?, Instructions=?, StartDate=?, EndDate=?
                        WHERE MedicationID=? AND PrescribedByID=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) die("Prepare lỗi: " . $conn->error);
                $endDateVal = $endDate ?: null;
                $stmt->bind_param('isssssii', $patientId, $medName, $dosage, $instructions, $startDate, $endDateVal, $medId, $doctorId);
                if ($stmt->execute()) {
                    $success = 'Cập nhật đơn thuốc thành công.';
                } else {
                    $errors[] = 'Lỗi khi cập nhật đơn thuốc: ' . $stmt->error;
                }
            }
        }
    }
}

// Xóa đơn thuốc
if (isset($_GET['delete']) && intval($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM medication WHERE MedicationID=? AND PrescribedByID=?");
    if (!$stmt) die("Prepare lỗi: " . $conn->error);
    $stmt->bind_param('ii', $delId, $doctorId);
    if ($stmt->execute()) {
        $success = 'Xóa đơn thuốc thành công.';
    } else {
        $errors[] = 'Lỗi khi xóa đơn thuốc.';
    }
}

// Lấy danh sách đơn thuốc
$sql = <<<SQL
SELECT
    m.MedicationID,
    p.PatientID,
    CONCAT(p.FirstName, ' ', p.LastName) AS PatientName,
    m.MedicationName,
    m.Dosage,
    DATE_FORMAT(m.StartDate, '%Y-%m-%d') AS StartDate,
    DATE_FORMAT(m.EndDate, '%Y-%m-%d') AS EndDate,
    m.Instructions
FROM medication m
JOIN patient p ON m.PatientID = p.PatientID
WHERE m.PrescribedByID = ?
ORDER BY m.StartDate DESC
SQL;
$stmt = $conn->prepare($sql);
if (!$stmt) die("Prepare lỗi: " . $conn->error);
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$medications = $stmt->get_result();

// Lấy danh sách bệnh nhân
$patientStmt = $conn->prepare("SELECT PatientID, CONCAT(FirstName, ' ', LastName) AS FullName FROM patient ORDER BY LastName, FirstName");
if (!$patientStmt) die("Prepare lỗi: " . $conn->error);
$patientStmt->execute();
$patients = $patientStmt->get_result();

// Lấy dữ liệu edit
$editData = null;
if (isset($_GET['edit']) && intval($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmtEdit = $conn->prepare("SELECT * FROM medication WHERE MedicationID=? AND PrescribedByID=?");
    if (!$stmtEdit) die("Prepare lỗi: " . $conn->error);
    $stmtEdit->bind_param('ii', $editId, $doctorId);
    $stmtEdit->execute();
    $editData = $stmtEdit->get_result()->fetch_assoc();
}

// --- PHẦN XỬ LÝ THỐNG KÊ CHO CHART ---
$now = new DateTime();
$medCountByPatient = [];
$medCountByName = [];
$medCountByStatus = ['Còn hiệu lực' => 0, 'Hết hiệu lực' => 0];

foreach ($medications as $med) {
    $patientName = $med['PatientName'];
    $medCountByPatient[$patientName] = ($medCountByPatient[$patientName] ?? 0) + 1;

    $medName = $med['MedicationName'];
    $medCountByName[$medName] = ($medCountByName[$medName] ?? 0) + 1;

    $startDate = new DateTime($med['StartDate']);
    $endDate = $med['EndDate'] ? new DateTime($med['EndDate']) : null;

    if ($endDate === null || $endDate >= $now) {
        $medCountByStatus['Còn hiệu lực']++;
    } else {
        $medCountByStatus['Hết hiệu lực']++;
    }
}

arsort($medCountByPatient);
$medCountByPatient = array_slice($medCountByPatient, 0, 5, true);

arsort($medCountByName);
$medCountByName = array_slice($medCountByName, 0, 5, true);

$medications->data_seek(0);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Doctor Dashboard – Kê đơn thuốc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
  <style>
    body { background: #f3edf7; font-weight: 600; color: #1a1a1a; }
    .sidebar {
      width: 240px;
      background: #c5dcff;
      min-height: 100vh;
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
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .content {
      padding: 20px;
    }
    .stat-card {
      border-radius: 0.75rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: box-shadow 0.3s;
    }
    .stat-card:hover {
      box-shadow: 0 6px 20px rgba(111,66,193,0.4);
    }
    table.dataTable thead {
      background: #f8f9fa;
    }
    .form-section {
      background: #fff;
      padding: 20px;
      border-radius: 0.75rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      font-weight: 600;
    }
    .form-section h5 {
      margin-bottom: 20px;
      color: #7c3aed;
    }
    .btn-primary {
      background-color: #7c3aed;
      border-color: #7c3aed;
    }
    .btn-primary:hover {
      background-color: #6b31d4;
      border-color: #6b31d4;
    }
    .btn-secondary {
      background-color: #94b8ff;
      border-color: #94b8ff;
      color: #023047;
    }
    .btn-secondary:hover {
      background-color: #7f9cf9;
      border-color: #7f9cf9;
      color: #fff;
    }
    .btn-warning {
      background-color: #c4b5fd;
      border-color: #c4b5fd;
      color: #023047;
    }
    .btn-warning:hover {
      background-color: #a594fc;
      border-color: #a594fc;
      color: #fff;
    }
    .btn-danger {
      background-color: #d8b4fe;
      border-color: #d8b4fe;
      color: #023047;
    }
    .btn-danger:hover {
      background-color: #c999fd;
      border-color: #c999fd;
      color: #fff;
    }
    /* Chart container styles */
    .chart-container {
      background: white;
      padding: 15px;
      border-radius: 0.75rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    canvas {
      max-width: 100% !important;
      height: 250px !important;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <nav class="sidebar d-flex flex-column">
    <h3 class="text-white mb-4">Doctor Portal</h3>
    <ul class="nav nav-pills flex-column">
      <li class="nav-item"><a href="dashboard.php" class="nav-link">Tổng quan</a></li>
      <li class="nav-item"><a href="patient.php" class="nav-link">Bệnh nhân</a></li>
      <li class="nav-item"><a href="prescriptions.php" class="nav-link active">Đơn thuốc</a></li>
      <li class="nav-item"><a href="appointments.php" class="nav-link">Cuộc hẹn</a></li>
      <li class="nav-item"><a href="question.php" class="nav-link">Phản hồi thắc mắc</a></li>
      <li class="nav-item"><a href="settings.php" class="nav-link">Cài đặt</a></li>
    </ul>
    <div class="mt-auto">
      <a href="logout.php" class="btn btn-outline-light w-100">Đăng xuất</a>
    </div>
  </nav>

  <div class="flex-grow-1 p-4">
    <div class="topbar">
      <h4>Đơn thuốc</h4>
      <div><?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
    </div>

    <div class="content">

      <!-- Chart báo cáo -->
      <div class="row">
        <div class="col-md-4 chart-container">
          <canvas id="chartByPatient"></canvas>
        </div>
        <div class="col-md-4 chart-container">
          <canvas id="chartByMedication"></canvas>
        </div>
        <div class="col-md-4 chart-container">
          <canvas id="chartByStatus"></canvas>
        </div>
      </div>

      <div class="row g-4 mt-4">
        <div class="col-lg-4">
          <div class="form-section">
            <h5><?= $editData ? 'Chỉnh sửa đơn thuốc' : 'Thêm mới đơn thuốc' ?></h5>
            <form method="post" action="prescriptions.php" novalidate>
              <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'add' ?>">
              <?php if ($editData): ?>
                <input type="hidden" name="medication_id" value="<?= (int)$editData['MedicationID'] ?>">
              <?php endif; ?>

              <div class="mb-3">
                <label for="patient_id" class="form-label">Bệnh nhân</label>
                <select id="patient_id" name="patient_id" class="form-select" required>
                  <option value="">-- Chọn bệnh nhân --</option>
                  <?php
                  $patients->data_seek(0);
                  while ($p = $patients->fetch_assoc()):
                  ?>
                    <option value="<?= $p['PatientID'] ?>" <?= ($editData && $editData['PatientID'] == $p['PatientID']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($p['FullName']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="medication_name" class="form-label">Tên thuốc</label>
                <input type="text" id="medication_name" name="medication_name" class="form-control" required
                       value="<?= htmlspecialchars($editData['MedicationName'] ?? '') ?>">
              </div>

              <div class="mb-3">
                <label for="dosage" class="form-label">Liều dùng</label>
                <input type="text" id="dosage" name="dosage" class="form-control" required
                       value="<?= htmlspecialchars($editData['Dosage'] ?? '') ?>">
              </div>

              <div class="mb-3">
                <label for="start_date" class="form-label">Ngày bắt đầu</label>
                <input type="date" id="start_date" name="start_date" class="form-control" required
                       value="<?= htmlspecialchars($editData['StartDate'] ?? '') ?>">
              </div>

              <div class="mb-3">
                <label for="end_date" class="form-label">Ngày kết thúc</label>
                <input type="date" id="end_date" name="end_date" class="form-control"
                       value="<?= htmlspecialchars($editData['EndDate'] ?? '') ?>">
              </div>

              <div class="mb-3">
                <label for="instructions" class="form-label">Hướng dẫn</label>
                <textarea id="instructions" name="instructions" class="form-control" rows="3"><?= htmlspecialchars($editData['Instructions'] ?? '') ?></textarea>
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary"><?= $editData ? 'Cập nhật' : 'Thêm mới' ?></button>
                <?php if ($editData): ?>
                  <a href="prescriptions.php" class="btn btn-secondary">Hủy chỉnh sửa</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card p-3 bg-white stat-card">
            <table id="presTable" class="table table-striped" style="width:100%">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Bệnh nhân</th>
                  <th>Thuốc</th>
                  <th>Liều dùng</th>
                  <th>Ngày bắt đầu</th>
                  <th>Ngày kết thúc</th>
                  <th>Hướng dẫn</th>
                  <th>Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $medications->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['MedicationID']) ?></td>
                  <td><?= htmlspecialchars($row['PatientName']) ?></td>
                  <td><?= htmlspecialchars($row['MedicationName']) ?></td>
                  <td><?= htmlspecialchars($row['Dosage']) ?></td>
                  <td><?= htmlspecialchars($row['StartDate']) ?></td>
                  <td><?= htmlspecialchars($row['EndDate']) ?></td>
                  <td><?= htmlspecialchars($row['Instructions']) ?></td>
                  <td>
                    <a href="?edit=<?= $row['MedicationID'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                    <a href="?delete=<?= $row['MedicationID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa đơn thuốc này?');">Xóa</a>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div> <!-- /.row -->
    </div> <!-- /.content -->
  </div>
</div>

<!-- Thư viện JS cần thiết -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // Khởi tạo DataTables cho bảng
  $(document).ready(function() {
    $('#presTable').DataTable();
  });

  // Dữ liệu PHP sang JS cho biểu đồ
  const medCountByPatient = <?= json_encode($medCountByPatient) ?>;
  const medCountByName = <?= json_encode($medCountByName) ?>;
  const medCountByStatus = <?= json_encode($medCountByStatus) ?>;

  // Biểu đồ số đơn theo bệnh nhân
  const ctxPatient = document.getElementById('chartByPatient').getContext('2d');
  new Chart(ctxPatient, {
    type: 'bar',
    data: {
      labels: Object.keys(medCountByPatient),
      datasets: [{
        label: 'Số đơn thuốc',
        data: Object.values(medCountByPatient),
        backgroundColor: 'rgba(124, 58, 237, 0.7)'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Top 5 Bệnh nhân có nhiều đơn thuốc nhất' }
      },
      scales: {
        y: { beginAtZero: true, precision: 0 }
      }
    }
  });

  // Biểu đồ số đơn theo thuốc
  const ctxMedication = document.getElementById('chartByMedication').getContext('2d');
  new Chart(ctxMedication, {
    type: 'bar',
    data: {
      labels: Object.keys(medCountByName),
      datasets: [{
        label: 'Số đơn thuốc',
        data: Object.values(medCountByName),
        backgroundColor: 'rgba(99, 102, 241, 0.7)'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Top 5 Thuốc được kê nhiều nhất' }
      },
      scales: {
        y: { beginAtZero: true, precision: 0 }
      }
    }
  });

  // Biểu đồ trạng thái đơn thuốc
  const ctxStatus = document.getElementById('chartByStatus').getContext('2d');
  new Chart(ctxStatus, {
    type: 'pie',
    data: {
      labels: Object.keys(medCountByStatus),
      datasets: [{
        label: 'Số đơn',
        data: Object.values(medCountByStatus),
        backgroundColor: [
          'rgba(16, 185, 129, 0.7)',   // xanh lá
          'rgba(239, 68, 68, 0.7)'     // đỏ
        ]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: 'Tỉ lệ đơn thuốc còn và hết hiệu lực' }
      }
    }
  });
</script>
</body>
</html>
