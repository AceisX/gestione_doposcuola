<?php
require_once '../config.php';

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

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Metodo non valido.']);
}
?>