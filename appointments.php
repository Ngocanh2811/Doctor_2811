<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: logindoctor.php');
    exit;
}
require_once __DIR__ . '/db_config.php';
$doctorId = $_SESSION['linked_id'];

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $pid    = intval($_POST['patient_id']);
        $date   = $_POST['date'];
        $time   = $_POST['time'];
        $reason = trim($_POST['reason']);
        $status = $_POST['status'];
        $stmt = $conn->prepare("
            INSERT INTO appointment
              (DoctorID, PatientID, AppointmentDate, AppointmentTime, Reason, Status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iissss', $doctorId, $pid, $date, $time, $reason, $status);
        $stmt->execute();

    } elseif ($action === 'update_status') {
        $aid       = intval($_POST['appt_id']);
        $newStatus = $_POST['status'];
        $stmt = $conn->prepare("
            UPDATE appointment
               SET Status = ?
             WHERE AppointmentID = ? AND DoctorID = ?
        ");
        $stmt->bind_param('sii', $newStatus, $aid, $doctorId);
        $stmt->execute();

    } elseif ($action === 'edit') {
        $aid    = intval($_POST['appt_id']);
        $pid    = intval($_POST['patient_id']);
        $date   = $_POST['date'];
        $time   = $_POST['time'];
        $reason = trim($_POST['reason']);
        $status = $_POST['status'];
        $stmt = $conn->prepare("
            UPDATE appointment
               SET PatientID=?, AppointmentDate=?, AppointmentTime=?, Reason=?, Status=?
             WHERE AppointmentID=? AND DoctorID=?
        ");
        $stmt->bind_param('issssii', $pid, $date, $time, $reason, $status, $aid, $doctorId);
        $stmt->execute();

    } elseif ($action === 'delete') {
        $aid = intval($_POST['appt_id']);
        $stmt = $conn->prepare("
            DELETE FROM appointment
             WHERE AppointmentID=? AND DoctorID=?
        ");
        $stmt->bind_param('ii', $aid, $doctorId);
        $stmt->execute();
    }

    header('Location: appointments.php');
    exit;
}

// Get patient list
$patients = $conn
    ->query("SELECT PatientID, CONCAT(FirstName,' ',LastName) AS Name FROM patient ORDER BY LastName, FirstName")
    ->fetch_all(MYSQLI_ASSOC);

// Get appointment list
$stmt = $conn->prepare("
    SELECT a.AppointmentID, a.PatientID,
           CONCAT(p.FirstName,' ',p.LastName) AS PatientName,
           a.AppointmentDate, a.AppointmentTime, a.Reason, a.Status
      FROM appointment a
      JOIN patient p ON a.PatientID = p.PatientID
     WHERE a.DoctorID = ?
  ORDER BY a.AppointmentDate, a.AppointmentTime
");
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Data statistics for charts ---
$apptCountByStatus = ['pending' => 0, 'confirmed' => 0, 'cancelled' => 0];
$apptCountByDate = [];
$apptStatusByDate = [];

foreach ($appointments as $appt) {
    // Count total by status
    if (isset($apptCountByStatus[$appt['Status']])) {
        $apptCountByStatus[$appt['Status']]++;
    }

    // Count appointments by date
    $date = $appt['AppointmentDate'];
    if (!isset($apptCountByDate[$date])) {
        $apptCountByDate[$date] = 0;
    }
    $apptCountByDate[$date]++;

    // Count status by date
    if (!isset($apptStatusByDate[$date])) {
        $apptStatusByDate[$date] = ['pending'=>0, 'confirmed'=>0, 'cancelled'=>0];
    }
    if (in_array($appt['Status'], ['pending', 'confirmed', 'cancelled'], true)) {
        $apptStatusByDate[$date][$appt['Status']]++;
    }
}

ksort($apptCountByDate);
ksort($apptStatusByDate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Doctor Portal – Appointment List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
  <style>
    body { background: #f3edf7; }
    .sidebar {
      width: 240px;
      background: #c5dcff;
      min-height: 100vh;
      padding: 1rem;
      display: flex;
      flex-direction: column;
    }
    .sidebar .nav-link {
      color: #2f4f8a;
      margin-bottom: 4px;
      border-radius: 6px;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: #95b8ff;
      color: #fff;
    }
    .content {
      padding: 0 2rem 2rem;
    }
    .card-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,.05);
      padding: 1rem;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <nav class="sidebar">
    <h3 class="text-white">Doctor Portal</h3>
    <ul class="nav nav-pills flex-column mt-3">
      <li class="nav-item"><a href="dashboard.php" class="nav-link">Overview</a></li>
      <li class="nav-item"><a href="patient.php" class="nav-link">Patients</a></li>
      <li class="nav-item"><a href="prescriptions.php" class="nav-link">Prescriptions</a></li>
      <li class="nav-item"><a href="appointments.php" class="nav-link active">Appointments</a></li>
      <li class="nav-item"><a href="question.php" class="nav-link">Feedback & Questions</a></li>
      <li class="nav-item"><a href="settings.php" class="nav-link">Settings</a></li>
    </ul>
    <div class="mt-auto">
      <a href="logout.php" class="btn btn-outline-light w-100 mt-3">Logout</a>
    </div>
  </nav>

  <div class="flex-grow-1">
    <div class="container-fluid mt-3 mb-4">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
          <h5 class="mb-0">Appointment List</h5>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
            Create New
          </button>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="row">
        <div class="col-md-4">
          <div class="card-container">
            <canvas id="chartApptByStatus"></canvas>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card-container mb-4">
            <canvas id="chartApptByDate"></canvas>
          </div>
          <div class="card-container">
            <canvas id="chartApptStatusByDate"></canvas>
          </div>
        </div>
      </div>

      <div class="card-container">
        <table id="apptTable" class="table table-striped">
          <thead>
            <tr>
              <th>Patient</th>
              <th>Date</th>
              <th>Time</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($appointments as $r): ?>
            <tr data-id="<?= $r['AppointmentID'] ?>"
                data-pid="<?= $r['PatientID'] ?>"
                data-date="<?= $r['AppointmentDate'] ?>"
                data-time="<?= substr($r['AppointmentTime'],0,5) ?>"
                data-reason="<?= htmlspecialchars($r['Reason'],ENT_QUOTES) ?>"
                data-status="<?= $r['Status'] ?>">
              <td><?= htmlspecialchars($r['PatientName']) ?></td>
              <td><?= date('d/m/Y',strtotime($r['AppointmentDate'])) ?></td>
              <td><?= substr($r['AppointmentTime'],0,5) ?></td>
              <td><?= htmlspecialchars($r['Reason']) ?></td>
              <td>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="appt_id" value="<?= $r['AppointmentID'] ?>">
                  <select name="status" class="form-select form-select-sm d-inline-block w-auto"
                          onchange="this.form.submit()">
                    <?php foreach(['pending'=>'Pending','confirmed'=>'Confirmed','cancelled'=>'Cancelled'] as $k=>$v): ?>
                      <option value="<?= $k ?>" <?= $r['Status']==$k?'selected':'' ?>>
                        <?= $v ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td>
                <button class="btn btn-sm btn-primary btn-edit" data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
                <button class="btn btn-sm btn-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post">
    <div class="modal-header">
      <h5 class="modal-title">Create Appointment</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <input type="hidden" name="action" value="create">
      <div class="mb-3">
        <label class="form-label">Patient</label>
        <select name="patient_id" class="form-select">
          <?php foreach($patients as $p): ?>
            <option value="<?= $p['PatientID'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="row mb-3">
        <div class="col">
          <label class="form-label">Date</label>
          <input type="date" name="date" class="form-control" required>
        </div>
        <div class="col">
          <label class="form-label">Time</label>
          <input type="time" name="time" class="form-control" required>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Reason</label>
        <textarea name="reason" class="form-control" rows="2"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary">Save</button>
    </div>
  </form>
</div></div></div>

<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post">
    <div class="modal-header">
      <h5 class="modal-title">Edit Appointment</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="appt_id" id="editApptId">
      <div class="mb-3">
        <label class="form-label">Patient</label>
        <select name="patient_id" id="editPatientId" class="form-select">
          <?php foreach($patients as $p): ?>
            <option value="<?= $p['PatientID'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="row mb-3">
        <div class="col">
          <label class="form-label">Date</label>
          <input type="date" name="date" id="editDate" class="form-control">
        </div>
        <div class="col">
          <label class="form-label">Time</label>
          <input type="time" name="time" id="editTime" class="form-control">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Reason</label>
        <textarea name="reason" id="editReason" class="form-control" rows="2"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" id="editStatus" class="form-select">
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary">Update</button>
    </div>
  </form>
</div></div></div>

<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post">
    <div class="modal-header">
      <h5 class="modal-title text-danger">Delete Appointment</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="appt_id" id="delApptId">
      <p>Are you sure you want to delete this appointment?</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
      <button type="submit" class="btn btn-danger">Delete</button>
    </div>
  </form>
</div></div></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  $(function(){
    $('#apptTable').DataTable({ pageLength: 10 });

    $('.btn-edit').click(function(){
      const tr = $(this).closest('tr');
      $('#editApptId').val(tr.data('id'));
      $('#editPatientId').val(tr.data('pid'));
      $('#editDate').val(tr.data('date'));
      $('#editTime').val(tr.data('time'));
      $('#editReason').val(tr.data('reason'));
      $('#editStatus').val(tr.data('status'));
    });
    $('.btn-delete').click(function(){
      $('#delApptId').val($(this).closest('tr').data('id'));
    });
  });

  // Pie chart - Appointments by status
  const apptCountByStatus = <?= json_encode($apptCountByStatus) ?>;
  const apptCountByDate = <?= json_encode($apptCountByDate) ?>;
  const apptStatusByDate = <?= json_encode($apptStatusByDate) ?>;

  const ctxStatus = document.getElementById('chartApptByStatus').getContext('2d');
  new Chart(ctxStatus, {
    type: 'pie',
    data: {
      labels: Object.keys(apptCountByStatus).map(s => {
        if(s==='pending') return 'Pending';
        if(s==='confirmed') return 'Confirmed';
        if(s==='cancelled') return 'Cancelled';
        return s;
      }),
      datasets: [{
        data: Object.values(apptCountByStatus),
        backgroundColor: [
          apptCountByStatus['pending'] > 0 ? '#facc15' : '#facc1555',
          apptCountByStatus['confirmed'] > 0 ? '#22c55e' : '#22c55e55',
          apptCountByStatus['cancelled'] > 0 ? '#ef4444' : '#ef444455',
        ],
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: { display: true, text: 'Appointments by Status' },
        legend: { position: 'bottom' }
      }
    }
  });

  // Bar chart - Appointments by date
  const ctxDate = document.getElementById('chartApptByDate').getContext('2d');
  new Chart(ctxDate, {
    type: 'bar',
    data: {
      labels: Object.keys(apptCountByDate),
      datasets: [{
        label: 'Number of Appointments',
        data: Object.values(apptCountByDate),
        backgroundColor: 'rgba(59, 130, 246, 0.7)',
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: { display: true, text: 'Appointments by Date' }
      },
      scales: {
        y: { beginAtZero: true, precision: 0 }
      }
    }
  });

  // Stacked Bar chart - Status ratio by date
  const ctxStatusByDate = document.getElementById('chartApptStatusByDate').getContext('2d');
  const labelsDate = Object.keys(apptStatusByDate);
  const dataPending = labelsDate.map(d => apptStatusByDate[d]?.pending ?? 0);
  const dataConfirmed = labelsDate.map(d => apptStatusByDate[d]?.confirmed ?? 0);
  const dataCancelled = labelsDate.map(d => apptStatusByDate[d]?.cancelled ?? 0);

  new Chart(ctxStatusByDate, {
    type: 'bar',
    data: {
      labels: labelsDate,
      datasets: [
        {
          label: 'Pending',
          data: dataPending,
          backgroundColor: '#facc15',
        },
        {
          label: 'Confirmed',
          data: dataConfirmed,
          backgroundColor: '#22c55e',
        },
        {
          label: 'Cancelled',
          data: dataCancelled,
          backgroundColor: '#ef4444',
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        title: { display: true, text: 'Appointment Status Ratio by Date' },
        legend: { position: 'bottom' }
      },
      scales: {
        x: { stacked: true },
        y: { stacked: true, beginAtZero: true, precision: 0 }
      }
    }
  });
</script>
</body>
</html>
