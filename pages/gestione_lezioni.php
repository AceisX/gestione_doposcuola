<?php
require_once '../config.php';

// Inizializziamo la sessione
session_start();

// Verifichiamo se l'utente è loggato
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: pages/login.php');
    exit;
}


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
/* Stile per tutti i modali - z-index alto per apparire sopra il calendario */
.modal {
    display: none;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0,0,0,0.5) !important;
}

.modal-content {
    position: relative !important;
    background: white !important;
    margin: 15px auto !important;
    padding: 20px !important;
    border-radius: 8px !important;
    max-width: 800px !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
}

/* Z-index hierarchy */
#calendar-container {
    z-index: 1 !important;
}

#add-lesson-modal {
    z-index: 100002 !important;
}

#student-modal {
    z-index: 100003 !important;
}

#add-tutor-modal {
    z-index: 100004 !important;
}

#report-alunno-modal {
    z-index: 100005 !important;
}

#report-result-modal {
    z-index: 100006 !important;
}

#info-modal {
    z-index: 100007 !important; /* Highest z-index to appear above all */
}

.info-btn {
    background-color: transparent !important;
    color: #007bff !important;
    cursor: pointer !important;
    border: none !important;
    padding: 5px !important;
    margin-left: 10px !important;
}

.info-btn:hover {
    color: #0056b3 !important;
}

/* Modal backdrop */
.modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0,0,0,0.5) !important;
    z-index: -1 !important;
}

/* Fix for report content display */
#report-content {
    max-height: 400px;
    overflow-y: auto;
    padding: 20px !important;
}

#report-content p {
    margin: 10px 0;
    padding: 10px;
    background: #f0f4f8;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

#report-content p:first-child {
    margin-top: 0 !important;
}
</style>
<style>
/* Compact style for Aggiungi Lezioni modal slots */
#add-lesson-modal .modal-column {
    padding: 8px 10px;
}

#add-lesson-modal .slot-container {
    display: flex;
    gap: 12px;
    justify-content: space-between;
}

#add-lesson-modal .slot-group {
    flex: 1 1 0;
    margin-bottom: 0;
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

#add-lesson-modal .slot-header {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

#add-lesson-modal .slot-header label {
    margin: 0;
}

#add-lesson-modal .slot-header button.add-student-btn {
    width: 22px;
    height: 22px;
    font-size: 16px;
    padding: 0;
    border-radius: 50%;
}

#add-lesson-modal .slot-body {
    font-size: 13px;
    padding-left: 4px;
}

#add-lesson-modal .slot-body label {
    margin-left: 4px;
    font-weight: normal;
}

#add-lesson-modal .selected-students {
    margin-top: 4px;
    font-size: 12px;
}

#add-lesson-modal .student-item {
    padding: 2px 0;
}

.duplicate-slot-btn{
    width: 20px;
    height: 20px;
    font-size: 12px;
    padding: 15px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Stili per inserimento veloce */
.quick-add-tutor-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    cursor: pointer;
    margin-left: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.quick-add-tutor-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(102,126,234,0.4);
}

.clickable-slot:hover {
    background: rgba(102,126,234,0.1) !important;
}

.clickable-slot .empty-slot-content {
    opacity: 0.6;
    transition: opacity 0.3s ease;
}

.clickable-slot:hover .empty-slot-content {
    opacity: 1;
}

/* Stili per i modal veloci */
#quick-tutor-modal .modal-content,
#quick-student-modal .modal-content {
    margin-top: 10%;
}

.student-quick-item:hover {
    background: #f8f9fa;
}
</style>



