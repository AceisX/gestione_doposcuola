<?php
require_once '../config.php';

if (isset($_GET['id'])) {
    $alunnoId = intval($_GET['id']);

    // Query per recuperare i dettagli dell'alunno
    $sqlAlunno = "SELECT alunni.nome, alunni.cognome, alunni.scuola, pacchetti.nome AS pacchetto,
                         alunni.prezzo_finale, alunni.stato, alunni.data_iscrizione, alunni.id_genitore
                  FROM alunni
                  LEFT JOIN pacchetti ON alunni.id_pacchetto = pacchetti.id
                  WHERE alunni.id = ?";
    $stmtAlunno = $conn->prepare($sqlAlunno);
    $stmtAlunno->bind_param("i", $alunnoId);
    $stmtAlunno->execute();
    $resultAlunno = $stmtAlunno->get_result();

    if ($resultAlunno->num_rows > 0) {
        $alunno = $resultAlunno->fetch_assoc();
        $alunno['nome_completo'] = $alunno['nome'] . " " . $alunno['cognome'];

        // Query per il genitore
        $sqlGenitore = "SELECT * FROM genitori WHERE id = ?";
        $stmtGenitore = $conn->prepare($sqlGenitore);
        $stmtGenitore->bind_param("i", $alunno['id_genitore']);
        $stmtGenitore->execute();
        $genitore = $stmtGenitore->get_result()->fetch_assoc();

        // Query per i pagamenti
        $sqlPagamenti = "SELECT * FROM pagamenti WHERE id_alunno = ?";
        $stmtPagamenti = $conn->prepare($sqlPagamenti);
        $stmtPagamenti->bind_param("i", $alunnoId);
        $stmtPagamenti->execute();
        $pagamenti = $stmtPagamenti->get_result()->fetch_all(MYSQLI_ASSOC);

        // Risposta JSON
        echo json_encode([
            'success' => true,
            'alunno' => $alunno,
            'genitore' => $genitore,
            'pagamenti' => $pagamenti
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Alunno non trovato.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID non fornito.']);
}
?>