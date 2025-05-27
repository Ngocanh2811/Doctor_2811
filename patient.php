<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: logindoctor.php');
    exit;
}
require_once __DIR__ . '/db_config.php';

$doctorId = $_SESSION['linked_id'] ?? 0;

// 1) Get list of patients who have appointments with the doctor
$sql = <<<SQL
SELECT
    p.PatientID,
    CONCAT(p.FirstName, ' ', p.LastName) AS Name,
    p.Gender,
    DATE_FORMAT(p.DateOfBirth, '%d/%m/%Y') AS DOB,
    p.PhoneNumber,
    DATE_FORMAT(
      MIN(CASE WHEN a.AppointmentDate >= CURDATE() THEN a.AppointmentDate END),
      '%d/%m/%Y'
    ) AS NextApptDate
FROM appointment a
JOIN patient p ON a.PatientID = p.PatientID
WHERE a.DoctorID = ?
GROUP BY p.PatientID
ORDER BY p.LastName, p.FirstName
SQL;

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$patients = $stmt->get_result();

$patientIDs = [];
$patientsArr = [];
while ($row = $patients->fetch_assoc()) {
    $patientsArr[] = $row;
    $patientIDs[] = $row['PatientID'];
}

// 2) Get medical records for patients
$records = [];
if (!empty($patientIDs)) {
    $placeholders = implode(',', array_fill(0, count($patientIDs), '?'));
    $types = str_repeat('i', count($patientIDs));
    $sqlRecords = "SELECT * FROM medical_record WHERE PatientID IN ($placeholders) ORDER BY RecordID DESC";
    $rstmt = $conn->prepare($sqlRecords);

    // bind_param dynamic requires reference for unpacking in PHP < 8.0
    $refs = [];
    foreach ($patientIDs as $key => $value) {
        $refs[$key] = &$patientIDs[$key];
    }
    array_unshift($refs, $types);
    call_user_func_array([$rstmt, 'bind_param'], $refs);

    $rstmt->execute();
    $resultRecords = $rstmt->get_result();

    while ($rec = $resultRecords->fetch_assoc()) {
        $records[$rec['PatientID']][] = $rec;
    }
}

// --- PROCESS DATA FOR CHARTS ---

// Count patients by gender
$genderCount = ['Male' => 0, 'Female' => 0, 'Other' => 0];
// Age groups
$ageGroups = ['<20' => 0, '20-39' => 0, '40-59' => 0, '60+' => 0];

$today = new DateTime();

