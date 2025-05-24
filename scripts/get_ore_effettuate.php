<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['alunno_id']) && isset($_GET['mese']) && isset($_GET['anno'])) {
    $alunnoId = intval($_GET['alunno_id']);
    $mese = $_GET['mese'];
    $anno = $_GET['anno'];

    // Modifichiamo la query per arrotondare sempre per eccesso
    $sql = "SELECT CEIL(COALESCE(SUM(
        CASE 
            WHEN l.durata = 1 THEN 0.5 
            ELSE 1 
        END
    ), 0)) as ore_totali
    FROM lezioni l
    JOIN lezioni_alunni la ON l.id = la.id_lezione
    WHERE la.id_alunno = ? 
    AND MONTH(l.data) = ? 
    AND YEAR(l.data) = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $alunnoId, $mese, $anno);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // Converti il risultato in numero intero
        $oreTotali = intval($row['ore_totali']);
        
        echo json_encode([
            'success' => true,
            'ore_effettuate' => $oreTotali
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Errore nell\'esecuzione della query',
            'error' => $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Parametri mancanti'
    ]);
}
?>