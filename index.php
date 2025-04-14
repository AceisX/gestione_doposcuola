<?php
// Includiamo il file di configurazione
require_once 'config.php';

// Inizializziamo la sessione
session_start();

// Verifichiamo se l'utente è loggato
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: pages/login.php');
    exit;
}

// Variabili per la tabella alunni (placeholder per test)


$pacchetti = [];
$sql = "SELECT id, nome FROM pacchetti";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $pacchetti[] = $row;
    }
}

$sql = "SELECT 
            alunni.id AS alunno_id,
            CONCAT(alunni.nome, ' ', alunni.cognome) AS nome_completo,
            alunni.scuola,
            pacchetti.nome AS pacchetto,
            alunni.prezzo_finale,
            alunni.stato,
            alunni.data_iscrizione
        FROM 
            alunni
        LEFT JOIN 
            pacchetti ON alunni.id_pacchetto = pacchetti.id
        ORDER BY 
            alunni.data_iscrizione DESC";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Doposcuola - Homepage</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header>
        <h1>Gestione Doposcuola</h1>
        <a href="scripts/logout.php">Logout</a>
    </header>
    <main class="container">
        <h2>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <div class="actions">
            <button id="add-student-btn">Iscrizione Alunno</button>
            <button id="generate-report-btn" onclick="openReportModal()">Genera Report Mensile</button>
        </div>
        <section class="student-table">
            <h3>Lista Alunni</h3>
             <!-- Tabella principale -->
        <table class="alunni-table">
            <thead>
                <tr>
                    <th>Nome Alunno</th>
                    <th>Scuola</th>
                    <th>Pacchetto</th>
                    <th>Prezzo Pagato</th>
                    <th>Stato</th>
                    <th>Data Iscrizione</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                            <td><?php echo htmlspecialchars($row['scuola']); ?></td>
                            <td><?php echo htmlspecialchars($row['pacchetto']); ?></td>
                            <td>€<?php echo htmlspecialchars(number_format($row['prezzo_finale'], 2)); ?></td>
                            <td>
								<span class="status <?php echo $row['stato'] === 'attivo' ? 'active' : 'inactive'; ?>">
																														●
								</span>
							</td>
                            <td><?php echo htmlspecialchars($row['data_iscrizione']); ?></td>
                            <td>
                                <button class="info-btn" data-id="<?php echo $row['alunno_id']; ?>">INFO</button>
                                <button class="edit-btn" data-id="<?php echo $row['alunno_id']; ?>">EDIT</button>
                                <button class="pagamento-btn" data-id="<?php echo $row['alunno_id']; ?>">PAGAMENTO</button>
                                <button class="delete-btn" data-id="<?php echo $row['alunno_id']; ?>">ELIMINA</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Nessun alunno trovato.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </section>
    </main>
	
	<!-- Modale per selezionare il mese -->
<div id="report-modal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeReportModal()">&times;</span>
        <h2>Genera Report Mensile</h2>
        <form action="scripts/generate_report.php" method="GET">
            <div class="form-group">
                <label for="mese">Seleziona il mese:</label>
                <input type="month" id="mese" name="mese" required>
            </div>
            <button type="submit" class="generate-btn">Genera Report</button>
        </form>
    </div>
</div>

    <!-- Modale Iscrizione Alunno -->