<body>
<button id="add-lesson-fixed-btn" class="fixed-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 4px 15px rgba(102,126,234,0.3); border: none; color: white; font-size: 24px; font-weight: bold;">+</button>


	<?php include __DIR__ . '/../assets/header.php'; ?>
    <div class="container">

        <!-- Sezione superiore -->
     <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center;">
        <h1 style="margin: 0; padding-left: 50px; color: #333; font-size: 28px; font-weight: 800;">
            Gestione mensile lezioni
        </h1>
		<div class="actions">
            <button id="add-tutor-btn" class="btn" style="width:auto; border-radius: 15px;">
                <i class="fa-solid fa-user-plus" style="color: #ffffff; margin-right: 8px;"></i>Aggiungi Tutor
            </button>
            <button type="button" id="report-alunno-btn" class="btn" style="width:auto; border-radius: 15px;">
                <i class="fa-solid fa-file-lines" style="color: #ffffff; margin-right: 8px;"></i>Report Alunno
            </button>
            <button type="button" id="report-tutor-btn" class="btn" style="width:auto; border-radius: 15px;">
                <i class="fa-solid fa-chalkboard-teacher" style="color: #ffffff; margin-right: 8px;"></i>Report Tutor
            </button>
        </div>
	</div>
</div>

<!--- Modale Aggiungi lezione -->
<div id="add-lesson-modal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-add-lesson">&times;</span>
        <h2><i class="fas fa-calendar-plus"></i> Aggiungi Lezione</h2>
        
        <form action="../scripts/aggiungi_lezione.php" method="POST" id="lesson-form">
            <div class="modal-grid">
                <!-- Colonna Sinistra -->
                <div class="modal-column">
                    <h4>Dettagli Lezione</h4>
                    <div class="form-group">
                        <label for="lesson-date"><i class="fas fa-calendar-alt"></i> Data Lezione:</label>
                        <div class="input-with-icon">
                            <input type="date" id="lesson-date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="tutor-search"><i class="fas fa-user"></i> Cerca Tutor:</label>
                        <input type="text" id="tutor-search" placeholder="Cerca tutor..." onkeyup="filterTutors()">
                        <input type="hidden" id="selected-tutor-id" name="tutor_id" required>
                        <div id="tutor-list" class="tutor-list" style="max-height: 200px; overflow-y: auto; margin: 10px 0; padding: 10px; border: 1px solid #edf2f7; border-radius: 8px;">
                            <?php foreach ($tutors as $tutor): ?>
                                <div class="tutor-item" style="padding: 8px; border-bottom: 1px solid #edf2f7; cursor: pointer;" onclick="selectTutor(<?php echo $tutor['id']; ?>, '<?php echo htmlspecialchars($tutor['nome_completo'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-user" style="margin-right: 8px; color: #667eea;"></i>
                                    <span><?php echo $tutor['nome_completo']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="selected-tutor" style="margin-top: 10px; padding: 8px; background-color: #edf2f7; border-radius: 4px; display: none;">
                            <i class="fas fa-check-circle" style="color: #48bb78; margin-right: 8px;"></i>
                            <span id="selected-tutor-name"></span>
                            <button type="button" onclick="clearTutorSelection()" style="float: right; background: none; border: none; color: #e53e3e; cursor: pointer;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                </div>

                <!-- Colonna Destra -->
                <div class="modal-column">
                    <h4><i class="fas fa-clock"></i> Slot Orari</h4>
                    <div class="slot-container">
                    <?php foreach ($slots_mapping as $slot => $orario): ?>
                        <div class="slot-group" data-slot="<?php echo $slot; ?>">
                            <div class="slot-header">
                                <label><strong><?php echo $orario; ?></strong></label>
                                <button type="button" class="add-student-btn" data-slot="<?php echo $slot; ?>" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 50%; width: 25px; height: 25px; font-size: 14px; cursor: pointer;">
                                   +
                                </button>
                                <button type="button" class="duplicate-slot-btn" data-slot="<?php echo $slot; ?>" title="Duplica slot" style="background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">
                                    <i class="fa-solid fa-copy"></i>
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
                <div class="form-group form-submit">
                <button type="submit" class="btn">Salva Lezione</button>
            </div>
            </div>

            <!-- Pulsante di Invio -->
            
        </form>
    </div>
</div>

    <!-- Modale per selezionare gli studenti -->
