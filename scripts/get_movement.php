<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['username'] !== 'alessandro') {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$movement_id = $_GET['id'] ?? 0;

if (!$movement_id) {
    echo json_encode(['success' => false, 'message' => 'ID movimento non valido']);
    exit;
}

$query = "SELECT * FROM movimenti_contabili WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movement_id);
$stmt->execute();
$result = $stmt->get_result();

if ($movement = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'movement' => $movement]);
} else {
    echo json_encode(['success' => false, 'message' => 'Movimento non trovato']);
}
?>