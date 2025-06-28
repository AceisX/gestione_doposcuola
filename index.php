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


// Inizializza le variabili per le statistiche
$totalAlunni = 0;
$alunniAttivi = 0;
$pagamentiMese = 0;
$oreTotaliMese = 0;

// Query per le statistiche della dashboard
try {

  

    // Query per il totale degli alunni
    $queryTotaleAlunni = "SELECT COUNT(*) as totale FROM alunni";
    $resultTotale = $conn->query($queryTotaleAlunni);
    if ($resultTotale && $row = $resultTotale->fetch_assoc()) {
        $totalAlunni = $row['totale'];
    }

    // Query per gli alunni attivi
    $queryAlunniAttivi = "SELECT COUNT(*) as attivi FROM alunni WHERE stato = 'attivo'";
    $resultAttivi = $conn->query($queryAlunniAttivi);
    if ($resultAttivi && $row = $resultAttivi->fetch_assoc()) {
        $alunniAttivi = $row['attivi'];
    }

    // Query per i pagamenti del mese
    $queryPagamentiMese = "SELECT COALESCE(SUM(totale_pagato), 0) as totale FROM pagamenti 
                          WHERE MONTH(data_pagamento) = MONTH(CURRENT_DATE)
                          AND YEAR(data_pagamento) = YEAR(CURRENT_DATE)";
    $resultPagamenti = $conn->query($queryPagamentiMese);
    if ($resultPagamenti && $row = $resultPagamenti->fetch_assoc()) {
        $pagamentiMese = $row['totale'];
    }

    // Query per le ore totali del mese
    $queryOreTotaliMese = "SELECT COALESCE(SUM(ore_effettuate), 0) as totale FROM pagamenti 
                          WHERE MONTH(data_pagamento) = MONTH(CURRENT_DATE)
                          AND YEAR(data_pagamento) = YEAR(CURRENT_DATE)";
    $resultOre = $conn->query($queryOreTotaliMese);
    if ($resultOre && $row = $resultOre->fetch_assoc()) {
        $oreTotaliMese = $row['totale'];
    }
} catch (Exception $e) {
    // Log dell'errore o gestione dell'eccezione
    error_log("Errore nelle query delle statistiche: " . $e->getMessage());
}



// Variabili per la tabella alunni (placeholder per test)


$pacchetti = [];
$sql = "SELECT id, nome FROM pacchetti";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $pacchetti[] = $row;
    }
}

