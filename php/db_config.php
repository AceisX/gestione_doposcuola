<?php
// Configurazione del database

$host = "localhost";
$username = "root";
$password = "";
$database = "gestione_doposcuola";

$conn = new mysqli($host, $username, $password, $database);

// Controllo connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
?>