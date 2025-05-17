<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: logindoctor.php');
    exit;
}
require_once __DIR__ . '/db_config.php';
$doctorId = $_SESSION['linked_id'];

// 1) Fetch all patient questions
$sql = <<<SQL
SELECT
    q.QuestionID,
    CONCAT(p.FirstName, ' ', p.LastName) AS PatientName,
    q.Question,
    DATE_FORMAT(q.QuestionDate, '%d/%m/%Y %H:%i') AS QuestionDate,
    q.Answer,
    q.IsAnswered
FROM `question` q
JOIN patient p ON q.PatientID = p.PatientID
WHERE q.DoctorID = ?
ORDER BY q.QuestionDate DESC
SQL;
$stmt     = $conn->prepare($sql);
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$questions = $stmt->get_result();

// 2) Count new (unanswered) questions for badge
$sqlNew   = "SELECT COUNT(*) AS cnt FROM `question` WHERE DoctorID = ? AND IsAnswered = 0";
$nstmt     = $conn->prepare($sqlNew);
$nstmt->bind_param('i', $doctorId);
$nstmt->execute();
$newCount  = (int)$nstmt->get_result()->fetch_assoc()['cnt'];
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
    .content { padding: 0 2rem 2rem; }
    .card-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,.05);
      padding: 1rem;
    }
    .badge-new {
      background: #e17055;
      color: #fff;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <nav class="sidebar">
    <h3 class="text-white mb-4">Doctor Portal</h3>
    <ul class="nav nav-pills flex-column">
      <li class="nav-item"><a href="dashboard.php" class="nav-link">Tổng quan</a></li>
      <li class="nav-item"><a href="patient.php"   class="nav-link">Bệnh nhân</a></li>
      <li class="nav-item"><a href="prescriptions.php" class="nav-link">Đơn thuốc</a></li>
      <li class="nav-item"><a href="appointments.php"  class="nav-link">Cuộc hẹn</a></li>
      <li class="nav-item">
        <a href="question.php" class="nav-link active">
          Phản hồi thắc mắc
          <?php if($newCount): ?>
            <span class="badge-new ms-1"><?= $newCount ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item"><a href="settings.php" class="nav-link">Cài đặt</a></li>
    </ul>
    <div class="mt-auto">
      <a href="logout.php" class="btn btn-outline-light w-100 mt-3">Đăng xuất</a>
    </div>
  </nav>

  <!-- Main area -->
  <div class="flex-grow-1">
    <!-- Header Card -->
    <div class="container-fluid mt-3 mb-4">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
          <h5 class="mb-0">Phản hồi thắc mắc</h5>
          <span class="fw-bold"><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
      </div>
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
                <?php if($row['IsAnswered']): ?>
                  <span class="badge bg-success">Đã trả lời</span>
                <?php else: ?>
                  <span class="badge bg-warning">Chưa trả lời</span>
                <?php endif; ?>
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

<!-- Modal Gửi trả lời -->
<div class="modal fade" id="replyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="answer_question.php">
        <div class="modal-header">
          <h5 class="modal-title">Trả lời thắc mắc</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

<!-- JS Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
  $('#qTable').DataTable({ pageLength: 10 });

  $('.btn-reply').on('click', function(){
    const tr = $(this).closest('tr');
    $('#replyQuestionId').val(tr.data('id'));
    $('#displayQuestion').val(tr.data('question'));
    $('#replyAnswer').val(tr.data('answer') || '');
  });
});
</script>
</body>
</html>
