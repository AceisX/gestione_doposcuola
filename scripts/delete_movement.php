<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['username'] !== 'alessandro') {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$movement_id = $data['movement_id'] ?? 0;

if (!$movement_id) {
    echo json_encode(['success' => false, 'message' => 'ID movimento non valido']);
    exit;
}

$query = "DELETE FROM movimenti_contabili WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movement_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Movimento eliminato con successo']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione']);
}
?>