<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Non autorizzato']));
}

// Ottieni tutti gli alunni attivi
$sql = "SELECT id, CONCAT(nome, ' ', cognome) AS nome 
        FROM alunni 
        WHERE stato = 'attivo'
        ORDER BY nome, cognome";

$result = $conn->query($sql);

if (!$result) {
    die(json_encode(['success' => false, 'message' => 'Errore nel database']));
}

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode(['success' => true, 'students' => $students]);
?>