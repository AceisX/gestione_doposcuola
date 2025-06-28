<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$product_id = $_POST['product_id'] ?? 0;
$tipo = $_POST['tipo_movimento'] ?? '';
$quantita = (int)($_POST['quantita'] ?? 0);
$id_alunno = $_POST['id_alunno'] ?: null;
$data_rientro = $_POST['data_rientro'] ?: null;
$note = $_POST['note'] ?? '';

if (!$product_id || !$tipo || $quantita <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dati non validi']);
    exit;
}

// Verifica disponibilità per uscite
if ($tipo === 'uscita') {
    $query = "SELECT quantita FROM inventario WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prodotto = $result->fetch_assoc();
    
    if ($prodotto['quantita'] < $quantita) {
        echo json_encode(['success' => false, 'message' => 'Quantità non disponibile']);
        exit;
    }
}

// Inizia transazione
$conn->begin_transaction();

try {
    // Registra movimento
    $query = "INSERT INTO movimenti_inventario (id_prodotto, tipo, quantita, id_alunno, data_rientro, note) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isiiss", $product_id, $tipo, $quantita, $id_alunno, $data_rientro, $note);
    $stmt->execute();
    
    // Aggiorna quantità inventario
    if ($tipo === 'entrata') {
        $query = "UPDATE inventario SET quantita = quantita + ? WHERE id = ?";
    } else {
        $query = "UPDATE inventario SET quantita = quantita - ? WHERE id = ?";
    }
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $quantita, $product_id);
    $stmt->execute();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Movimento registrato con successo']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio: ' . $e->getMessage()]);
}
?>