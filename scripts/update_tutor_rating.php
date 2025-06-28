<?php
require_once '../config.php';
session_start();

// Verifica che sia l'amministratore
if (!isset($_SESSION['loggedin']) || $_SESSION['username'] !== 'alessandro') {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Ottieni i dati JSON
$data = json_decode(file_get_contents('php://input'), true);

$tutor_id = isset($data['tutor_id']) ? (int)$data['tutor_id'] : 0;
$valutazione = isset($data['valutazione']) ? (int)$data['valutazione'] : 0;

if ($tutor_id <= 0 || $valutazione < 1 || $valutazione > 5) {
    echo json_encode(['success' => false, 'message' => 'Dati non validi']);
    exit;
}

// Inserisci o aggiorna la valutazione
$query = "INSERT INTO valutazioni_tutor (tutor_id, valutazione) 
          VALUES (?, ?) 
          ON DUPLICATE KEY UPDATE 
          valutazione = VALUES(valutazione),
          updated_at = CURRENT_TIMESTAMP";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $tutor_id, $valutazione);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Valutazione aggiornata']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento']);
}

$stmt->close();
$conn->close();
?>