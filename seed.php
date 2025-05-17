<?php
// seed_feedback.php
// Chạy 1 lần để tạo dữ liệu dummy cho bảng 'feedback'
// Sau khi chạy, xóa hoặc đổi tên file này để tránh chạy nhầm

require_once __DIR__ . '/db_config.php';

// Nếu session không có, gán cứng DoctorID
session_start();
if (isset($_SESSION['linked_id']) && $_SESSION['role'] === 'doctor') {
    $doctorId = $_SESSION['linked_id'];
} else {
    // Uncomment và điều chỉnh nếu muốn gán cứng
    // $doctorId = 1;
    die("Không xác định được DoctorID. Vui lòng login hoặc gán cứng DoctorID trên dòng 8.");
}

// Lấy tất cả PatientID
$patientRes = $conn->query("SELECT PatientID FROM patient");
$patientIds = [];
while ($r = $patientRes->fetch_assoc()) {
    $patientIds[] = $r['PatientID'];
}
if (empty($patientIds)) {
    die("Chưa có bệnh nhân. Vui lòng seed bảng patient trước.");
}

// Mảng dữ liệu mẫu cho feedback
$comments = [
    'Bác sĩ rất nhiệt tình và chuyên nghiệp.',
    'Phòng khám sạch sẽ, nhân viên vui vẻ.',
    'Thuốc tác dụng nhanh, tôi cảm thấy dễ chịu hơn.',
    'Tôi muốn tái khám vào tuần tới.',
    'Chi phí hơi cao nhưng chất lượng tốt.',
    'Bác sĩ giải thích rõ ràng, tôi rất hài lòng.',
    'Chờ khám lâu quá, nên cải thiện thời gian.',
    'Trang thiết bị hiện đại.',
    'Tôi bị đau nhưng đã được tư vấn rất kỹ.',
    'Mong phòng khám có thêm bác sĩ chuyên khoa.'
];
$ratings = [1,2,3,4,5];

// Số bản ghi muốn tạo
$seedCount = 50;

$stmt = $conn->prepare(
    "INSERT INTO feedback (PatientID, DoctorID, FeedbackDate, Rating, Comments, IsAddressed) VALUES (?,?,?,?,?,?)"
);
// Tham số: i:PatientID, i:DoctorID, s:FeedbackDate, i:Rating, s:Comments, i:IsAddressed
$stmt->bind_param('iisisi', $pid, $doctorId, $fbDate, $rating, $comment, $isAddressed);

for ($i = 0; $i < $seedCount; $i++) {
    // random patient
    $pid = $patientIds[array_rand($patientIds)];
    // random feedback date trong 60 ngày gần đây
    $fbTimestamp = strtotime('-'.rand(0,60).' days');
    // random thời gian trong ngày
    $timeOffset = rand(0,86400);
    $fbDate = date('Y-m-d H:i:s', $fbTimestamp + $timeOffset);
    // random rating
    $rating = $ratings[array_rand($ratings)];
    // random comment
    $comment = $comments[array_rand($comments)];
    // random addressed status
    $isAddressed = rand(0,1);

    $stmt->execute();
}

echo "Seed xong $seedCount phản hồi vào bảng feedback!";
