<?php
// Includiamo il file di configurazione per la connessione al database
require_once '../config.php';

if (isset($_GET['mese'])) {
    $mese = $_GET['mese'];

    // Query per recuperare gli alunni iscritti in un determinato mese
    $sql = "SELECT 
                CONCAT(alunni.nome, ' ', alunni.cognome) AS nome_completo,
                alunni.scuola,
                pacchetti.nome AS pacchetto,
                alunni.prezzo_finale,
                alunni.stato,
                alunni.data_iscrizione
            FROM 
                alunni
            LEFT JOIN 
                pacchetti ON alunni.id_pacchetto = pacchetti.id
            WHERE 
                DATE_FORMAT(alunni.data_iscrizione, '%Y-%m') = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Errore nella preparazione della query: " . $conn->error);
    }

    $stmt->bind_param("s", $mese);
    $stmt->execute();
    $result = $stmt->get_result();

    // Creazione del file CSV
    $filename = "report_alunni_$mese.csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Intestazione del file CSV
    fputcsv($output, ['Nome Alunno', 'Scuola', 'Pacchetto', 'Prezzo Pagato', 'Stato', 'Data Iscrizione']);

    // Dati degli alunni
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['nome_completo'],
            $row['scuola'],
            $row['pacchetto'],
            number_format($row['prezzo_finale'], 2),
            $row['stato'],
            $row['data_iscrizione']
        ]);
    }

    fclose($output);
    exit;
} else {
    echo "Mese non selezionato.";
}
?>