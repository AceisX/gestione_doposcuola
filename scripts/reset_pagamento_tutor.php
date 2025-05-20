<?php
require_once '../config.php';

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID mancante']);
    exit;
}

$id = intval($_POST['id']);

$sql = "UPDATE pagamenti_tutor SET stato = 0, data_pagamento = NULL WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nel reset']);
}
$stmt->close();
$conn->close();
?>