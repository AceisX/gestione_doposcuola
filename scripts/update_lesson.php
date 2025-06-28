<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Non autorizzato']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Metodo non consentito']));
}

$lesson_id = intval($_POST['lesson_id']);
$half_lesson = isset($_POST['half_lesson']) ? 1 : 0;

if (!$lesson_id) {
    die(json_encode(['success' => false, 'message' => 'ID lezione mancante']));
}

try {
    // Aggiorna solo il campo durata (mezza lezione)
    $sql = "UPDATE lezioni SET durata = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $half_lesson, $lesson_id);
    $stmt->execute();
    
    if ($stmt->affected_rows >= 0) {
        echo json_encode(['success' => true, 'message' => 'Lezione aggiornata con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nessuna modifica effettuata']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}
?>