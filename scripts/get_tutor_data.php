<?php
require_once '../config.php';

header('Content-Type: application/json');

$tutor_id = intval($_GET['id'] ?? 0);

if (!$tutor_id) {
    echo json_encode(['success' => false, 'message' => 'ID tutor mancante']);
    exit;
}

try {
    $sql = "SELECT id, nome, cognome, email, telefono FROM tutor WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($tutor = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'tutor' => $tutor]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tutor non trovato']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>