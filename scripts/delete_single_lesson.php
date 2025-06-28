<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Non autorizzato']));
}

// Leggi il JSON dal body della richiesta
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['lesson_id'])) {
    die(json_encode(['success' => false, 'message' => 'ID lezione mancante']));
}

$lesson_id = intval($data['lesson_id']);

$conn->begin_transaction();

try {
    // Elimina prima le associazioni con gli alunni (se non c'è ON DELETE CASCADE)
    $sql_alunni = "DELETE FROM lezioni_alunni WHERE id_lezione = ?";
    $stmt_alunni = $conn->prepare($sql_alunni);
    $stmt_alunni->bind_param("i", $lesson_id);
    $stmt_alunni->execute();
    
    // Elimina la lezione
    $sql = "DELETE FROM lezioni WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        throw new Exception('Lezione non trovata');
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Lezione eliminata con successo']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}
?>