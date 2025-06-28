<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'ID prodotto non valido']);
    exit;
}

// Verifica se ci sono movimenti associati
$query = "SELECT COUNT(*) as count FROM movimenti_inventario WHERE id_prodotto = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];

if ($count > 0) {
    echo json_encode(['success' => false, 'message' => 'Impossibile eliminare: il prodotto ha movimenti registrati']);
    exit;
}

// Elimina prodotto
$query = "DELETE FROM inventario WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Prodotto eliminato con successo']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione']);
}
?>