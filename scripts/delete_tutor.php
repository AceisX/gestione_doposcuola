<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$tutor_id = intval($_POST['tutor_id'] ?? 0);

if (!$tutor_id) {
    echo json_encode(['success' => false, 'message' => 'ID tutor mancante']);
    exit;
}

try {
    // Nota: grazie ai CASCADE nelle foreign key, verranno eliminate automaticamente
    // anche le lezioni e i pagamenti associati
    $sql = "DELETE FROM tutor WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tutor_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tutor eliminato con successo']);
    } else {
        throw new Exception('Errore durante l\'eliminazione');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>