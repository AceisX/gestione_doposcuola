<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Non autorizzato']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['date'];
    $id_tutor = intval($_POST['tutor_id']);
    $slot = intval($_POST['slot']);
    $students = $_POST['students'] ?? [];
    $half_lesson = isset($_POST['half_lesson']) ? 1 : 0;
    
    // Mappa slot agli orari
    $slot_mapping = [
        1 => '15:30-16:30',
        2 => '16:30-17:30',
        3 => '17:30-18:30'
    ];
    
    $slot_orario = $slot_mapping[$slot];
    
    if (empty($students)) {
        die(json_encode(['success' => false, 'message' => 'Seleziona almeno un alunno']));
    }
    
    $conn->begin_transaction();
    
    try {
        // Verifica se esiste già una lezione per questo tutor/slot/data
        $check_sql = "SELECT id FROM lezioni WHERE data = ? AND id_tutor = ? AND slot_orario = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sis", $data, $id_tutor, $slot_orario);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception('Esiste già una lezione per questo slot');
        }
        
        // Determina il tipo di lezione
        $tipo = count($students) === 1 ? 'singolo' : 'gruppo';
        
        // Inserisci la lezione
        $sql = "INSERT INTO lezioni (data, id_tutor, slot_orario, durata, tipo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisis", $data, $id_tutor, $slot_orario, $half_lesson, $tipo);
        $stmt->execute();
        $id_lezione = $stmt->insert_id;
        
        // Inserisci gli alunni
        foreach ($students as $id_alunno) {
            $sql = "INSERT INTO lezioni_alunni (id_lezione, id_alunno) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id_lezione, $id_alunno);
            $stmt->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Lezione aggiunta con successo']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>