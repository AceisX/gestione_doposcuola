<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

$anno = $_GET['anno'] ?? date('Y');
$stato = $_GET['stato'] ?? '';

// Query per ottenere i dati
$sql = "SELECT 
    t.nome,
    t.cognome,
    pt.mensilita,
    pt.paga,
    pt.ore_singole,
    pt.ore_gruppo,
    pt.mezze_ore_singole,
    pt.mezze_ore_gruppo,
    pt.stato,
    pt.data_pagamento,
    pt.note
FROM pagamenti_tutor pt
INNER JOIN tutor t ON pt.tutor_id = t.id
WHERE YEAR(pt.mensilita) = ?";

if ($stato == 'pagato') {
    $sql .= " AND pt.stato = 1";
} elseif ($stato == 'non-pagato') {
    $sql .= " AND pt.stato = 0";
}

$sql .= " ORDER BY t.cognome, t.nome, pt.mensilita";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $anno);
$stmt->execute();
$result = $stmt->get_result();

// Imposta headers per download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="pagamenti_tutor_' . $anno . '.xls"');
header('Cache-Control: max-age=0');

// Inizio output HTML per Excel
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; }
        th { background-color: #4CAF50; color: white; }
        .pagato { background-color: #90EE90; }
        .non-pagato { background-color: #FFB6C1; }
    </style>
</head>
<body>
    <h2>Report Pagamenti Tutor - Anno ' . $anno . '</h2>
    <table>
        <thead>
            <tr>
                <th>Tutor</th>
                <th>Mese</th>
                <th>Importo</th>
                <th>Ore Singole</th>
                <th>Ore Gruppo</th>
                <th>Mezze Ore Singole</th>
                <th>Mezze Ore Gruppo</th>
                <th>Stato</th>
                <th>Data Pagamento</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>';

$totale_pagato = 0;
$totale_da_pagare = 0;

while ($row = $result->fetch_assoc()) {
    $stato_class = $row['stato'] == 1 ? 'pagato' : 'non-pagato';
    $stato_text = $row['stato'] == 1 ? 'Pagato' : 'Non Pagato';
    
    if ($row['stato'] == 1) {
        $totale_pagato += $row['paga'];
    } else {
        $totale_da_pagare += $row['paga'];
    }
    
    echo '<tr class="' . $stato_class . '">
            <td>' . htmlspecialchars($row['cognome'] . ' ' . $row['nome']) . '</td>
            <td>' . date('m/Y', strtotime($row['mensilita'])) . '</td>
            <td>€' . number_format($row['paga'], 2) . '</td>
            <td>' . $row['ore_singole'] . '</td>
            <td>' . $row['ore_gruppo'] . '</td>
            <td>' . $row['mezze_ore_singole'] . '</td>
            <td>' . $row['mezze_ore_gruppo'] . '</td>
            <td>' . $stato_text . '</td>
            <td>' . ($row['data_pagamento'] ? date('d/m/Y', strtotime($row['data_pagamento'])) : '-') . '</td>
            <td>' . htmlspecialchars($row['note'] ?? '') . '</td>
          </tr>';
}

echo '</tbody>
        <tfoot>
            <tr>
                <th colspan="2">TOTALI</th>
                <th colspan="8"></th>
            </tr>
            <tr>
                <td colspan="2">Totale Pagato</td>
                <td colspan="8">€' . number_format($totale_pagato, 2) . '</td>
            </tr>
            <tr>
                <td colspan="2">Totale Da Pagare</td>
                <td colspan="8">€' . number_format($totale_da_pagare, 2) . '</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>';
?>