<?php
// Connessione al database
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Dati del genitore
    $genitore_nome = $data['genitore_nome'];
    $genitore_telefono = $data['genitore_telefono'];
    $genitore_email = $data['genitore_email'];

    // Inserimento del genitore
    $stmt = $conn->prepare("INSERT INTO Genitori (nome, telefono, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $genitore_nome, $genitore_telefono, $genitore_email);
    $stmt->execute();
    $genitore_id = $stmt->insert_id;

    // Dati dell'alunno
    $nome = $data['nome'];
    $scuola = $data['scuola'];
    $piano = $data['piano'];
    $quota = $data['quota'];
    $ore = $data['ore'];
    $stato = $data['stato'];

    // Inserimento dell'alunno
    $stmt = $conn->prepare("INSERT INTO Alunni (nome, scuola, piano, quota, ore, stato, genitore_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdisi", $nome, $scuola, $piano, $quota, $ore, $stato, $genitore_id);
    $stmt->execute();

    echo json_encode(['success' => true]);
}
?>