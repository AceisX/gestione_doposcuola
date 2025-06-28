<?php
// scripts/generate_report_tutor.php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) {
    die('Accesso negato');
}

$tutor_ids = isset($_POST['tutor_ids']) ? $_POST['tutor_ids'] : [];
$periodo = isset($_POST['periodo']) ? $_POST['periodo'] : date('Y-m');

// Validate input
if (!is_array($tutor_ids) || count($tutor_ids) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No tutors selected']);
    exit;
}

// Validate periodo format
if (!preg_match('/^(anno-\d{4}|\d{4}-\d{2})$/', $periodo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid period format']);
    exit;
}

// Determina se è un mese specifico o tutto l'anno
if (strpos($periodo, 'anno-') === 0) {
    $anno = substr($periodo, 5);
    $whereDate = "YEAR(l.data) = $anno";
    $periodoText = "Anno $anno";
} else {
    list($anno, $mese) = explode('-', $periodo);
    $whereDate = "YEAR(l.data) = $anno AND MONTH(l.data) = $mese";
    $mesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    $periodoText = $mesi[intval($mese)] . " $anno";
}

// Mapping slot orari
$slot_mapping = [
    '15:30-16:30' => 'Slot 1 (15:30-16:30)',
    '16:30-17:30' => 'Slot 2 (16:30-17:30)', 
    '17:30-18:30' => 'Slot 3 (17:30-18:30)'
];

// Headers per Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="report_tutor_' . str_replace('-', '_', $periodo) . '.xls"');
header('Cache-Control: max-age=0');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        .tutor-name { background-color: #2196F3; color: white; font-size: 20px; padding: 10px; margin: 20px 0 10px 0; font-weight: bold; }
        .date-cell { background-color: #f0f0f0; font-weight: bold; white-space: nowrap; }
        .half-hour { background-color: #FFA500 !important; color: #000; }
        .empty-slot { color: #999; text-align: center; }
        .summary-row { background-color: #e7f3ff; font-weight: bold; }
        .stats-table { margin-top: 20px; width: 50%; }
        .stats-table th { background-color: #666; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <h1>Report Lezioni Tutor - <?php echo $periodoText; ?></h1>
    <p>Generato il: <?php echo date('d/m/Y H:i'); ?></p>

<?php
// Per ogni tutor selezionato
foreach ($tutor_ids as $index => $tutor_id) {
    // Ottieni info tutor
    $tutorQuery = "SELECT nome, cognome FROM tutor WHERE id = ?";
    $stmt = $conn->prepare($tutorQuery);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $tutorResult = $stmt->get_result();
    $tutor = $tutorResult->fetch_assoc();
    
    if (!$tutor) continue;
    
    echo '<div class="tutor-name">' . htmlspecialchars($tutor['nome'] . ' ' . $tutor['cognome']) . '</div>';
    
    // Query semplificata per ottenere TUTTE le lezioni
    $query = "SELECT 
                l.id,
                l.data,
                l.slot_orario,
                l.durata,
                l.tipo,
                GROUP_CONCAT(
                    CONCAT(a.nome, ' ', a.cognome) 
                    ORDER BY a.nome 
                    SEPARATOR '<br>'
                ) as alunni
              FROM lezioni l
              LEFT JOIN lezioni_alunni la ON l.id = la.id_lezione
              LEFT JOIN alunni a ON la.id_alunno = a.id
              WHERE l.id_tutor = ? AND $whereDate
              GROUP BY l.id, l.data, l.slot_orario, l.durata, l.tipo
              ORDER BY l.data ASC, 
                       CASE l.slot_orario 
                           WHEN '15:30-16:30' THEN 1
                           WHEN '16:30-17:30' THEN 2
                           WHEN '17:30-18:30' THEN 3
                       END";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Organizza i dati per data
    $lezioni_per_data = [];
    $totale_lezioni = 0;
    $totale_ore = 0;
    $totale_singole = 0;
    $totale_gruppo = 0;
    
    while ($row = $result->fetch_assoc()) {
        $data = $row['data'];
        if (!isset($lezioni_per_data[$data])) {
            $lezioni_per_data[$data] = [
                '15:30-16:30' => null,
                '16:30-17:30' => null,
                '17:30-18:30' => null
            ];
        }
        
        $lezioni_per_data[$data][$row['slot_orario']] = [
            'alunni' => $row['alunni'],
            'durata' => $row['durata'],
            'tipo' => $row['tipo']
        ];
        
        $totale_lezioni++;
        $totale_ore += ($row['durata'] == 0) ? 1 : 0.5;
        
        if ($row['tipo'] == 'singolo') {
            $totale_singole++;
        } else {
            $totale_gruppo++;
        }
    }
    
    // Crea la tabella
    echo '<table>';
    echo '<tr>';
    echo '<th width="100">Data</th>';
    echo '<th width="200">Slot 1<br>15:30-16:30</th>';
    echo '<th width="200">Slot 2<br>16:30-17:30</th>';
    echo '<th width="200">Slot 3<br>17:30-18:30</th>';
    echo '</tr>';
    
    // Stampa le lezioni organizzate per data
    foreach ($lezioni_per_data as $data => $slots) {
        echo '<tr>';
        echo '<td class="date-cell">' . date('d/m/Y', strtotime($data)) . '</td>';
        
        foreach (['15:30-16:30', '16:30-17:30', '17:30-18:30'] as $orario) {
            if ($slots[$orario] !== null) {
                $class = ($slots[$orario]['durata'] == 1) ? 'half-hour' : '';
                $tipo_badge = $slots[$orario]['tipo'] == 'singolo' ? ' [S]' : ' [G]';
                $alunni_text = $slots[$orario]['alunni'] ?: 'Nessun alunno';
                
                echo '<td class="' . $class . '">';
                echo $alunni_text . $tipo_badge;
                if ($slots[$orario]['durata'] == 1) {
                    echo '<br><small>(½ ora)</small>';
                }
                echo '</td>';
            } else {
                echo '<td class="empty-slot">-</td>';
            }
        }
        echo '</tr>';
    }
    
    // Riga di riepilogo
    echo '<tr class="summary-row">';
    echo '<td colspan="4">';
    echo 'Totale lezioni: ' . $totale_lezioni . ' | ';
    echo 'Totale ore: ' . $totale_ore . ' | ';
    echo 'Singole: ' . $totale_singole . ' | ';
    echo 'Gruppo: ' . $totale_gruppo;
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    
    // Statistiche dettagliate
    echo '<h3>Statistiche Dettagliate</h3>';
    echo '<table class="stats-table">';
    echo '<tr><th>Metrica</th><th>Valore</th></tr>';
    
    // Conta alunni unici
    $uniqueQuery = "SELECT COUNT(DISTINCT la.id_alunno) as alunni_unici
                    FROM lezioni l
                    JOIN lezioni_alunni la ON l.id = la.id_lezione
                    WHERE l.id_tutor = ? AND $whereDate";
    $stmt = $conn->prepare($uniqueQuery);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $uniqueResult = $stmt->get_result();
    $alunniUnici = $uniqueResult->fetch_assoc()['alunni_unici'];
    
    echo '<tr><td>Alunni unici seguiti</td><td>' . $alunniUnici . '</td></tr>';
    echo '<tr><td>Media alunni per lezione</td><td>' . ($totale_lezioni > 0 ? round($alunniUnici / $totale_lezioni, 1) : 0) . '</td></tr>';
    
    // Distribuzione per giorno della settimana
    $giornoQuery = "SELECT 
                      DAYNAME(l.data) as giorno,
                      COUNT(*) as numero
                    FROM lezioni l
                    WHERE l.id_tutor = ? AND $whereDate
                    GROUP BY DAYOFWEEK(l.data)
                    ORDER BY DAYOFWEEK(l.data)";
    $stmt = $conn->prepare($giornoQuery);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $giornoResult = $stmt->get_result();
    
    $giorni_it = [
        'Monday' => 'Lunedì',
        'Tuesday' => 'Martedì',
        'Wednesday' => 'Mercoledì',
        'Thursday' => 'Giovedì',
        'Friday' => 'Venerdì',
        'Saturday' => 'Sabato',
        'Sunday' => 'Domenica'
    ];
    
    echo '<tr><td colspan="2"><strong>Distribuzione per giorno</strong></td></tr>';
    while ($giorno = $giornoResult->fetch_assoc()) {
        $nome_giorno = $giorni_it[$giorno['giorno']] ?? $giorno['giorno'];
        echo '<tr><td>' . $nome_giorno . '</td><td>' . $giorno['numero'] . '</td></tr>';
    }
    
    echo '</table>';
    
    // Interruzione di pagina tra tutor (tranne l'ultimo)
    if ($index < count($tutor_ids) - 1) {
        echo '<div class="page-break"></div>';
    }
}
?>

<div style="margin-top: 40px; padding: 20px; background-color: #f0f0f0; border: 1px solid #ccc;">
    <h4>Legenda:</h4>
    <ul style="margin: 0; padding-left: 20px;">
        <li><strong>[S]</strong> = Lezione Singola</li>
        <li><strong>[G]</strong> = Lezione di Gruppo</li>
        <li><strong>Celle arancioni</strong> = Lezioni di mezz'ora</li>
        <li><strong>-</strong> = Slot libero</li>
    </ul>
</div>

</body>
</html>
<?php
$conn->close();
?>
