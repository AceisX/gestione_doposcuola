<?php
// All'inizio del file, prima di tutto
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: mostra il metodo ricevuto e altre info
$debug_info = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'headers' => getallheaders()
];

// Se non è POST, mostra info di debug
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Metodo non consentito. Ricevuto: ' . $_SERVER['REQUEST_METHOD'],
        'debug' => $debug_info
    ]);
    exit;
}

require_once '../config.php';

header('Content-Type: application/json');

// Resto del codice...
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';

// Log per debug
error_log("create_tutor.php - Dati ricevuti: " . json_encode($_POST));

if (empty($nome) || empty($cognome)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Nome e cognome sono obbligatori',
        'received' => [
            'nome' => $nome,
            'cognome' => $cognome
        ]
    ]);
    exit;
}

try {
    $sql = "INSERT INTO tutor (nome, cognome, email, telefono) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Errore preparazione statement: " . $conn->error);
    }
    
    $email = empty($email) ? null : $email;
    $telefono = empty($telefono) ? null : $telefono;
    
    $stmt->bind_param("ssss", $nome, $cognome, $email, $telefono);
    
    if ($stmt->execute()) {
        $tutor_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tutor aggiunto con successo',
            'tutor_id' => $tutor_id
        ]);
    } else {
        throw new Exception("Errore esecuzione query: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>