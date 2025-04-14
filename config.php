<?php
// Configurazione database
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Utente predefinito di XAMPP
define('DB_PASSWORD', ''); // Password predefinita di XAMPP è vuota
define('DB_NAME', 'gestione_doposcuola'); // Sostituisci con il nome del tuo database

// Connessione al database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Verifica connessione
if ($conn->connect_error) {
    die('Connessione al database fallita: ' . $conn->connect_error);
}

// Configurazioni aggiuntive (se necessarie)
// Esempio: definire timezone
date_default_timezone_set('Europe/Rome');
?>