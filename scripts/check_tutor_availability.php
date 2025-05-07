<?php
require_once '../config.php';

if (isset($_GET['tutor_id']) && isset($_GET['date'])) {
    $tutorId = (int) $_GET['tutor_id'];
    $date = $_GET['date'];

    // Query per controllare se il tutor ha già una lezione registrata nella data
    $sql = "SELECT COUNT(*) AS count, CONCAT(tutor.nome, ' ', tutor.cognome) AS tutor_name
            FROM lezioni
            JOIN tutor ON lezioni.id_tutor = tutor.id
            WHERE lezioni.id_tutor = ? AND lezioni.data = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $tutorId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $hasLesson = $row['count'] > 0;
    $tutorName = $row['tutor_name'];

    echo json_encode([
        'hasLesson' => $hasLesson,
        'tutorName' => $tutorName,
        'date' => $date
    ]);
    exit;
} else {
    echo json_encode(['error' => 'Parametri mancanti']);
    exit;
}
?>