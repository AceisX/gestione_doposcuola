<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $alunnoId = intval($_GET['id']);

    // Controlla se ci sono pagamenti associati
    $sqlCheck = "SELECT COUNT(*) AS count FROM pagamenti WHERE id_alunno = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $alunnoId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck->fetch_assoc();

    if ($rowCheck['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Non è possibile eliminare un alunno con pagamenti associati.']);
        exit;
    }

    // Elimina l'alunno
    $sqlDelete = "DELETE FROM alunni WHERE id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $alunnoId);

    if ($stmtDelete->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'alunno.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
}
?>