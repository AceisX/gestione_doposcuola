<?php
include 'db_config.php';

// Endpoint per aggiungere un pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alunno_id = $_POST['alunno_id'];
    $data_pagamento = $_POST['data_pagamento'];
    $mese_pagamento = $_POST['mese_pagamento'];
    $metodo_pagamento = $_POST['metodo_pagamento'];
    $importo = $_POST['importo'];
    $tipo_pagamento = $_POST['tipo_pagamento'];
    $note = $_POST['note'];

    $sql = "INSERT INTO Pagamenti (alunno_id, data_pagamento, mese_pagamento, metodo_pagamento, importo, tipo_pagamento, note)
            VALUES ('$alunno_id', '$data_pagamento', '$mese_pagamento', '$metodo_pagamento', '$importo', '$tipo_pagamento', '$note')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
}
?>