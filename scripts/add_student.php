<?php
// Includiamo il file di configurazione
require_once '../config.php';

// Avvia la sessione se non è già avviata (per usare $_SESSION['user_id'])
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica che la richiesta sia POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera i dati dal form
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $scuola = trim($_POST['scuola']);
    $id_pacchetto = intval($_POST['id_pacchetto']);
    $sconto = trim($_POST['sconto']);
    $data_iscrizione = $_POST['data_iscrizione'];
    $nome_genitore = trim($_POST['nome_genitore']);
    $residenza = trim($_POST['residenza']);
    $codice_fiscale = trim($_POST['codice_fiscale']);
    $telefono = trim($_POST['telefono']);

    // Calcola il prezzo finale con lo sconto
    $sql_pacchetto = "SELECT prezzo FROM pacchetti WHERE id = ?";
    $stmt_pacchetto = $conn->prepare($sql_pacchetto);
    if (!$stmt_pacchetto) {
        die("Errore nella preparazione della query pacchetto: " . $conn->error);
    }
    $stmt_pacchetto->bind_param("i", $id_pacchetto);
    $stmt_pacchetto->execute();
    $stmt_pacchetto->bind_result($prezzo_base);
    $stmt_pacchetto->fetch();
    $stmt_pacchetto->close();

    // Applica lo sconto (percentuale o fisso)
    if (strpos($sconto, '%') !== false) {
        $percentuale = floatval($sconto);
        $prezzo_finale = $prezzo_base - ($prezzo_base * ($percentuale / 100));
    } else {
        $prezzo_finale = $prezzo_base - floatval($sconto);
    }

    // Inizio transazione per garantire coerenza
    $conn->begin_transaction();

    try {
        // Query per inserire i dati del genitore
        $sql_genitore = "INSERT INTO genitori (nome_completo, residenza, codice_fiscale, telefono)
                         VALUES (?, ?, ?, ?)";
        $stmt_genitore = $conn->prepare($sql_genitore);
        if (!$stmt_genitore) {
            throw new Exception("Errore nella preparazione della query genitore: " . $conn->error);
        }
        $stmt_genitore->bind_param("ssss", $nome_genitore, $residenza, $codice_fiscale, $telefono);
        $stmt_genitore->execute();
        $id_genitore = $stmt_genitore->insert_id; // Otteniamo l'ID del genitore appena inserito
        $stmt_genitore->close();

        // Query per inserire un nuovo alunno
        $sql_alunno = "INSERT INTO alunni (nome, cognome, scuola, id_pacchetto, prezzo_finale, stato, data_iscrizione, id_genitore)
                       VALUES (?, ?, ?, ?, ?, 'attivo', ?, ?)";
        $stmt_alunno = $conn->prepare($sql_alunno);
        if (!$stmt_alunno) {
            throw new Exception("Errore nella preparazione della query alunno: " . $conn->error);
        }
        $stmt_alunno->bind_param("sssidsi", $nome, $cognome, $scuola, $id_pacchetto, $prezzo_finale, $data_iscrizione, $id_genitore);
        $stmt_alunno->execute();
        $id_alunno = $stmt_alunno->insert_id;
        $stmt_alunno->close();

        // REGISTRAZIONE AUTOMATICA NELLO STORICO
        $id_utente = isset($_SESSION['id']) ? $_SESSION['id'] : null;
        $sql_storico = "INSERT INTO storico_modifiche (id_alunno, id_utente, campo_modificato, valore_precedente, valore_nuovo, dettagli)
                        VALUES (?, ?, ?, ?, ?, ?)";
        // Pacchetto
        $campo = 'pacchetto';
        $valore_precedente = null;
        $valore_nuovo = $id_pacchetto;
        $dettagli = "Nuovo alunno inserito";
        $stmt_storico = $conn->prepare($sql_storico);
        $stmt_storico->bind_param("iissss", $id_alunno, $id_utente, $campo, $valore_precedente, $valore_nuovo, $dettagli);
        $stmt_storico->execute();
        // Prezzo
        $campo = 'prezzo_finale';
        $valore_nuovo = $prezzo_finale;
        $stmt_storico->bind_param("iissss", $id_alunno, $id_utente, $campo, $valore_precedente, $valore_nuovo, $dettagli);
        $stmt_storico->execute();
        // Stato
        $campo = 'stato';
        $valore_nuovo = 'attivo';
        $stmt_storico->bind_param("iissss", $id_alunno, $id_utente, $campo, $valore_precedente, $valore_nuovo, $dettagli);
        $stmt_storico->execute();
        $stmt_storico->close();

        // Commit della transazione
        $conn->commit();

        // Reindirizza alla homepage
        header('Location: ../index.php?success=1');
        exit;
    } catch (Exception $e) {
        // Rollback della transazione in caso di errore
        $conn->rollback();
        die("Errore nell'inserimento: " . $e->getMessage());
    }
} else {
    echo "Metodo non consentito.";
}
?>