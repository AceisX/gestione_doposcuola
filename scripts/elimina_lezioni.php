<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verifica che la richiesta sia di tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Ottieni i dati inviati
    $tutorId = intval($data['tutor_id']);
    $date = $data['date'];

    if ($tutorId && $date) {
        try {
            // Inizia una transazione
            $conn->begin_transaction();

            // Elimina le lezioni del tutor per la data specifica
            $sqlDeleteLessons = "DELETE FROM lezioni WHERE id_tutor = ? AND data = ?";
            $stmt = $conn->prepare($sqlDeleteLessons);
            $stmt->bind_param("is", $tutorId, $date);
            $stmt->execute();

            // Elimina le associazioni degli alunni con le lezioni
            $sqlDeleteLessonStudents = "DELETE la 
                                        FROM lezioni_alunni la
                                        JOIN lezioni l ON la.id_lezione = l.id
                                        WHERE l.id_tutor = ? AND l.data = ?";
            $stmt = $conn->prepare($sqlDeleteLessonStudents);
            $stmt->bind_param("is", $tutorId, $date);
            $stmt->execute();

            // Conferma la transazione
            $conn->commit();

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            // Annulla la transazione in caso di errore
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Parametri mancanti.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito.']);
}
?>