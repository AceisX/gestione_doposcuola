<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Non autorizzato']));
}

if (!isset($_GET['lesson_id'])) {
    die(json_encode(['success' => false, 'message' => 'ID lezione mancante']));
}

$lesson_id = intval($_GET['lesson_id']);

// Ottieni i dettagli della lezione
$sql = "SELECT l.*, t.nome AS tutor_nome, t.cognome AS tutor_cognome
        FROM lezioni l
        JOIN tutor t ON l.id_tutor = t.id
        WHERE l.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die(json_encode(['success' => false, 'message' => 'Lezione non trovata']));
}

$lesson = $result->fetch_assoc();

// Ottieni gli studenti associati alla lezione
$sql_students = "SELECT a.id, CONCAT(a.nome, ' ', a.cognome) AS nome
                 FROM lezioni_alunni la
                 JOIN alunni a ON la.id_alunno = a.id
                 WHERE la.id_lezione = ?
                 ORDER BY a.nome, a.cognome";

$stmt_students = $conn->prepare($sql_students);
$stmt_students->bind_param("i", $lesson_id);
$stmt_students->execute();
$result_students = $stmt_students->get_result();

$students = [];
while ($row = $result_students->fetch_assoc()) {
    $students[] = $row;
}

$lesson['students'] = $students;

echo json_encode(['success' => true, 'lesson' => $lesson]);
?>