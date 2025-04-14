<?php
// Avvia la sessione
session_start();

// Distruggi tutte le variabili di sessione
$_SESSION = [];

// Distruggi la sessione
session_destroy();

// Reindirizza l'utente alla pagina di login
header("Location: ../pages/login.php");
exit;
?>