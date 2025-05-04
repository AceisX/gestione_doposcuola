<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);

    $sql = "INSERT INTO tutor (nome, cognome, email, telefono) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $cognome, $email, $telefono);

    if ($stmt->execute()) {
        header("Location: ../pages/gestione_lezioni.php?success=1");
    } else {
        header("Location: ../pages/gestione_lezioni.php?error=1");
    }

    $stmt->close();
}
?>