<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: logindoctor.php');
    exit;
}
require_once __DIR__ . '/db_config.php';
$doctorId = $_SESSION['linked_id'];

// 1) Fetch all patient questions/feedback
$sql = <<<SQL
SELECT
    q.QuestionID,
    CONCAT(p.FirstName, ' ', p.LastName) AS PatientName,
    q.Question,
    DATE_FORMAT(q.QuestionDate, '%d/%m/%Y %H:%i') AS QuestionDate,
    q.Answer,
    q.IsAnswered
FROM question_feedback q
JOIN patient p ON q.PatientID = p.PatientID
WHERE q.DoctorID = ?
ORDER BY q.QuestionDate DESC
SQL;
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$questions = $stmt->get_result();

// 2) Count new (unanswered) questions for notification badge
$sqlNew = "SELECT COUNT(*) AS cnt FROM question_feedback WHERE DoctorID = ? AND IsAnswered = 0";
$nstmt = $conn->prepare($sqlNew);
$nstmt->bind_param('i', $doctorId);
$nstmt->execute();
$newCount = (int)$nstmt->get_result()->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Doctor Portal – Phản hồi thắc mắc</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <style>
    body { background: #f3edf7; }
    .sidebar { width:240px; background:#c5dcff; min-height:100vh; padding:1rem; }
    .sidebar .nav-link { color:#2f4f8a; margin-bottom:4px; border-radius:6px; }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover { background:#95b8ff; color:#fff; }
    .topbar { background:#fff; padding:.75rem 1.5rem; margin-bottom:1.5rem;
              border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,.1);
              display:flex; justify-content:space-between; align-items:center; }
    .content { padding:0 2rem 2rem; }
    .card-container { background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.05); padding:1rem; }
    .badge-new { background:#e17055; color:#fff; }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <nav class="sidebar d-flex flex-column">
    <h3>Doctor Portal</h3>
    <ul class="nav nav-pills flex-column mt-3">
      <li class="nav-item"><a href="dashboard.php" class="nav-link">Tổng quan</a></li>
      <li class="nav-item"><a href="patient.php" class="nav-link">Bệnh nhân</a></li>
      <li class="nav-item"><a href="prescriptions.php" class="nav-link">Đơn thuốc</a></li>
      <li class="nav-item"><a href="appointments.php" class="nav-link">Cuộc hẹn</a></li>
      <li class="nav-item"><a href="feedback.php" class="nav-link">Phản hồi</a></li>
      <li class="nav-item">
        <a href="patient_questions.php" class="nav-link active">
          Phản hồi thắc mắc <?php if($newCount){ echo '<span class="badge-new ms-1">'.$newCount.'</span>'; } ?>
        </a>
      </li>
      <li class="nav-item"><a href="settings.php" class="nav-link">Cài đặt</a></li>
    </ul>
    <div class="mt-auto"><a href="logout.php" class="btn btn-outline-primary w-100 mt-3">Đăng xuất</a></div>
  </nav>

  <div class="flex-grow-1">
    <!-- Topbar -->
    <div class="topbar">
      <h5>Phản hồi thắc mắc</h5>
      <span><strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
    </div>

    <div class="content">
      <div class="card-container mb-4">
        <table id="qTable" class="table table-striped">
          <thead>
            <tr>
              <th>Bệnh nhân</th>
              <th>Câu hỏi</th>
              <th>Ngày giờ</th>
              <th>Trạng thái</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody>
          <?php while($row = $questions->fetch_assoc()): ?>
            <tr data-id="<?= $row['QuestionID'] ?>"
                data-question="<?= htmlspecialchars($row['Question'],ENT_QUOTES) ?>"
                data-answer="<?= htmlspecialchars($row['Answer'],ENT_QUOTES) ?>"
                data-isanswered="<?= $row['IsAnswered'] ?>">
              <td><?= htmlspecialchars($row['PatientName']) ?></td>
              <td><?= nl2br(htmlspecialchars($row['Question'])) ?></td>
              <td><?= $row['QuestionDate'] ?></td>
              <td>
                <?= $row['IsAnswered'] 
                   ? '<span class="badge bg-success">Đã trả lời</span>'
                   : '<span class="badge bg-warning">Chưa trả lời</span>' ?>
              </td>
              <td>
                <button class="btn btn-sm btn-primary btn-reply" data-bs-toggle="modal" data-bs-target="#replyModal">
                  Trả lời
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Reply -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="answer_question.php">
        <div class="modal-header">
          <h5 class="modal-title">Trả lời thắc mắc</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="question_id" id="replyQuestionId">
          <div class="mb-3">
            <label class="form-label">Câu hỏi</label>
            <textarea class="form-control" id="displayQuestion" rows="3" disabled></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Trả lời</label>
            <textarea name="answer" class="form-control" id="replyAnswer" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary">Gửi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function(){
    $('#qTable').DataTable({ pageLength: 10 });

    $('.btn-reply').on('click', function(){
      var tr = $(this).closest('tr');
      $('#replyQuestionId').val(tr.data('id'));
      $('#displayQuestion').val(tr.data('question'));
      $('#replyAnswer').val(tr.data('answer'));
    });
  });
</script>
</body>
</html>
