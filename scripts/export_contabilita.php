<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['username'] !== 'alessandro') {
    header('Location: ../pages/login.php');
    exit;
}

$vista = $_GET['vista'] ?? 'mensile';
$anno = $_GET['anno'] ?? date('Y');
$mese = $_GET['mese'] ?? date('n');

// Nome file
if ($vista === 'mensile') {
    $filename = "contabilita_" . $anno . "_" . str_pad($mese, 2, '0', STR_PAD_LEFT) . ".csv";
} else {
    $filename = "contabilita_annuale_" . $anno . ".csv";
}

// Headers per il download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Crea output
$output = fopen('php://output', 'w');

// Aggiungi BOM per Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers CSV
fputcsv($output, ['Data', 'Tipo', 'Importo', 'Categoria', 'Descrizione', 'Metodo Pagamento', 'Fattura Emessa'], ';');

// Query dati
if ($vista === 'mensile') {
    $query = "SELECT * FROM movimenti_contabili 
              WHERE MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?
              ORDER BY data_movimento DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mese, $anno);
} else {
    $query = "SELECT * FROM movimenti_contabili 
              WHERE YEAR(data_movimento) = ?
              ORDER BY data_movimento DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $anno);
}

$stmt->execute();
$result = $stmt->get_result();

$totale_entrate = 0;
$totale_uscite = 0;

while ($row = $result->fetch_assoc()) {
    $line = [
        date('d/m/Y', strtotime($row['data_movimento'])),
        ucfirst($row['tipo']),
        number_format($row['importo'], 2, ',', '.'),
        $row['categoria'] ?: 'Non categorizzato',
        $row['descrizione'],
        $row['metodo_pagamento'] ? ucfirst($row['metodo_pagamento']) : '-',
        $row['fattura_emessa'] ? 'Sì' : 'No'
    ];
    
    fputcsv($output, $line, ';');
    
    if ($row['tipo'] == 'entrata') {
        $totale_entrate += $row['importo'];
    } else {
        $totale_uscite += $row['importo'];
    }
}

// Aggiungi totali
fputcsv($output, [], ';');
fputcsv($output, ['RIEPILOGO'], ';');
fputcsv($output, ['Totale Entrate', '', number_format($totale_entrate, 2, ',', '.')], ';');
fputcsv($output, ['Totale Uscite', '', number_format($totale_uscite, 2, ',', '.')], ';');
fputcsv($output, ['Bilancio', '', number_format($totale_entrate - $totale_uscite, 2, ',', '.')], ';');

// Se vista annuale, aggiungi riepilogo mensile
if ($vista === 'annuale') {
    fputcsv($output, [], ';');
    fputcsv($output, ['RIEPILOGO MENSILE'], ';');
    fputcsv($output, ['Mese', 'Entrate', 'Uscite', 'Bilancio'], ';');
    
    $mesi_italiano = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
    ];
    
    for ($m = 1; $m <= 12; $m++) {
        $query = "SELECT 
                  SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
                  SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite
                  FROM movimenti_contabili 
                  WHERE MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $m, $anno);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $entrate_mese = $row['entrate'] ?: 0;
        $uscite_mese = $row['uscite'] ?: 0;
        $bilancio_mese = $entrate_mese - $uscite_mese;
        
        fputcsv($output, [
            $mesi_italiano[$m],
            number_format($entrate_mese, 2, ',', '.'),
            number_format($uscite_mese, 2, ',', '.'),
            number_format($bilancio_mese, 2, ',', '.')
        ], ';');
    }
}

fclose($output);
exit();
?>