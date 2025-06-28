<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['username'] !== 'alessandro') {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$movement_id = $_POST['movement_id'] ?? 0;
$tipo = $_POST['tipo'] ?? '';
$importo = (float)($_POST['importo'] ?? 0);
$categoria = $_POST['categoria'] ?? '';
$descrizione = $_POST['descrizione'] ?? '';
$metodo_pagamento = $_POST['metodo_pagamento'] ?: null;
$data_movimento = $_POST['data_movimento'] ?? '';
$fattura_emessa = isset($_POST['fattura_emessa']) ? 1 : 0;

if (!$movement_id || !$tipo || $importo <= 0 || !$categoria || !$descrizione || !$data_movimento) {
    echo json_encode(['success' => false, 'message' => 'Dati non validi']);
    exit;
}

// Verifica che il movimento non sia sincronizzato (non modificabile se collegato a pagamenti)
$query = "SELECT riferimento_id, riferimento_tipo FROM movimenti_contabili WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movement_id);
$stmt->execute();
$result = $stmt->get_result();
$movimento = $result->fetch_assoc();

if ($movimento['riferimento_id'] && $movimento['riferimento_tipo']) {
    echo json_encode(['success' => false, 'message' => 'Non puoi modificare un movimento sincronizzato']);
    exit;
}

// Aggiorna il movimento
$query = "UPDATE movimenti_contabili 
          SET tipo = ?, importo = ?, categoria = ?, descrizione = ?, 
              metodo_pagamento = ?, data_movimento = ?, fattura_emessa = ?
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sdssssii", $tipo, $importo, $categoria, $descrizione, 
                  $metodo_pagamento, $data_movimento, $fattura_emessa, $movement_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Movimento aggiornato con successo']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento']);
}
?>