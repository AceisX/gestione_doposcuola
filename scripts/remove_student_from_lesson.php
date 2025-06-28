<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Non autorizzato']));
}

// Leggi il JSON dal body della richiesta
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['lesson_id']) || !isset($data['student_id'])) {
    die(json_encode(['success' => false, 'message' => 'Parametri mancanti']));
}

$lesson_id = intval($data['lesson_id']);
$student_id = intval($data['student_id']);

$conn->begin_transaction();

try {
    // Rimuovi lo studente dalla lezione
    $sql = "DELETE FROM lezioni_alunni WHERE id_lezione = ? AND id_alunno = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $lesson_id, $student_id);
    $stmt->execute();
    
    // Verifica se ci sono ancora studenti nella lezione
    $sql_check = "SELECT COUNT(*) as count FROM lezioni_alunni WHERE id_lezione = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $lesson_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row = $result_check->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Se non ci sono più studenti, elimina la lezione
        $sql_delete = "DELETE FROM lezioni WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $lesson_id);
        $stmt_delete->execute();
        
        $message = 'Studente rimosso. La lezione è stata eliminata perché non aveva più studenti.';
    } else {
        // Aggiorna il tipo di lezione se necessario
        if ($row['count'] == 1) {
            $sql_update = "UPDATE lezioni SET tipo = 'singolo' WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $lesson_id);
            $stmt_update->execute();
        }
        
        $message = 'Studente rimosso con successo.';
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}
?>