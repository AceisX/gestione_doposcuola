<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Non autorizzato']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $tutor_id = intval($_POST['tutor_id']);
    
    // Verifica se il tutor ha già lezioni in quel giorno
    $check_sql = "SELECT COUNT(*) as count FROM lezioni WHERE data = ? AND id_tutor = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $date, $tutor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Il tutor ha già lezioni in questo giorno']);
        exit;
    }
    
    // Se è solo un placeholder, ricarica la pagina senza fare nulla
    // Il calendario mostrerà automaticamente una riga vuota per poter aggiungere lezioni
    echo json_encode(['success' => true, 'message' => 'Tutor aggiunto al giorno']);
}
?>