<?php
require_once '../config.php';

if (isset($_GET['tutor_id'], $_GET['mensilita'])) {
    $tutorId = intval($_GET['tutor_id']);
    $mensilita = $_GET['mensilita'];

    $sql = "
        SELECT t.nome, t.cognome, pt.ore_singole, pt.ore_gruppo, pt.mezze_ore_singole, pt.mezze_ore_gruppo, pt.paga
        FROM tutor t
        LEFT JOIN pagamenti_tutor pt ON t.id = pt.tutor_id
        WHERE t.id = ? AND pt.mensilita = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $tutorId, $mensilita);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Calcola il totale delle ore (singole e di gruppo)
        $oreSingoleTotali = $row['ore_singole'] + floor($row['mezze_ore_singole'] / 2);
        $oreGruppoTotali = $row['ore_gruppo'] + floor($row['mezze_ore_gruppo'] / 2);

        echo json_encode([
            'success' => true,
            'tutor' => [
                'nome_completo' => $row['nome'] . ' ' . $row['cognome'],
                'ore_singole' => $oreSingoleTotali,
                'ore_gruppo' => $oreGruppoTotali,
                'paga' => $row['paga']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Dati del tutor non trovati.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Parametri non validi.']);
}
?>