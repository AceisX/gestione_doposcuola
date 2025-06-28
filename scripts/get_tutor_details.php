<?php
require_once '../config.php';

$tutorId = intval($_GET['tutor_id'] ?? 0);

if ($tutorId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$sql = "
    SELECT 
        t.nome, 
        t.cognome, 
        pt.id, 
        DATE_FORMAT(pt.mensilita, '%b %Y') AS mese, 
        pt.paga, 
        (pt.ore_singole + FLOOR(pt.mezze_ore_singole/2)) as ore_singole_totali,
        (pt.ore_gruppo + FLOOR(pt.mezze_ore_gruppo/2)) as ore_gruppo_totali,
        pt.ore_singole,
        pt.ore_gruppo,
        pt.mezze_ore_singole,
        pt.mezze_ore_gruppo,
        pt.data_pagamento, 
        pt.stato, 
        pt.note
    FROM tutor t
    LEFT JOIN pagamenti_tutor pt ON t.id = pt.tutor_id
    WHERE t.id = ?
    ORDER BY pt.mensilita DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $tutorId);
$stmt->execute();
$result = $stmt->get_result();

$tutor = null;
$mensilita = [];

while ($row = $result->fetch_assoc()) {
    if (!$tutor) {
        $tutor = ['nome' => $row['nome'], 'cognome' => $row['cognome']];
    }
    $mensilita[] = $row;
}

echo json_encode(['success' => true, 'tutor' => $tutor, 'mensilita' => $mensilita]);