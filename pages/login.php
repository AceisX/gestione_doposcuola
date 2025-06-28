<?php
// Includiamo il file di configurazione
require_once '../config.php';

// Inizializziamo la sessione
session_start();

// Verifichiamo se l'utente è già loggato
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {	
    // Se l'utente è già loggato, reindirizziamolo alla homepage
    header('Location: ../index.php');
    exit;
}

// Variabili per errori
$error = "";

// Gestione del form di login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Controlliamo se i campi sono stati riempiti
    if (empty($username) || empty($password)) {
        $error = "Inserisci sia username che password.";
    } else {
        // Query per verificare l'utente nel database
        $sql = "SELECT id, username, password FROM utenti WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            // Verifichiamo se l'utente esiste
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $db_username, $db_password);
                $stmt->fetch();

                // Confrontiamo la password (hashata con MD5)
                if ($db_password === md5($password)) {
                    // Password corretta, inizializziamo la sessione
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $id;
                    $_SESSION['username'] = $db_username;

                    // Reindirizziamo alla homepage
                    header('Location: ../index.php');
                    exit;
                } else {
                    $error = "Password non corretta.";
                }
            } else {
                $error = "Username non trovato.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestione Doposcuola</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h1>Accesso</h1>
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           required 
                           placeholder="Inserisci il tuo username"
                           autofocus>
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required
                           placeholder="Inserisci la tua password">
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Accedi
                </button>
            </form>
        </div>
    </div>
</body>
</html>