<div id="add-student-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-modal">&times;</span>
        <h3>Iscrizione Alunno</h3>
        <form action="scripts/add_student.php" method="POST">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div class="form-group">
                <label for="cognome">Cognome:</label>
                <input type="text" name="cognome" id="cognome" required>
            </div>
            <div class="form-group">
                <label for="scuola">Scuola:</label>
                <input type="text" name="scuola" id="scuola" required>
            </div>
            <div class="form-group">
                <label for="id_pacchetto">Pacchetto:</label>
                <select name="id_pacchetto" id="id_pacchetto" required>
                    <?php foreach ($pacchetti as $pacchetto): ?>
                        <option value="<?php echo htmlspecialchars($pacchetto['id']); ?>">
                            <?php echo htmlspecialchars($pacchetto['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sconto">Sconto (€ o %):</label>
                <input type="text" name="sconto" id="sconto" placeholder="Esempio: 10 o 10%">
            </div>
            <div class="form-group">
                <label for="data_iscrizione">Data Iscrizione:</label>
                <input type="date" name="data_iscrizione" id="data_iscrizione" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <h4>Dati Genitore</h4>
            <div class="form-group">
                <label for="nome_genitore">Nome Completo Genitore:</label>
                <input type="text" name="nome_genitore" id="nome_genitore" required>
            </div>
            <div class="form-group">
                <label for="residenza">Residenza:</label>
                <input type="text" name="residenza" id="residenza" required>
            </div>
            <div class="form-group">
                <label for="codice_fiscale">Codice Fiscale:</label>
                <input type="text" name="codice_fiscale" id="codice_fiscale" pattern="[A-Za-z0-9]{16}" title="Inserisci un codice fiscale valido di 16 caratteri" required>
            </div>
            <div class="form-group">
                <label for="telefono">Numero di Telefono:</label>
                <input type="text" name="telefono" id="telefono" pattern="[0-9]{10}" title="Inserisci un numero di telefono valido (10 cifre)" required>
            </div>
            <div class="form-group">
                <button type="submit">Registra Alunno</button>
            </div>
        </form>
    </div>
</div>

<!-- Modale INFO -->
<div id="info-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" onclick="closeInfoModal()">&times;</span>
        <h2>Dettagli Alunno</h2>
        <div id="info-content">
            <!-- I dettagli saranno caricati dinamicamente -->
            <p>Caricamento...</p>
        </div>
    </div>
</div>

<!-- Modale EDIT -->
<div id="edit-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" onclick="closeEditModal()">&times;</span>
        <h2>Modifica Alunno</h2>
        <form id="edit-form">
            <input type="hidden" name="alunno_id" id="edit-alunno-id">
            <div class="form-group">
                <label for="edit-nome">Nome:</label>
                <input type="text" name="nome" id="edit-nome" required>
            </div>
            <div class="form-group">
                <label for="edit-cognome">Cognome:</label>
                <input type="text" name="cognome" id="edit-cognome" required>
            </div>
            <div class="form-group">
                <label for="edit-scuola">Scuola:</label>
                <input type="text" name="scuola" id="edit-scuola" required>
            </div>
            <div class="form-group">
                <label for="edit-id-pacchetto">Pacchetto:</label>
                <select name="id_pacchetto" id="edit-id-pacchetto" required>
                    <?php foreach ($pacchetti as $pacchetto): ?>
                        <option value="<?php echo htmlspecialchars($pacchetto['id']); ?>">
                            <?php echo htmlspecialchars($pacchetto['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit-stato">Stato:</label>
                <select name="stato" id="edit-stato" required>
                    <option value="attivo">Attivo</option>
                    <option value="disattivato">Disattivato</option>
                </select>
            </div>
            <h4>Dati Genitore</h4>
            <div class="form-group">
                <label for="edit-nome-genitore">Nome Completo Genitore:</label>
                <input type="text" name="nome_genitore" id="edit-nome-genitore" required>
            </div>
            <div class="form-group">
                <label for="edit-residenza">Residenza:</label>
                <input type="text" name="residenza" id="edit-residenza" required>
            </div>
            <div class="form-group">
                <label for="edit-codice-fiscale">Codice Fiscale:</label>
                <input type="text" name="codice_fiscale" id="edit-codice-fiscale" pattern="[A-Za-z0-9]{16}" required>
            </div>
            <div class="form-group">
                <label for="edit-telefono">Numero di Telefono:</label>
                <input type="text" name="telefono" id="edit-telefono" pattern="[0-9]{10}" required>
            </div>
            <div class="form-group">
                <button type="submit">Salva Modifiche</button>
            </div>
        </form>
    </div>
</div>


<div id="pagamentoModale" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Registra Pagamento</h2>
        <form id="pagamento-form" action="scripts/registra_pagamento.php" method="POST">
            <input type="hidden" name="alunno_id" id="pagamento-alunno-id">
            
            <div class="form-group">
                <label for="data-pagamento">Data Pagamento:</label>
                <input type="date" name="data_pagamento" id="data-pagamento" required>
            </div>
            
            <div class="form-group">
                <label for="metodo-pagamento">Metodo di Pagamento:</label>
                <select name="metodo_pagamento" id="metodo-pagamento" required>
                    <option value="Contanti">Contanti</option>
                    <option value="Bonifico">Bonifico</option>
                    <option value="Carta">Carta</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="totale-pagato">Totale Pagato:</label>
                <input type="number" name="totale_pagato" id="totale-pagato" required>
            </div>
            
            <div class="form-group">
                <label for="tipologia">Tipologia:</label>
                <div>
                    <input type="radio" name="tipologia" id="tipologia-acconto" value="Acconto" required>
                    <label for="tipologia-acconto">Acconto</label>
                    
                    <input type="radio" name="tipologia" id="tipologia-saldo" value="Saldo" required>
                    <label for="tipologia-saldo">Saldo</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="mese-pacchetto">Mese o Pacchetto Pagato:</label>
                <input type="text" name="mese_pacchetto" id="mese-pacchetto" required>
            </div>
			
			<div class="form-group">
                <label for="ore-eff">Ore effettuare:</label>
                <input type="text" name="ore-eff" id="ore-eff" required>
            </div>
            
            <div class="form-group">
                <button type="submit">Registra Pagamento</button>
            </div>
        </form>
    </div>
</div>

	<script src="scripts/scripts.js"></script>
    <!-- Script per gestire la modale -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addStudentBtn = document.getElementById('add-student-btn');
            const modal = document.getElementById('add-student-modal');
            const closeModal = document.getElementById('close-modal');

            // Evento per aprire la modale
            addStudentBtn.addEventListener('click', () => {
                modal.style.display = 'block';
            });

            // Evento per chiudere la modale
            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            // Chiudere la modale cliccando fuori
            window.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>