<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (isset($_GET['alunno_id']) && isset($_GET['mese'])) {
    $alunnoId = intval($_GET['alunno_id']);
    $mese = $_GET['mese'];

    // Query per verificare se esiste un pagamento di tipo SALDO per questo mese
    $sql = "SELECT COUNT(*) as pagato, 
            MAX(CASE WHEN tipologia = 'Saldo' THEN 1 ELSE 0 END) as is_saldo
            FROM pagamenti 
            WHERE id_alunno = ? 
            AND (
                mese_pagato = ? 
                OR mese_pagato LIKE CONCAT('MENSILE ', ?)
                OR mese_pagato LIKE CONCAT(?, ' %')
                OR mese_pagato LIKE CONCAT('% ', ?)
            )
            AND YEAR(data_pagamento) = YEAR(CURRENT_DATE)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $alunnoId, $mese, $mese, $mese, $mese);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'gia_pagato' => $row['pagato'] > 0,
            'is_saldo' => $row['is_saldo'] > 0,
            'debug_info' => [
                'alunno_id' => $alunnoId,
                'mese' => $mese,
                'pagato' => $row['pagato'],
                'is_saldo' => $row['is_saldo']
            ]
        ]);
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
        'message' => 'Parametri mancanti',
        'params' => $_GET
    ]);
}
?>