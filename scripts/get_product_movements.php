<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$product_id = $_GET['product_id'] ?? 0;

$query = "SELECT m.*, a.nome, a.cognome
          FROM movimenti_inventario m
          LEFT JOIN alunni a ON m.id_alunno = a.id
          WHERE m.id_prodotto = ?
          ORDER BY m.data_movimento DESC
          LIMIT 10";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$movements = [];
while ($row = $result->fetch_assoc()) {
    $movements[] = [
        'data' => date('d/m/Y H:i', strtotime($row['data_movimento'])),
        'tipo' => $row['tipo'],
        'quantita' => $row['quantita'],
        'alunno' => $row['nome'] ? $row['nome'] . ' ' . $row['cognome'] : null,
        'note' => $row['note'],
        'data_rientro' => $row['data_rientro'] ? date('d/m/Y', strtotime($row['data_rientro'])) : null
    ];
}

echo json_encode(['success' => true, 'movements' => $movements]);
?>