function getMeseNome($numero) {
    $mesi = [
        1 => 'GENNAIO',
        2 => 'FEBBRAIO',
        3 => 'MARZO',
        4 => 'APRILE',
        5 => 'MAGGIO',
        6 => 'GIUGNO',
        7 => 'LUGLIO',
        8 => 'AGOSTO',
        9 => 'SETTEMBRE',
        10 => 'OTTOBRE',
        11 => 'NOVEMBRE',
        12 => 'DICEMBRE'
    ];
    return $mesi[$numero] ?? '';
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
	<!-- our project just needs Font Awesome Solid + Brands -->
  <link href="assets/fontawesome/css/fontawesome.css" rel="stylesheet" />
  <link href="assets/fontawesome/css/brands.css" rel="stylesheet" />
  <link href="assets/fontawesome/css/solid.css" rel="stylesheet" />
  <link href="assets/fontawesome/css/sharp-thin.css" rel="stylesheet" />
  <link href="assets/fontawesome/css/duotone-thin.css" rel="stylesheet" />
  <link href="assets/fontawesome/css/sharp-duotone-thin.css" rel="stylesheet" />
</head>
<body>
    <?php include __DIR__ . '/assets/header.php'; ?>   

    <main class="container">
	
	
	
	
	<div style="text-align:center;">
        <h2>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
		
		<div class="dashboard-cards">
    <div class="dashboard-card">
        <i class="fa-solid fa-user-graduate"></i>
        <div class="card-content">
            <h4>Totale Alunni</h4>
            <p class="card-value"><?php echo $totalAlunni; ?></p>
        </div>
    </div>
    <div class="dashboard-card">
        <i class="fa-solid fa-user-check"></i>
        <div class="card-content">
            <h4>Alunni Attivi</h4>
            <p class="card-value"><?php echo $alunniAttivi; ?></p>
        </div>
    </div>
    <div class="dashboard-card">
        <i class="fa-solid fa-euro-sign"></i>
        <div class="card-content">
            <h4>Pagamenti Questo Mese</h4>
            <p class="card-value">€<?php echo number_format($pagamentiMese, 2); ?></p>
        </div>
    </div>
    <div class="dashboard-card">
        <i class="fa-solid fa-clock"></i>
        <div class="card-content">
            <h4>Ore Totali Mese</h4>
            <p class="card-value"><?php echo $oreTotaliMese; ?></p>
        </div>
    </div>
</div>
		
        
	</div>	
        <section class="student-table">
<div class="table-controls">
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Cerca alunno...">
        <i class="fa-solid fa-search"></i>
    </div>
    <div class="filters">
        <select id="filterStato">
            <option value="">Tutti gli stati</option>
            <option value="attivo">Attivi</option>
            <option value="disattivato">Disattivati</option>
        </select>
        <select id="filterPacchetto">
            <option value="">Tutti i pacchetti</option>
            <?php foreach ($pacchetti as $pacchetto): ?>
                <option value="<?php echo htmlspecialchars($pacchetto['nome']); ?>">
                    <?php echo htmlspecialchars($pacchetto['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
	  <div class="view-controls">
                <button class="view-btn active" data-view="table">
                    <i class="fa-solid fa-table"></i> Vista Tabella
                </button>
                <button class="view-btn" data-view="payments">
                    <i class="fa-solid fa-calendar-days"></i> Vista Pagamenti
                </button>
            </div>
</div>
			<div class="actions">
            <button id="add-student-btn" style="width:80px; border-radius: 15px;"><i class="fa-solid fa-plus" style="color: #ffffff;"></i></i></button>
            <button id="generate-report-btn" style="width:80px; border-radius: 15px;" onclick="openReportModal()"><i class="fa-solid fa-file-export" style="color: #ffffff;"></i></button>
        </div>
             <!-- Tabella principale -->
			 <div id="tableView">
        <table class="alunni-table">
    <thead>
        <tr>
            <th data-type="string">Nome Alunno</th>
            <th data-type="string">Scuola</th>
            <th data-type="string">Pacchetto</th>
            <th data-type="number">Prezzo Pagato</th>
            <th data-type="status">Stato</th>
            <th data-type="date">Data Iscrizione</th>
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
                                <span class="status <?php echo $row['stato'] === 'attivo' ? 'active' : 'inactive'; ?>"></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['data_iscrizione']); ?></td>
                            <td>
                                <button class="info-btn" data-id="<?php echo $row['alunno_id']; ?>"><i class="fa-solid fa-circle-info" style="color: #ffffff;"></i></button>
                                <button class="edit-btn" data-id="<?php echo $row['alunno_id']; ?>"><i class="fa-solid fa-pen" style="color: #ffffff;"></i></button>
                                <?php if ($row['stato'] === 'attivo'): ?>
								<button class="pagamento-btn" data-id="<?php echo $row['alunno_id']; ?>"><i class="fa-solid fa-euro-sign" style="color: #ffffff;"></i></button>
								<?php endif; ?>
                                <button class="delete-btn" data-id="<?php echo $row['alunno_id']; ?>"><i class="fa-solid fa-trash-can" style="color: #ffffff;"></i></button>
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
		</div>
		<?php
			// Impostazione del fuso orario
			date_default_timezone_set('Europe/Rome');

			// Definizione delle variabili per la data corrente
			$currentYear = (int)date('Y');
			$currentMonth = (int)date('n');

			// Query per trovare l'ultimo mese con pagamenti
			$queryUltimoMese = "SELECT 
				MAX(CASE mese_pagato
					WHEN 'GENNAIO' THEN 1
					WHEN 'FEBBRAIO' THEN 2
					WHEN 'MARZO' THEN 3
					WHEN 'APRILE' THEN 4
					WHEN 'MAGGIO' THEN 5
					WHEN 'GIUGNO' THEN 6
					WHEN 'LUGLIO' THEN 7
					WHEN 'AGOSTO' THEN 8
					WHEN 'SETTEMBRE' THEN 9
					WHEN 'OTTOBRE' THEN 10
					WHEN 'NOVEMBRE' THEN 11
					WHEN 'DICEMBRE' THEN 12
				END) as ultimo_mese,
				YEAR(data_pagamento) as anno
			FROM pagamenti 
			WHERE YEAR(data_pagamento) = ?";

			$stmtUltimoMese = $conn->prepare($queryUltimoMese);
			$stmtUltimoMese->bind_param("i", $currentYear);
			$stmtUltimoMese->execute();
			$risultatoUltimoMese = $stmtUltimoMese->get_result()->fetch_assoc();

			// Determina l'ultimo mese da visualizzare (il più grande tra mese corrente e ultimo mese con pagamenti)
			$ultimoMese = max($currentMonth, $risultatoUltimoMese['ultimo_mese'] ?? $currentMonth);

			// Debug delle variabili temporali
			echo "<!-- Debug - Date Variables: 
				Current Year: $currentYear
				Current Month: $currentMonth
				Ultimo Mese: $ultimoMese
			-->\n";
		?>
		 <!-- Nuova tabella pagamenti -->
   <div id="paymentsView" style="display: none;">
    <div class="payments-table-wrapper">
        <table class="payments-table">
            <thead>
                <thead>
					<tr>
						<th class="fixed-column">Alunno</th>
						<?php
						for ($month = 1; $month <= $ultimoMese; $month++) {
							echo "<th>" . getMeseNome($month) . "</th>";
						}
						?>
					</tr>
				</thead>
            <tbody>
			<?php
			$queryAlunni = "SELECT 
						a.id,
						CONCAT(a.nome, ' ', a.cognome) AS nome_completo,
						a.id_pacchetto,
					a.prezzo_finale, 
				p.nome AS tipo_pacchetto,
				p.tipo AS modalita_pacchetto,
				p.prezzo AS prezzo_pacchetto
			FROM alunni a
			LEFT JOIN pacchetti p ON a.id_pacchetto = p.id
			WHERE a.stato = 'attivo'
			ORDER BY nome_completo";

$resultAlunni = $conn->query($queryAlunni);

if ($resultAlunni && $resultAlunni->num_rows > 0):
    while ($alunno = $resultAlunni->fetch_assoc()):
        echo "<tr>";
		   echo "<td class='fixed-column'>" . 
		 htmlspecialchars($alunno['nome_completo']) . 
		 " <button style='font-size: 0.8rem!important; background-color:white!important;' class='pagamento-btn' data-id='" . $alunno['id'] . "' " .
		 "data-tipo-pacchetto='" . htmlspecialchars($alunno['modalita_pacchetto']) . "'>" .
		 "<i class='fa-solid fa-euro-sign' style='color: #28a745;'></i></button></td>";
        
        for ($month = 1; $month <= $ultimoMese; $month++) {
            $queryPagamenti = "SELECT 
                CASE 
                    WHEN EXISTS (
                        SELECT 1 
                        FROM pagamenti 
                        WHERE id_alunno = ? 
                        AND mese_pagato = ? 
                        AND YEAR(data_pagamento) = ? 
                        AND tipologia = 'saldo'
                    ) 
                    THEN 'saldo'
                    ELSE 'acconto'
                END as tipologia,
                SUM(totale_pagato) as totale_pagato
            FROM pagamenti 
            WHERE id_alunno = ? 
            AND mese_pagato = ? 
            AND YEAR(data_pagamento) = ?
            GROUP BY mese_pagato, YEAR(data_pagamento)";
            
            $meseNome = getMeseNome($month);

            $stmt = $conn->prepare($queryPagamenti);
            $stmt->bind_param("ssissi", 
                $alunno['id'], $meseNome, $currentYear,
                $alunno['id'], $meseNome, $currentYear
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $pagamento = $result->fetch_assoc();

            $cellClass = '';
            $cellContent = '-';
            
            if ($pagamento) {
                $condizioneSaldo = strtolower($pagamento['tipologia']) === 'saldo';
                $condizioneOrario = $alunno['modalita_pacchetto'] === 'orario' && strtolower($pagamento['tipologia']) === 'acconto';
                if ($condizioneSaldo || $condizioneOrario) {
                    $cellClass = 'paid-full';
                } elseif (strtolower($pagamento['tipologia']) === 'acconto') {
                    $cellClass = 'paid-partial';
                }
                $cellContent = "€" . number_format($pagamento['totale_pagato'], 2);
                
                // Aggiungi attributi per il modale
							echo "<td class='$cellClass payment-cell' 
					 data-student-id='" . $alunno['id'] . "' 
					 data-student-name='" . htmlspecialchars($alunno['nome_completo']) . "' 
					 data-month='" . $month . "' 
					 data-year='" . $currentYear . "'
					 data-tipo-pacchetto='" . htmlspecialchars($alunno['modalita_pacchetto']) . "'>"; // Aggiunta questa riga
						echo $cellContent;
						echo "</td>";
            } else {
					if ($month == $currentMonth && $alunno['modalita_pacchetto'] === 'mensile') {
						$cellContent = "€" . number_format($alunno['prezzo_finale'], 2); // Modificata questa riga
					} elseif ($alunno['modalita_pacchetto'] === 'orario') {
						$cellContent = "-";
					}
					echo "<td class='$cellClass'>";
					echo $cellContent;
					echo "</td>";
				}

            $stmt->close();
        }
        echo "</tr>";
    endwhile;
else:
    echo "<tr><td class='fixed-column'>Nessun alunno trovato</td></tr>";
endif;
?>

                </tbody>
            </table>
        </div>
    </div>
		
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
            <div class="modal-grid">
                <!-- Colonna Info Alunno -->
                <div class="modal-column">
                    <h4>Dati Alunno</h4>
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
                        <label for="data_iscrizione"><i class="fas fa-calendar-alt"></i>  Data Iscrizione:</label>
                        <div class="input-with-icon">
                            <input type="date" name="data_iscrizione" id="data_iscrizione" value="<?php echo date('Y-m-d'); ?>" required>
                         
                        </div>
                    </div>
                </div>

                <!-- Colonna Info Genitore -->
                <div class="modal-column">
                    <h4>Dati Genitore</h4>
                    <div class="form-group">
                        <label for="nome_genitore">Nome Completo:</label>
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
                </div>
            </div>
            <!-- Pulsante di invio -->
            <div class="form-group form-submit">
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
            <div class="modal-grid">
                <!-- Colonna Info Alunno -->
                <div class="modal-column">
                    <h4>Dati Alunno</h4>
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
                        <label for="prezzo_finale">Prezzo Finale:</label>
                        <div class="input-with-icon">
                            <input type="text" name="prezzo_finale" id="prezzo_finale" placeholder="">
                            
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-stato">Stato:</label>
                        <select name="stato" id="edit-stato" required>
                            <option value="attivo">Attivo</option>
                            <option value="disattivato">Disattivato</option>
                        </select>
                    </div>
                </div>

                <!-- Colonna Info Genitore -->
                <div class="modal-column">
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
                </div>
            </div>

            <!-- Pulsante di invio -->
            <div class="form-group form-submit">
                <button type="submit">Salva Modifiche</button>
            </div>
        </form>
    </div>
</div>

<!-- Modale pagamenti -->
<div id="pagamentoModale" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Registra Pagamento</h2>
        <h3 id="student-name-display"></h3>
        <form id="pagamento-form" action="scripts/registra_pagamento.php" method="POST">
            <input type="hidden" name="alunno_id" id="pagamento-alunno-id">
			<input type="hidden" id="pagamento-pacchetto-id" name="id_pacchetto">

            <div class="modal-grid">
                <!-- Colonna Sinistra -->
                <div class="modal-column">
                    <div class="form-group">
                        <label for="data-pagamento"><i class="fas fa-calendar-alt"></i>  Data Pagamento:</label>
                        <div class="input-with-icon">
                            <input type="date" name="data_pagamento" id="data-pagamento" required>
                            
                        </div>
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
                        <input type="number" step="0.01" name="totale_pagato" id="totale-pagato" required>
                    </div>
                </div>

                <!-- Colonna Destra -->
                <div class="modal-column">
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
    <label for="mese-pacchetto">Mese:</label>
    <select name="mese_pacchetto" id="mese-pacchetto" required>
        <option value="GENNAIO">Gennaio</option>
        <option value="FEBBRAIO">Febbraio</option>
        <option value="MARZO">Marzo</option>
        <option value="APRILE">Aprile</option>
        <option value="MAGGIO">Maggio</option>
        <option value="GIUGNO">Giugno</option>
        <option value="LUGLIO">Luglio</option>
        <option value="AGOSTO">Agosto</option>
        <option value="SETTEMBRE">Settembre</option>
        <option value="OTTOBRE">Ottobre</option>
        <option value="NOVEMBRE">Novembre</option>
        <option value="DICEMBRE">Dicembre</option>
    </select>
</div>
<div class="form-group">
    <label for="ore-eff">Ore Effettuate:</label>
    <input type="text" name="ore-eff" id="ore-eff" required>
</div>
                </div>
            </div>

            <!-- Pulsante di Invio -->
            <div class="form-group form-submit">
                <button type="submit">Registra Pagamento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modale Dettagli Pagamenti -->
<div id="payment-details-modal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closePaymentModal()">&times;</span>
        <h2>Dettagli Pagamenti</h2>
        <h3 id="modal-student-name"></h3>
        <div id="payment-details-content" class="info-table-container">
            <!-- I pagamenti verranno inseriti qui -->
        </div>
    </div>
</div>
	<script src="scripts/sorting.js"></script>
	<script src="scripts/scripts.js"></script>
    <!-- Script per gestire la modale -->
    <script>
	//gestione delle viste
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.view-btn');
    const tableView = document.getElementById('tableView');
    const paymentsView = document.getElementById('paymentsView');

    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Rimuove la classe active da tutti i bottoni
            viewButtons.forEach(btn => btn.classList.remove('active'));
            // Aggiunge la classe active al bottone cliccato
            this.classList.add('active');

            // Gestisce la visualizzazione delle viste
            if (this.dataset.view === 'table') {
                tableView.style.display = 'block';
                paymentsView.style.display = 'none';
            } else {
                tableView.style.display = 'none';
                paymentsView.style.display = 'block';
            }
        });
    });
});

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