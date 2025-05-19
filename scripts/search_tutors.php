<?php
require_once '../config.php';

$query = $_GET['query'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => false]);
    exit;
}

$sql = "SELECT id, nome, cognome FROM tutor WHERE nome LIKE ? OR cognome LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$search = '%' . $query . '%';
$stmt->bind_param('ss', $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$tutors = [];
while ($row = $result->fetch_assoc()) {
    $tutors[] = $row;
}

echo json_encode(['success' => true, 'tutors' => $tutors]);