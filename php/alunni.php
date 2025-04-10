<?php
include 'db_config.php';

// Endpoint per ottenere tutti gli alunni
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM Alunni";
    $result = $conn->query($sql);

    $alunni = [];
    while ($row = $result->fetch_assoc()) {
        $alunni[] = $row;
    }

    echo json_encode($alunni);
}
?>