<?php
require_once '../config.php';

if (isset($_GET['mese']) && isset($_GET['alunni'])) {
    $mese = $_GET['mese'];
    $alunni = explode(',', $_GET['alunni']);
    $export = isset($_GET['export']) ? true : false;

    if (empty($mese) || empty($alunni)) {
        echo json_encode(["error" => "Parametri mancanti: mese o alunni"]);
        exit;
    }

    // Converti il mese da "2025-05" a "Maggio 2025"
    $dateObj = DateTime::createFromFormat('Y-m', $mese);
    if (!$dateObj) {
        echo json_encode(["error" => "Formato mese non valido"]);
        exit;
    }

    setlocale(LC_TIME, 'it_IT.UTF-8'); // Imposta la localizzazione in italiano
    $meseFormattato = strftime('%B %Y', $dateObj->getTimestamp());

    if ($export) {
        // Esporta in CSV
        $filename = "report_alunni_{$mese}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Nome Alunno', 'Data Lezione', 'Slot Orario', 'Tutor']);

        // Query per il CSV
        $sqlDettagliLezioni = "
            SELECT DISTINCT
                CONCAT(alunni.nome, ' ', alunni.cognome) AS nome_completo,
                lezioni.data AS data_lezione,
                lezioni.slot_orario AS slot_orario,
                CONCAT(tutor.nome, ' ', tutor.cognome) AS tutor
            FROM alunni
            JOIN lezioni_alunni ON alunni.id = lezioni_alunni.id_alunno
            JOIN lezioni ON lezioni_alunni.id_lezione = lezioni.id
            JOIN tutor ON lezioni.id_tutor = tutor.id
            WHERE 
                alunni.id IN (" . implode(',', array_fill(0, count($alunni), '?')) . ")
                AND DATE_FORMAT(lezioni.data, '%Y-%m') = ?
        ";

        $stmtCSV = $conn->prepare($sqlDettagliLezioni);
        if (!$stmtCSV) {
            error_log("Errore nella preparazione della query CSV: " . $conn->error);
            exit;
        }

        $stmtCSV->bind_param(str_repeat('i', count($alunni)) . 's', ...array_merge($alunni, [$mese]));
        $stmtCSV->execute();
        $resultCSV = $stmtCSV->get_result();

        while ($row = $resultCSV->fetch_assoc()) {
            fputcsv($output, [
                $row['nome_completo'],
                $row['data_lezione'],
                $row['slot_orario'],
                $row['tutor']
            ]);
        }

        fclose($output);
        exit;
    } else {
        // Restituisci il JSON per il report
        $sqlTotaleOre = "
            SELECT 
                alunni.id AS alunno_id,
                CONCAT(alunni.nome, ' ', alunni.cognome) AS nome_completo,
                COUNT(DISTINCT lezioni.id) AS ore_totali
            FROM alunni
            LEFT JOIN lezioni_alunni ON alunni.id = lezioni_alunni.id_alunno
            LEFT JOIN lezioni ON lezioni_alunni.id_lezione = lezioni.id
                  AND DATE_FORMAT(lezioni.data, '%Y-%m') = ?
            WHERE alunni.id IN (" . implode(',', array_fill(0, count($alunni), '?')) . ")
            GROUP BY alunni.id
        ";

        $stmt = $conn->prepare($sqlTotaleOre);
        if (!$stmt) {
            echo json_encode(["error" => "Errore nella preparazione della query: " . $conn->error]);
            exit;
        }

        $params = array_merge([$mese], $alunni);
        $stmt->bind_param('s' . str_repeat('i', count($alunni)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => $row['alunno_id'],
                'nome' => $row['nome_completo'],
                'ore' => $row['ore_totali']
            ];
        }

        echo json_encode([
            'mese' => $meseFormattato,
            'data' => $data
        ]);
    }
} else {
    echo json_encode(["error" => "Parametri mancanti o non validi."]);
    exit;
}
?>