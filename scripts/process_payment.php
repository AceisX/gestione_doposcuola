<?php
require_once '../config.php';

header('Content-Type: application/json'); // Assicura che il server restituisca JSON

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non valido.']);
    exit;
}

$mensilitaId = intval($_POST['mensilita_id'] ?? 0);
$importoPaga = trim($_POST['importo_paga'] ?? '');
$notePaga = trim($_POST['note_paga'] ?? '');
$dataPagamento = $_POST['data_pagamento'] ?? date('Y-m-d'); // Nuova riga per gestire la data

if ($mensilitaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID mensilità non valido.']);
    exit;
}

if (empty($importoPaga) && empty($notePaga)) {
    echo json_encode(['success' => false, 'message' => 'Nessun dato da salvare.']);
    exit;
}

// Valida la data
if (!empty($dataPagamento)) {
    $date = DateTime::createFromFormat('Y-m-d', $dataPagamento);
    if (!$date || $date->format('Y-m-d') !== $dataPagamento) {
        echo json_encode(['success' => false, 'message' => 'Data non valida.']);
        exit;
    }
}

// Recupera i dati della mensilità
$sql = "SELECT stato, note FROM pagamenti_tutor WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $mensilitaId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Mensilità non trovata.']);
    exit;
}

$row = $result->fetch_assoc();
$currentNote = $row['note'] ?? '';
$currentState = $row['stato'];

// Concatenazione delle note se presenti
if (!empty($notePaga)) {
    $notePaga = empty($currentNote) ? $notePaga : $currentNote . "\n" . $notePaga;
}

// Gestione pagamento
if (!empty($importoPaga) && floatval($importoPaga) > 0) {
    // Aggiorna lo stato a pagato con la data specificata
    $sql = "UPDATE pagamenti_tutor 
            SET paga = ?, stato = 1, data_pagamento = ?, note = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('dssi', $importoPaga, $dataPagamento, $notePaga, $mensilitaId);
} else {
    // Aggiorna solo le note
    $sql = "UPDATE pagamenti_tutor 
            SET note = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $notePaga, $mensilitaId);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Dati aggiornati con successo.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento.']);
}
exit;
?>