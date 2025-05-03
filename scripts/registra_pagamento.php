<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log dei dati ricevuti
    error_log(print_r($_POST, true));

    // Estrai i dati dal POST
    $alunnoId = intval($_POST['alunno_id'] ?? 0);
    $dataPagamento = $_POST['data_pagamento'] ?? '';
    $metodoPagamento = $_POST['metodo_pagamento'] ?? '';
    $totalePagato = floatval($_POST['totale_pagato'] ?? 0);
    $tipologia = $_POST['tipologia'] ?? '';
    $mesePacchetto = $_POST['mese_pacchetto'] ?? '';
	$oreEffettuate = floatval($_POST['ore-eff'] ?? 0);

    // Validazione di base
    if (empty($alunnoId) || empty($dataPagamento) || empty($metodoPagamento) || empty($totalePagato) || empty($tipologia) || empty($mesePacchetto) || empty($oreEffettuate)) {
        echo json_encode(['success' => false, 'message' => 'Tutti i campi sono obbligatori.', 'debug' => $_POST]);
        exit;
    }

    // Query SQL
    $sql = "INSERT INTO pagamenti (id_alunno, data_pagamento, metodo_pagamento, totale_pagato, tipologia, mese_pagato, ore_effettuate) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Controlla errori nella preparazione della query
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Errore nella preparazione della query SQL.',
            'error' => $conn->error // Mostra l'errore del database
        ]);
        exit;
    }

    // Associa i parametri
    $stmt->bind_param("issdssd", $alunnoId, $dataPagamento, $metodoPagamento, $totalePagato, $tipologia, $mesePacchetto, $oreEffettuate);

    // Esegui la query
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pagamento registrato con successo.']);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Errore durante la registrazione del pagamento.',
            'error' => $stmt->error // Mostra l'errore della query
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
}

// Controllo condizionale
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../index.php");
    exit;
}
?>