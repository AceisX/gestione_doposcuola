<?php
require_once '../config.php';

// Ottieni i tutor dal database
$tutors = [];
$sql = "SELECT id, CONCAT(nome, ' ', cognome) AS nome_completo, email, telefono FROM tutor ORDER BY nome ASC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $tutors[] = $row;
    }
}

// Ottieni gli alunni dal database
$alunni = [];
$sql = "SELECT id, CONCAT(nome, ' ', cognome) AS nome_completo FROM alunni ORDER BY nome ASC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $alunni[] = $row;
    }
}

$slots_mapping = [
    1 => "15:30-16:30",
    2 => "16:30-17:30",
    3 => "17:30-18:30",
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Tutor e Lezioni</title>
    <link rel="stylesheet" href="../assets/styles.css">
		<!-- our project just needs Font Awesome Solid + Brands -->
   <link href="../assets/fontawesome/css/fontawesome.css" rel="stylesheet" />
  <link href="../assets/fontawesome/css/brands.css" rel="stylesheet" />
  <link href="../assets/fontawesome/css/solid.css" rel="stylesheet" />
 
</head>
<style>
/* Stile per il modale INFO */
#info-modal {
    z-index: 1050; /* Deve essere più alto rispetto agli altri modali */
}

.info-btn{
	  background-color: transparent !important;
  color: #007bff !important;

	
}
</style>
<body>
<button id="add-lesson-fixed-btn" class="fixed-btn">+</button>


   	<header>
		<img src="../img/logo.png" alt="Your Logo" height="75px" style="padding-left: 20px;" />
        <h1>Gestione Tutor e Lezioni</h1>
        <a href="scripts/logout.php">Logout</a>
    </header>
    <div class="container">

        <!-- Sezione superiore -->
     <div class="top-bar" style="text-align:center;">
		<div class="actions">
        <button id="add-tutor-btn" class="btn">Aggiungi Tutor</button>
		<button type="button" id="report-alunno-btn" class="btn">Report Alunno</button>

		</div>
	</div>

		
  <!--- Modale Aggiungi lezione -->
<div id="add-lesson-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-add-lesson">&times;</span>
        <h2>Aggiungi Lezione</h2>
        
        <form action="../scripts/aggiungi_lezione.php" method="POST" id="lesson-form">
            <div class="modal-grid-rows">
                <!-- Riga 1: Data e Tutor -->
                <div class="row" style="padding-top:20px;">
                    <div class="form-group">
                        <label for="lesson-date"><i class="fas fa-calendar-alt"></i> Data Lezione:</label>
                        <div class="input-with-icon">
                            <input type="date" id="lesson-date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
							
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="tutor-select"><i class="fas fa-user"></i> Seleziona Tutor:</label>
                        <select id="tutor-select" name="tutor_id" required>
                            <option value="" disabled selected>Seleziona un Tutor</option>
                            <?php foreach ($tutors as $tutor): ?>
                                <option value="<?php echo $tutor['id']; ?>"><?php echo $tutor['nome_completo']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Riga 2: Slot Orari -->
				  <h4>Slot Orari</h4>
                <div class="row">
                  
                    <?php foreach ($slots_mapping as $slot => $orario): ?>
                        <div class="slot-group" data-slot="<?php echo $slot; ?>">
                            <div class="slot-header">
                                <label><strong><?php echo $orario; ?></strong></label>
                                <button type="button" class="add-student-btn" data-slot="<?php echo $slot; ?>">
                                   +
                                </button>
                            </div>
                            <div class="slot-body">
                                <input type="checkbox" name="half_lesson[<?php echo $slot; ?>]" id="half-lesson-<?php echo $slot; ?>">
                                <label for="half-lesson-<?php echo $slot; ?>">Mezza Lezione</label>
                                <div class="selected-students"></div>
                                <!-- Campo nascosto per gli ID degli alunni -->
                                <input type="hidden" name="students[<?php echo $slot; ?>]" class="student-ids">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pulsante di Invio -->
            <div class="form-group form-submit">
                <button type="submit" class="btn">Salva Lezione</button>
            </div>
        </form>
    </div>
</div>

    <!-- Modale per selezionare gli studenti -->
<div id="student-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-student-modal">&times;</span>
        <h2>Seleziona Alunni</h2>
        <div class="form-group">
            <label for="student-search">Cerca Alunni:</label>
            <input type="text" id="student-search" placeholder="Cerca alunni..." onkeyup="filterStudents()">
        </div>
        <form id="student-selection-form">
            <div id="student-list" class="student-list">
                <?php foreach ($alunni as $alunno): ?>
                    <div class="student-item">
                        <input type="checkbox" class="student-checkbox" value="<?php echo $alunno['id']; ?>">
                        <label><?php echo $alunno['nome_completo']; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-submit">
                <button type="button" id="save-students" class="btn">Salva</button>
            </div>
        </form>
    </div>
</div>
	
	<!-- Modale per aggiungere un tutor -->
<div id="add-tutor-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-add-tutor">&times;</span>
        <h2>Aggiungi Tutor</h2>
        <form action="../scripts/aggiungi_tutor.php" method="POST">
            <div class="form-group">
                <label for="tutor-nome">Nome:</label>
                <input type="text" id="tutor-nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="tutor-cognome">Cognome:</label>
                <input type="text" id="tutor-cognome" name="cognome" required>
            </div>
            <div class="form-group">
                <label for="tutor-email">Email:</label>
                <input type="email" id="tutor-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="tutor-telefono">Telefono:</label>
                <input type="text" id="tutor-telefono" name="telefono" required>
            </div>
            <button type="submit" class="btn">Salva Tutor</button>
        </form>
    </div>
</div>

<!-- Modale per la Ricerca degli Alunni -->
<div id="report-alunno-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-report-modal">&times;</span>
        <h2>Genera Report Alunno</h2>
        <div class="form-group">
            <label for="alunno-search">Cerca Alunni:</label>
            <input type="text" id="alunno-search" placeholder="Cerca alunni..." onkeyup="filterAlunni()">
        </div>
        <form id="alunno-selection-form">
            <div id="alunno-list" class="student-list">
                <?php foreach ($alunni as $alunno): ?>
                    <div class="alunno-item">
                        <input type="checkbox" class="alunno-checkbox" value="<?php echo $alunno['id']; ?>">
                        <label><?php echo $alunno['nome_completo']; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label for="mese-report" style="padding-top: 20px;">Mese:</label>
                <input type="month" id="mese-report" name="mese" required>
            </div>
            <div class="form-submit">
                <button type="button" id="generate-report_a" class="btn">Genera Report</button>
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

<!-- Modale per Visualizzare il Report -->
<div id="report-result-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-result-modal">&times;</span>
        <h2>Risultati Report</h2>
        <div id="report-content">
            <!-- Risultati del report verranno caricati dinamicamente -->
        </div>
        <div class="form-submit">
            <a href="#" id="download-report" class="btn">Scarica Completo</a>
        </div>
    </div>
</div>
	
	<?php include 'calendar_view.php'; ?>
	
	<script src="../scripts/scripts_lezioni.js"></script>

</body>
</html>