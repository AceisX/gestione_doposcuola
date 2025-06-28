<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $anno = $_GET['anno'] ?? date('Y');
    $stato = $_GET['stato'] ?? '';
    
    // Totale da pagare
    $sql_da_pagare = "SELECT SUM(paga) as totale 
                      FROM pagamenti_tutor 
                      WHERE stato = 0
                      AND YEAR(mensilita) = ?";
    $stmt = $conn->prepare($sql_da_pagare);
    $stmt->bind_param("i", $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    $totale_da_pagare = $result->fetch_assoc()['totale'] ?? 0;

    // Pagamenti questo mese
    $sql_mese = "SELECT SUM(paga) as totale 
                 FROM pagamenti_tutor 
                 WHERE stato = 1 
                 AND MONTH(data_pagamento) = MONTH(CURRENT_DATE)
                 AND YEAR(data_pagamento) = YEAR(CURRENT_DATE)";
    $result = $conn->query($sql_mese);
    $pagamenti_mese = $result->fetch_assoc()['totale'] ?? 0;

    // Tutor attivi - modifica per contare TUTTI i tutor
    $sql_attivi = "SELECT COUNT(DISTINCT id) as count FROM tutor";
    $result = $conn->query($sql_attivi);
    $tutor_attivi = $result->fetch_assoc()['count'] ?? 0;

    // Riepilogo tutti i tutor con filtri - QUERY CORRETTA
    $sql_riepilogo = "SELECT 
        t.id,
        t.nome,
        t.cognome,
        COALESCE(COUNT(CASE WHEN pt.stato = 0 THEN 1 END), 0) as mesi_non_pagati,
        COALESCE(SUM(CASE WHEN pt.stato = 0 THEN pt.paga ELSE 0 END), 0) as totale_dovuto,
        MAX(CASE WHEN pt.stato = 1 THEN pt.data_pagamento END) as ultimo_pagamento
    FROM tutor t
    LEFT JOIN pagamenti_tutor pt ON t.id = pt.tutor_id";
    
    // Costruisci le condizioni WHERE
    $where_conditions = [];
    $params = [];
    $types = "";
    
    // Filtro per anno solo se ci sono pagamenti
    if ($anno) {
        $where_conditions[] = "(pt.id IS NULL OR YEAR(pt.mensilita) = ?)";
        $params[] = $anno;
        $types .= "i";
    }
    
    // Filtro per stato
    if ($stato == 'pagato') {
        $where_conditions[] = "EXISTS (SELECT 1 FROM pagamenti_tutor pt2 WHERE pt2.tutor_id = t.id AND pt2.stato = 1)";
    } elseif ($stato == 'non-pagato') {
        $where_conditions[] = "(NOT EXISTS (SELECT 1 FROM pagamenti_tutor pt2 WHERE pt2.tutor_id = t.id) OR EXISTS (SELECT 1 FROM pagamenti_tutor pt3 WHERE pt3.tutor_id = t.id AND pt3.stato = 0))";
    }
    
    // Applica le condizioni WHERE se presenti
    if (!empty($where_conditions)) {
        $sql_riepilogo .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql_riepilogo .= " GROUP BY t.id, t.nome, t.cognome
                        ORDER BY t.cognome, t.nome";
    
    // Prepara ed esegui la query
    if (!empty($params)) {
        $stmt = $conn->prepare($sql_riepilogo);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql_riepilogo);
    }
    
    $tutors = [];
    while ($row = $result->fetch_assoc()) {
        $tutors[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'cognome' => $row['cognome'],
            'mesi_non_pagati' => intval($row['mesi_non_pagati']),
            'totale_dovuto' => floatval($row['totale_dovuto']),
            'ultimo_pagamento' => $row['ultimo_pagamento']
        ];
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'totale_da_pagare' => floatval($totale_da_pagare),
            'pagamenti_mese' => floatval($pagamenti_mese),
            'tutor_attivi' => intval($tutor_attivi)
        ],
        'tutors' => $tutors
    ]);

} catch (Exception $e) {
    error_log("Errore in get_dashboard_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>