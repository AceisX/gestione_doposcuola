<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$tutor_id = $_POST['tutor_id'] ?? null;
$mensilita_ids = $_POST['mensilita_ids'] ?? '';
$note = $_POST['note'] ?? '';
$dataPagamento = $_POST['data_pagamento'] ?? date('Y-m-d'); // Nuova riga per gestire la data

if (!$tutor_id || empty($mensilita_ids)) {
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
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

$ids_array = explode(',', $mensilita_ids);
$success_count = 0;
$errors = [];

try {
    $conn->begin_transaction();
    
    foreach ($ids_array as $mensilita_id) {
        $mensilita_id = intval($mensilita_id);
        
        // Verifica che la mensilità appartenga al tutor e non sia già pagata
        $check_sql = "SELECT * FROM pagamenti_tutor 
                      WHERE id = ? AND tutor_id = ? AND stato = 0";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $mensilita_id, $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Aggiorna lo stato del pagamento con la data specificata
            $update_sql = "UPDATE pagamenti_tutor 
                          SET stato = 1, 
                              data_pagamento = ?, 
                              note = CONCAT(IFNULL(note, ''), '\n', ?)
                          WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $dataPagamento, $note, $mensilita_id);
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $errors[] = "Errore nel pagamento della mensilità ID: $mensilita_id";
            }
        }
    }
    
    if ($success_count > 0 && empty($errors)) {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => "$success_count mensilità pagate con successo!"
        ]);
    } else {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Errore durante il pagamento: ' . implode(', ', $errors)
        ]);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'Errore del database: ' . $e->getMessage()
    ]);
}
?>