<div id="student-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-student-modal">&times;</span>
        <h2><i class="fas fa-users"></i> Seleziona Alunni</h2>
        <div class="form-group">
            <label for="student-search"><i class="fas fa-search"></i> Cerca Alunni:</label>
            <input type="text" id="student-search" placeholder="Cerca alunni..." onkeyup="filterStudents()">
        </div>
        <form id="student-selection-form">
            <div id="student-list" class="student-list" style="max-height: 300px; overflow-y: auto; margin: 20px 0; padding: 10px; border: 1px solid #edf2f7; border-radius: 8px;">
                <?php foreach ($alunni as $alunno): ?>
                    <div class="student-item" style="padding: 8px; border-bottom: 1px solid #edf2f7; cursor: pointer;" onclick="toggleStudentSelection(this, event)">
                        <input type="checkbox" class="student-checkbox" value="<?php echo $alunno['id']; ?>">
                        <label style="cursor: pointer;"><i class="fas fa-user-graduate" style="margin-right: 8px; color: #667eea;"></i><?php echo $alunno['nome_completo']; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-submit" style="display: flex; gap: 10px; justify-content: flex-start;">
                <button type="button" id="save-students" class="btn">
                    <i class="fas fa-save"></i> Salva Selezione
                </button>
                <button type="button" id="deselect-all-students" class="btn" style="background: #f56565;">
                    <i class="fas fa-times-circle"></i> Deseleziona Tutti
                </button>
            </div>
        </form>
    </div>
</div>
	
	<!-- Modale per aggiungere un tutor -->
<div id="add-tutor-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-add-tutor">&times;</span>
        <h2><i class="fas fa-user-plus"></i> Aggiungi Tutor</h2>
        <form action="../scripts/aggiungi_tutor.php" method="POST">
            <div class="modal-grid">
                <!-- Colonna Sinistra -->
                <div class="modal-column">
                    <h4>Dati Personali</h4>
                    <div class="form-group">
                        <label for="tutor-nome"><i class="fas fa-user"></i> Nome:</label>
                        <input type="text" id="tutor-nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="tutor-cognome"><i class="fas fa-user"></i> Cognome:</label>
                        <input type="text" id="tutor-cognome" name="cognome" required>
                    </div>
                </div>

                <!-- Colonna Destra -->
                <div class="modal-column">
                    <h4>Contatti</h4>
                    <div class="form-group">
                        <label for="tutor-email"><i class="fas fa-envelope"></i> Email:</label>
                        <input type="email" id="tutor-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="tutor-telefono"><i class="fas fa-phone"></i> Telefono:</label>
                        <input type="text" id="tutor-telefono" name="telefono" required>
                    </div>
                </div>
            </div>
            
            <!-- Pulsante di invio -->
            <div class="form-group form-submit">
                <button type="submit" class="btn">Salva Tutor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modale per la Ricerca degli Alunni -->
<div id="report-alunno-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-report-modal">&times;</span>
        <h2><i class="fas fa-file-lines"></i> Genera Report Alunno</h2>
        <div class="modal-grid">
            <!-- Colonna Sinistra -->
            <div class="modal-column">
                <h4><i class="fas fa-users"></i> Seleziona Alunni</h4>
                <div class="form-group">
                    <label for="alunno-search"><i class="fas fa-search"></i> Cerca Alunni:</label>
                    <input type="text" id="alunno-search" placeholder="Cerca alunni..." onkeyup="filterAlunni()">
                </div>
                <form id="alunno-selection-form">
                    <div id="alunno-list" class="student-list" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($alunni as $alunno): ?>
                            <div class="alunno-item">
                                <input type="checkbox" class="alunno-checkbox" value="<?php echo $alunno['id']; ?>">
                                <label><?php echo $alunno['nome_completo']; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

            <!-- Colonna Destra -->
            <div class="modal-column">
                <h4><i class="fas fa-calendar"></i> Periodo Report</h4>
                <div class="form-group">
                    <label><i class="fas fa-calendar-check"></i> Tipo Periodo:</label>
                    <div style="margin: 10px 0;">
                        <input type="radio" id="periodo-mese-alunno" name="tipo-periodo-alunno" value="mese" checked>
                        <label for="periodo-mese-alunno">Mese Specifico</label><br>
                        <input type="radio" id="periodo-anno-alunno" name="tipo-periodo-alunno" value="anno">
                        <label for="periodo-anno-alunno">Anno Completo</label>
                    </div>
                </div>
                <div class="form-group" id="mese-alunno-container">
                    <label for="mese-report"><i class="fas fa-calendar-alt"></i> Seleziona Mese:</label>
                    <input type="month" id="mese-report" name="mese" value="<?php echo date('Y-m'); ?>" required>
                </div>
                <div class="form-submit" style="margin-top: 20px;">
                    <button type="button" id="generate-report_a" class="btn">
                        <i class="fas fa-file-export"></i> Genera Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modale INFO -->
