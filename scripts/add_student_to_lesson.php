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
    // Verifica che lo studente non sia già nella lezione
    $sql_check = "SELECT COUNT(*) as count FROM lezioni_alunni 
                  WHERE id_lezione = ? AND id_alunno = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $lesson_id, $student_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    
    if ($row_check['count'] > 0) {
        throw new Exception('Lo studente è già iscritto a questa lezione');
    }
    
    // Aggiungi lo studente alla lezione
    $sql = "INSERT INTO lezioni_alunni (id_lezione, id_alunno) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $lesson_id, $student_id);
    $stmt->execute();
    
    // Conta gli studenti nella lezione
    $sql_count = "SELECT COUNT(*) as count FROM lezioni_alunni WHERE id_lezione = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $lesson_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_assoc();
    
    // Aggiorna il tipo di lezione se necessario
    if ($row_count['count'] > 1) {
        $sql_update = "UPDATE lezioni SET tipo = 'gruppo' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $lesson_id);
        $stmt_update->execute();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Studente aggiunto con successo']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>