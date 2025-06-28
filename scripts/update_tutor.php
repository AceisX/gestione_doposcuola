<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$tutor_id = intval($_POST['tutor_id'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

if (!$tutor_id || empty($nome) || empty($cognome)) {
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

try {
    $sql = "UPDATE tutor SET nome = ?, cognome = ?, email = ?, telefono = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nome, $cognome, $email, $telefono, $tutor_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tutor aggiornato con successo']);
    } else {
        throw new Exception('Errore durante l\'aggiornamento');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>