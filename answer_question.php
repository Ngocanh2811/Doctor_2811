<?php
// answer_question.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: logindoctor.php');
    exit;
}
require_once __DIR__ . '/db_config.php';

// Xử lý POST trả lời câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId = intval($_POST['question_id'] ?? 0);
    $answer     = trim($_POST['answer'] ?? '');
    if ($questionId > 0 && $answer !== '') {
        // Cập nhật câu trả lời
        $sql = "UPDATE `question`
                   SET `Answer` = ?,
                       `AnswerDate` = NOW(),
                       `IsAnswered` = 1
                 WHERE `QuestionID` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $answer, $questionId);
        $stmt->execute();
    }
}
// Quay về trang Phản hồi thắc mắc
header('Location: question.php');
exit;
?>