<?php
require_once '../config.php';

if (isset($_GET['alunno_id'])) {
    $alunnoId = intval($_GET['alunno_id']);

    $sql = "SELECT id_pacchetto FROM alunni WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $alunnoId);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'id_pacchetto' => $row['id_pacchetto']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Alunno non trovato'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Errore nella query'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID alunno mancante'
    ]);
}
?>