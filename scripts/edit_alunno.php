<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alunnoId = intval($_POST['alunno_id']);
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $scuola = $_POST['scuola'];
    $idPacchetto = intval($_POST['id_pacchetto']);
    $stato = $_POST['stato'];
    $nomeGenitore = $_POST['nome_genitore'];
    $residenza = $_POST['residenza'];
    $codiceFiscale = $_POST['codice_fiscale'];
    $telefono = $_POST['telefono'];
    $prezzo_finale = $_POST['prezzo_finale'];

    // Recupera i dati ATTUALI dell'alunno prima della modifica
    $sqlOld = "SELECT id_pacchetto, prezzo_finale, stato FROM alunni WHERE id = ?";
    $stmtOld = $conn->prepare($sqlOld);
    $stmtOld->bind_param("i", $alunnoId);
    $stmtOld->execute();
    $stmtOld->bind_result($old_pacchetto, $old_prezzo, $old_stato);
    $stmtOld->fetch();
    $stmtOld->close();

    // Aggiorna l'anagrafica dell'alunno
    $sqlAlunno = "UPDATE alunni SET nome = ?, cognome = ?, scuola = ?, id_pacchetto = ?, prezzo_finale = ?, stato = ? WHERE id = ?";
    $stmtAlunno = $conn->prepare($sqlAlunno);
    $stmtAlunno->bind_param("sssiisi", $nome, $cognome, $scuola, $idPacchetto, $prezzo_finale, $stato, $alunnoId);
    $stmtAlunno->execute();

    // Aggiorna i dati del genitore
    $sqlGenitore = "UPDATE genitori SET nome_completo = ?, residenza = ?, codice_fiscale = ?, telefono = ? WHERE id = (SELECT id_genitore FROM alunni WHERE id = ?)";
    $stmtGenitore = $conn->prepare($sqlGenitore);
    $stmtGenitore->bind_param("sssii", $nomeGenitore, $residenza, $codiceFiscale, $telefono, $alunnoId);
    $stmtGenitore->execute();

    // REGISTRAZIONE AUTOMATICA STORICO MODIFICHE
    $id_utente = isset($_SESSION['id']) ? $_SESSION['id'] : null;
    $sql_storico = "INSERT INTO storico_modifiche (id_alunno, id_utente, campo_modificato, valore_precedente, valore_nuovo, dettagli)
                    VALUES (?, ?, ?, ?, ?, ?)";
    $dettagli = "Modifica da pannello di gestione";

    // Pacchetto
    if ($old_pacchetto != $idPacchetto) {
        $campo = 'pacchetto';
        $valore_precedente = $old_pacchetto;
        $valore_nuovo = $idPacchetto;
        $stmt_storico = $conn->prepare($sql_storico);
        $stmt_storico->bind_param("iissss", $alunnoId, $id_utente, $campo, $valore_precedente, $valore_nuovo, $dettagli);
        $stmt_storico->execute();
        $stmt_storico->close();
    }
    // Prezzo
    if ($old_prezzo != $prezzo_finale) {
        $campo = 'prezzo_finale';
        $valore_precedente = $old_prezzo;
        $valore_nuovo = $prezzo_finale;
        $stmt_storico = $conn->prepare($sql_storico);
        $stmt_storico->bind_param("iissss", $alunnoId, $id_utente, $campo, $valore_precedente, $valore_nuovo, $dettagli);
        $stmt_storico->execute();
        $stmt_storico->close();
    }
    // Stato
    if ($old_stato != $stato) {
        $campo = 'stato';
        $valore_precedente = $old_stato;
        $valore_nuovo = $stato;
        $stmt_storico = $conn->prepare($sql_storico);
        $stmt_storico->bind_param("iissss", $alunnoId, $id_utente, $campo, $valore_precedente, $valore_nuovo, $dettagli);
        $stmt_storico->execute();
        $stmt_storico->close();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Metodo non valido.']);
}
?>