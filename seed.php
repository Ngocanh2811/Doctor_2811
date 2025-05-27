<?php
// seed_feedback.php
// Run once to generate dummy data for the 'feedback' table
// After running, delete or rename this file to prevent accidental rerun

require_once __DIR__ . '/db_config.php';

// If session not set, hardcode DoctorID
session_start();
if (isset($_SESSION['linked_id']) && $_SESSION['role'] === 'doctor') {
    $doctorId = $_SESSION['linked_id'];
} else {
    // Uncomment and adjust if you want to hardcode
    // $doctorId = 1;
    die("DoctorID not identified. Please login or hardcode DoctorID on line 8.");
}

// Get all PatientIDs
$patientRes = $conn->query("SELECT PatientID FROM patient");
$patientIds = [];
while ($r = $patientRes->fetch_assoc()) {
    $patientIds[] = $r['PatientID'];
}
if (empty($patientIds)) {
    die("No patients found. Please seed the patient table first.");
}

// Sample feedback data
$comments = [
    'The doctor was very attentive and professional.',
    'The clinic is clean, and the staff are friendly.',
    'The medication worked quickly; I feel better now.',
    'I want to schedule a follow-up next week.',
    'Costs are a bit high but the quality is good.',
    'The doctor explained everything clearly; I am very satisfied.',
    'Waiting time was too long, should improve scheduling.',
    'Modern equipment available.',
    'I had pain but received very thorough advice.',
    'Hope the clinic will add more specialists.'
];
$ratings = [1, 2, 3, 4, 5];

// Number of records to seed
$seedCount = 50;

$stmt = $conn->prepare(
    "INSERT INTO feedback (PatientID, DoctorID, FeedbackDate, Rating, Comments, IsAddressed) VALUES (?,?,?,?,?,?)"
);
// Params: i:PatientID, i:DoctorID, s:FeedbackDate, i:Rating, s:Comments, i:IsAddressed
$stmt->bind_param('iisisi', $pid, $doctorId, $fbDate, $rating, $comment, $isAddressed);

for ($i = 0; $i < $seedCount; $i++) {
    // random patient
    $pid = $patientIds[array_rand($patientIds)];
    // random feedback date within last 60 days
    $fbTimestamp = strtotime('-' . rand(0, 60) . ' days');
    // random time offset in day
    $timeOffset = rand(0, 86400);
    $fbDate = date('Y-m-d H:i:s', $fbTimestamp + $timeOffset);
    // random rating
    $rating = $ratings[array_rand($ratings)];
    // random comment
    $comment = $comments[array_rand($comments)];
    // random addressed status
    $isAddressed = rand(0, 1);

    $stmt->execute();
}

echo "Seeded $seedCount feedback records into the feedback table!";
