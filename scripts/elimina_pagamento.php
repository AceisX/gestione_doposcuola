<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $pagamentoId = intval($_GET['id']);

    // Verifica che l'ID del pagamento sia valido
    if ($pagamentoId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID pagamento non valido.']);
        exit;
    }

    // Elimina il pagamento dal database
    $sql = "DELETE FROM pagamenti WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Errore nella preparazione della query SQL.',
            'error' => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("i", $pagamentoId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pagamento eliminato con successo.']);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Errore durante l\'eliminazione del pagamento.',
            'error' => $stmt->error
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
}
?>