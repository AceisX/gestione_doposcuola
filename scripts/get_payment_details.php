<?php
require_once '../config.php';

// Abilita la visualizzazione degli errori per debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Imposta l'header JSON
header('Content-Type: application/json');


function getMeseNome($numeroMese) {
    $mesi = [
        1 => 'Gennaio',
        2 => 'Febbraio',
        3 => 'Marzo',
        4 => 'Aprile',
        5 => 'Maggio',
        6 => 'Giugno',
        7 => 'Luglio',
        8 => 'Agosto',
        9 => 'Settembre',
        10 => 'Ottobre',
        11 => 'Novembre',
        12 => 'Dicembre'
    ];
    return isset($mesi[$numeroMese]) ? $mesi[$numeroMese] : '';
}

try {
    // Verifica i parametri
    if (!isset($_GET['studentId']) || !isset($_GET['month']) || !isset($_GET['year'])) {
        throw new Exception('Parametri mancanti');
    }

    $studentId = intval($_GET['studentId']);
    $month = intval($_GET['month']);
    $year = intval($_GET['year']);
    
    // Verifica validità parametri
    if ($studentId <= 0 || $month < 1 || $month > 12 || $year <= 0) {
        throw new Exception('Parametri non validi');
    }

    // Ottieni il nome del mese
    $meseNome = getMeseNome($month);
    if (empty($meseNome)) {
        throw new Exception('Mese non valido');
    }

    // Query per i pagamenti
    $query = "SELECT 
        p.id,
        p.data_pagamento,
        p.totale_pagato,
        p.tipologia,
        p.mese_pagato,
        CONCAT(a.nome, ' ', a.cognome) as nome_studente
    FROM pagamenti p
    JOIN alunni a ON p.id_alunno = a.id
    WHERE p.id_alunno = ? 
    AND p.mese_pagato = ?
    AND YEAR(p.data_pagamento) = ?
    ORDER BY p.data_pagamento DESC";

    // Prepara e esegui la query
    if (!$stmt = $conn->prepare($query)) {
        throw new Exception("Errore nella preparazione della query: " . $conn->error);
    }

    if (!$stmt->bind_param("isi", $studentId, $meseNome, $year)) {
        throw new Exception("Errore nel binding dei parametri: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Errore nell'esecuzione della query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $payments = [];

    // Raccogli i risultati
    while ($row = $result->fetch_assoc()) {
        $payments[] = [
            'id' => intval($row['id']),
            'data_pagamento' => $row['data_pagamento'],
            'totale_pagato' => number_format(floatval($row['totale_pagato']), 2, '.', ''),
            'tipologia' => $row['tipologia'],
            'mese_pagato' => $row['mese_pagato'],
            'nome_studente' => $row['nome_studente']
        ];
    }

    // Chiudi lo statement
    $stmt->close();

    // Restituisci i risultati
    echo json_encode([
        'success' => true,
        'data' => $payments,
        'params' => [
            'studentId' => $studentId,
            'month' => $month,
            'year' => $year,
            'meseNome' => $meseNome
        ]
    ]);

} catch (Exception $e) {
    // Log dell'errore nel file di log di PHP
    error_log("Errore in get_payment_details.php: " . $e->getMessage());
    
    // Restituisci l'errore come JSON
    http_response_code(200); // Cambia da 500 a 200 per gestire l'errore lato client
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
    ]);
}
?>