foreach ($patientsArr as $patient) {
    // Gender
    $g = trim($patient['Gender']);
    if (!in_array($g, ['Male', 'Female'])) $g = 'Other';
    $genderCount[$g] = ($genderCount[$g] ?? 0) + 1;

    // Calculate age
    $dob = DateTime::createFromFormat('d/m/Y', $patient['DOB']);
    if ($dob) {
        $age = $today->diff($dob)->y;
        if ($age < 20) $ageGroups['<20']++;
        elseif ($age < 40) $ageGroups['20-39']++;
        elseif ($age < 60) $ageGroups['40-59']++;
        else $ageGroups['60+']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Patients</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      background: #f3edf7;
    }
    .sidebar {
      width: 240px;
      background: #c5dcff;
      min-height: 100vh;
      color: rgb(248, 246, 246);
      padding: 1rem;
      font-weight: 600;
    }
    .sidebar .nav-link {
      color: #2f4f8a;
      margin-bottom: 4px;
      font-weight: 500;
      transition: background-color 0.3s, color 0.3s;
      border-radius: 6px;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: #95b8ff;
      color: white;
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
    .content-container {
      padding: 0 2rem 2rem 2rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgb(0 0 0 / 0.05);
    }
    table.dataTable thead {
      background: #f8f9fa;
    }
    /* Patient table style */
    #patientsTable tbody tr:hover {
      background-color: #e6f0ff;
    }
    /* Medical record details style */
    .record-details {
      margin-top: 10px;
      padding: 12px 20px;
      background: #fafafa;
      border-left: 5px solid #7c3aed;
      border-radius: 8px;
      box-shadow: 0 1px 6px rgb(124 58 237 / 0.1);
      font-size: 0.9rem;
      color: #3a3a3a;
    }
    .record-details h6 {
      color: #7c3aed;
      font-weight: 700;
      margin-bottom: 8px;
      font-size: 1rem;
    }
    .record-table {
      width: 100%;
      border-collapse: collapse;
    }
    .record-table th, .record-table td {
      padding: 8px 12px;
      border: 1px solid #ddd;
      text-align: left;
    }
    .record-table th {
      background-color: #e0d7ff;
      color: #5c3ea8;
    }
    .btn-record-toggle {
      font-size: 0.85rem;
      padding: 3px 8px;
    }
    hr {
      border-color: #ddd;
      margin-top: 2rem;
      margin-bottom: 2rem;
    }
    /* Canvas chart standard height */
    canvas {
      max-width: 100% !important;
      height: 250px !important;
    }
    .row.mb-4 > div {
      padding: 0 0.75rem;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <nav class="sidebar d-flex flex-column">
    <h3 class="mb-4">Doctor Portal</h3>
    <ul class="nav nav-pills flex-column">
      <li class="nav-item"><a href="dashboard.php" class="nav-link">Overview</a></li>
      <li class="nav-item"><a href="patient.php" class="nav-link active">Patients</a></li>
      <li class="nav-item"><a href="prescriptions.php" class="nav-link">Prescriptions</a></li>
      <li class="nav-item"><a href="appointments.php" class="nav-link">Appointments</a></li>
      <li class="nav-item"><a href="question.php" class="nav-link">Feedback & Questions</a></li>
      <li class="nav-item"><a href="settings.php" class="nav-link">Settings</a></li>
    </ul>
    <div class="mt-auto">
      <a href="logout.php" class="btn btn-outline-primary w-100">Logout</a>
    </div>
  </nav>

  <div class="flex-grow-1 p-4">
    <div class="topbar">
      <h4>My Patients</h4>
      <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
    </div>

    <div class="content-container">

      <h5 class="mb-4">Statistical Report</h5>
      <div class="row mb-4">
        <div class="col-lg-6 col-md-6 mb-3">
          <canvas id="chartGender"></canvas>
        </div>
        <div class="col-lg-6 col-md-6 mb-3">
          <canvas id="chartAgeGroup"></canvas>
        </div>
      </div>

      <hr />

      <table id="patientsTable" class="table table-striped" style="width:100%">
        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Gender</th>
            <th>Date of Birth</th>
            <th>Phone</th>
            <th>Next Appointment Date</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($patientsArr as $row): ?>
          <tr data-patientid="<?= $row['PatientID'] ?>">
            <td><?= htmlspecialchars($row['PatientID']) ?></td>
            <td><?= htmlspecialchars($row['Name']) ?></td>
            <td><?= htmlspecialchars($row['Gender']) ?></td>
            <td><?= htmlspecialchars($row['DOB']) ?></td>
            <td><?= htmlspecialchars($row['PhoneNumber']) ?></td>
            <td><?= htmlspecialchars($row['NextApptDate'] ?? '-') ?></td>
            <td><button class="btn btn-sm btn-outline-primary btn-record-toggle" data-patientid="<?= $row['PatientID'] ?>">View Medical History</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  var table = $('#patientsTable').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25],
    language: {
      search: "Search:",
      lengthMenu: "Show _MENU_ records"
    }
  });

  var recordsData = <?= json_encode($records); ?>;

  $('#patientsTable tbody').on('click', '.btn-record-toggle', function() {
    var tr = $(this).closest('tr');
    var row = table.row(tr);
    var patientId = $(this).data('patientid');

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      $(this).text('View Medical History');
    } else {
      var records = recordsData[patientId] || [];
      var html = '';

      if (records.length === 0) {
        html = '<div class="record-details"><em>No medical history available.</em></div>';
      } else {
        html = '<div class="record-details">';
        html += '<h6>Medical History</h6>';
        html += '<table class="record-table">';
        html += '<thead><tr><th>Diagnosis</th><th>Treatment</th><th>Classification</th></tr></thead><tbody>';

        records.forEach(function(rec) {
          html += '<tr>' +
                    '<td>' + htmlspecialchars(rec.Diagnosis) + '</td>' +
                    '<td>' + htmlspecialchars(rec.Treatment) + '</td>' +
                    '<td>' + htmlspecialchars(rec.Classification) + '</td>' +
                  '</tr>';
        });

        html += '</tbody></table></div>';
      }

      row.child(html).show();
      tr.addClass('shown');
      $(this).text('Hide Medical History');
    }
  });

  function htmlspecialchars(text) {
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Chart data from PHP
  const genderData = <?= json_encode($genderCount) ?>;
  const ageGroupData = <?= json_encode($ageGroups) ?>;

  // Convert to arrays
  const genderLabels = Object.keys(genderData);
  const genderValues = Object.values(genderData);

  const ageLabels = Object.keys(ageGroupData);
  const ageValues = Object.values(ageGroupData);

  // Chart Gender
  new Chart(document.getElementById('chartGender'), {
    type: 'doughnut',
    data: {
      labels: genderLabels,
      datasets: [{
        label: 'Number of Patients by Gender',
        data: genderValues,
        backgroundColor: ['#4e79a7', '#f28e2b', '#e15759'],
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: 'Patient Distribution by Gender' }
      }
    }
  });

  // Chart Age Group
  new Chart(document.getElementById('chartAgeGroup'), {
    type: 'bar',
    data: {
      labels: ageLabels,
      datasets: [{
        label: 'Number of Patients',
        data: ageValues,
        backgroundColor: '#59a14f',
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Patient Distribution by Age Group' }
      },
      scales: {
        y: {
          beginAtZero: true,
          precision: 0
        }
      }
    }
  });

});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