<div id="info-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" onclick="closeInfoModal()">&times;</span>
        <h2><i class="fas fa-info-circle"></i> Dettagli Alunno</h2>
        <div id="info-content" style="margin-top: 20px; padding: 20px; border-radius: 8px; background: #f8fafc;">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Caricamento informazioni...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modale per Visualizzare il Report -->
<div id="report-result-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-result-modal">&times;</span>
        <h2><i class="fas fa-chart-line"></i> Risultati Report</h2>
        <div id="report-content" style="margin-top: 20px; padding: 20px; border-radius: 8px; background: #f8fafc;">
            <!-- Risultati del report verranno caricati dinamicamente -->
        </div>
        <div class="form-submit">
            <a href="#" id="download-report" class="btn">
                <i class="fas fa-download"></i> Scarica Completo
            </a>
        </div>
    </div>
</div>


<!-- Modale per Report Tutor -->
<div id="report-tutor-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="close-report-tutor-modal">&times;</span>
        <h2><i class="fas fa-chalkboard-teacher"></i> Genera Report Tutor</h2>
        <div class="modal-grid">
            <!-- Colonna Sinistra -->
            <div class="modal-column">
                <h4><i class="fas fa-user-tie"></i> Seleziona Tutor</h4>
                <div class="form-group">
                    <label for="tutor-report-search"><i class="fas fa-search"></i> Cerca Tutor:</label>
<input type="text" id="tutor-report-search" placeholder="Cerca tutor..." oninput="filterTutorReport()" onkeyup="filterTutorReport()">                </div>
                <form id="tutor-selection-form">
                    <div id="tutor-report-list" class="student-list" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($tutors as $tutor): ?>
                            <div class="tutor-report-item">
                            <input type="checkbox" class="tutor-report-checkbox" value="<?php echo $tutor['id']; ?>">
                            <label><?php echo $tutor['nome_completo']; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

            <!-- Colonna Destra -->
            <div class="modal-column">
                <h4><i class="fas fa-calendar"></i> Periodo Report</h4>
                <div class="form-group">
                    <label><i class="fas fa-calendar-check"></i> Tipo Periodo:</label>
                    <div style="margin: 10px 0;">
                        <input type="radio" id="periodo-mese" name="tipo-periodo" value="mese" checked>
                        <label for="periodo-mese">Mese Specifico</label><br>
                        <input type="radio" id="periodo-anno" name="tipo-periodo" value="anno">
                        <label for="periodo-anno">Anno Completo</label>
                    </div>
                </div>
                <div class="form-group" id="mese-tutor-container">
                    <label for="mese-tutor-report"><i class="fas fa-calendar-alt"></i> Seleziona Mese:</label>
                    <input type="month" id="mese-tutor-report" name="mese" value="<?php echo date('Y-m'); ?>" required>
                </div>
                <div class="form-submit" style="margin-top: 20px;">
                    <button type="button" id="generate-report-tutor" class="btn">
                        <i class="fas fa-file-export"></i> Genera Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

	<?php include 'calendar_view.php'; ?>
	<script src="../scripts/scripts_lezioni.js"></script>

</body>
</html>