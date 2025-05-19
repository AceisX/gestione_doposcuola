<?php
require_once '../config.php';

header('Content-Type: application/json'); // Assicura che il server restituisca un JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica che tutti i parametri siano presenti
    if (!isset($_POST['tutor_id'], $_POST['mensilita'], $_POST['totale_paga'])) {
        echo json_encode(['success' => false, 'message' => 'Parametri mancanti.']);
        exit;
    }

    $tutorId = intval($_POST['tutor_id']);
    $mensilita = $_POST['mensilita'];
    $totalePaga = floatval($_POST['totale_paga']);
    $note = isset($_POST['note']) ? $_POST['note'] : '';

    // Aggiorna lo stato del pagamento nella tabella pagamenti_tutor
    $sql = "UPDATE pagamenti_tutor 
            SET stato = 1, paga = ?, data_pagamento = NOW(), note = ?
            WHERE tutor_id = ? AND mensilita = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('diss', $totalePaga, $note, $tutorId, $mensilita);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pagamento effettuato con successo.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento del pagamento.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metodo non valido.']);
exit;
?>