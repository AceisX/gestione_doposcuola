<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['username'] !== 'alessandro') {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$anno = $data['anno'] ?? date('Y');
$mese = isset($data['mese']) ? $data['mese'] : null;

$alunni_sincronizzati = 0;
$tutor_sincronizzati = 0;

$conn->begin_transaction();

try {
    // Costruisci la WHERE clause in base alla vista
    if ($mese !== null) {
        $where_clause = "MONTH(p.data_pagamento) = ? AND YEAR(p.data_pagamento) = ?";
        $params = [$mese, $anno];
        $types = "ii";
    } else {
        $where_clause = "YEAR(p.data_pagamento) = ?";
        $params = [$anno];
        $types = "i";
    }
    
    // Sincronizza pagamenti alunni
    $query = "SELECT p.*, a.nome, a.cognome, p.id as pagamento_id, a.id as alunno_id
              FROM pagamenti p
              JOIN alunni a ON p.id_alunno = a.id
              WHERE $where_clause
              AND NOT EXISTS (
                  SELECT 1 FROM movimenti_contabili mc 
                  WHERE mc.riferimento_id = a.id 
                  AND mc.riferimento_tipo = 'alunno'
                  AND mc.tipo = 'entrata'
                  AND DATE(mc.data_movimento) = DATE(p.data_pagamento)
                  AND mc.importo = p.totale_pagato
              )";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $descrizione = "Pagamento " . $row['mese_pagato'] . " - " . $row['nome'] . " " . $row['cognome'] . " (" . $row['tipologia'] . ")";
        $metodo = strtolower($row['metodo_pagamento']);
        if ($metodo == 'carta') $metodo = 'pos';
        
        $insert = "INSERT INTO movimenti_contabili 
                   (tipo, importo, categoria, descrizione, metodo_pagamento, data_movimento, 
                    riferimento_id, riferimento_tipo, fattura_emessa) 
                   VALUES ('entrata', ?, 'Rette Alunni', ?, ?, ?, ?, 'alunno', 0)";
        
        $stmt_insert = $conn->prepare($insert);
        $stmt_insert->bind_param("dsssi", 
            $row['totale_pagato'], 
            $descrizione, 
            $metodo, 
            $row['data_pagamento'],
            $row['alunno_id']
        );
        $stmt_insert->execute();
        $alunni_sincronizzati++;
    }
    
    // Sincronizza pagamenti tutor con la stessa logica
    $where_clause_tutor = str_replace('p.data_pagamento', 'pt.data_pagamento', $where_clause);
    
    $query = "SELECT pt.*, t.nome, t.cognome, pt.id as pagamento_tutor_id, t.id as tutor_id
              FROM pagamenti_tutor pt
              JOIN tutor t ON pt.tutor_id = t.id
              WHERE pt.stato = 1 
              AND $where_clause_tutor
              AND NOT EXISTS (
                  SELECT 1 FROM movimenti_contabili mc 
                  WHERE mc.riferimento_id = t.id 
                  AND mc.riferimento_tipo = 'tutor'
                  AND mc.tipo = 'uscita'
                  AND DATE(mc.data_movimento) = DATE(pt.data_pagamento)
                  AND mc.importo = pt.paga
              )";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $mesi_italiano = [
            '01' => 'Gennaio', '02' => 'Febbraio', '03' => 'Marzo', '04' => 'Aprile',
            '05' => 'Maggio', '06' => 'Giugno', '07' => 'Luglio', '08' => 'Agosto',
            '09' => 'Settembre', '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
        ];
        
        $mese_ref = date('m', strtotime($row['mensilita']));
        $anno_ref = date('Y', strtotime($row['mensilita']));
        
        $descrizione = "Pagamento tutor " . $mesi_italiano[$mese_ref] . " " . $anno_ref . 
                      " - " . $row['nome'] . " " . $row['cognome'];
        
        $insert = "INSERT INTO movimenti_contabili 
                   (tipo, importo, categoria, descrizione, metodo_pagamento, data_movimento, 
                    riferimento_id, riferimento_tipo, fattura_emessa) 
                   VALUES ('uscita', ?, 'Stipendi Tutor', ?, 'contanti', ?, ?, 'tutor', 1)";
        
        $stmt_insert = $conn->prepare($insert);
        $stmt_insert->bind_param("dssi", 
            $row['paga'], 
            $descrizione, 
            $row['data_pagamento'],
            $row['tutor_id']
        );
        $stmt_insert->execute();
        $tutor_sincronizzati++;
    }
    
    $conn->commit();
    echo json_encode([
        'success' => true, 
        'alunni_sincronizzati' => $alunni_sincronizzati,
        'tutor_sincronizzati' => $tutor_sincronizzati
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>