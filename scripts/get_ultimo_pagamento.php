<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (isset($_GET['alunno_id'])) {
    $alunnoId = intval($_GET['alunno_id']);

    // Query per ottenere l'ultimo pagamento
    $sql = "SELECT mese_pagato 
            FROM pagamenti 
            WHERE id_alunno = ? 
            AND YEAR(data_pagamento) = YEAR(CURRENT_DATE)
            ORDER BY data_pagamento DESC 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $alunnoId);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'ultimo_mese_pagato' => $row['mese_pagato']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'ultimo_mese_pagato' => null
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Errore nella query',
            'error' => $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID alunno mancante'
    ]);
}
?>