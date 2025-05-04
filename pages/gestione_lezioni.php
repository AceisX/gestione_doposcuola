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
</head>
<body>
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
                <button id="add-lesson-btn" class="btn">Aggiungi Lezione</button>
            </div>
        </div>


        <!-- Modale per aggiungere una lezione -->
        <div id="add-lesson-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close-btn" id="close-add-lesson">&times;</span>
                <h2>Aggiungi Lezione</h2>
                <form action="../scripts/aggiungi_lezione.php" method="POST">
                    <div class="form-group">
                        <label for="lesson-date">Data Lezione:</label>
                        <input type="date" id="lesson-date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="tutor-select">Seleziona Tutor:</label>
                        <select id="tutor-select" name="tutor_id" required>
                            <option value="" disabled selected>Seleziona un Tutor</option>
                            <?php foreach ($tutors as $tutor): ?>
                                <option value="<?php echo $tutor['id']; ?>"><?php echo $tutor['nome_completo']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Slot orari -->
                    <h4>Slot Orari</h4>
                    <?php foreach ($slots_mapping as $slot => $orario): ?>
    <div class="slot-group" data-slot="<?php echo $slot; ?>">
        <label><?php echo $orario; ?></label>
        <button type="button" class="add-student-btn" data-slot="<?php echo $slot; ?>">+</button>
        <div>
            <input type="checkbox" name="half_lesson[<?php echo $slot; ?>]"> Mezza Lezione
        </div>
        <div class="selected-students"></div>
        <!-- Campo nascosto per gli ID degli alunni -->
        <input type="hidden" name="students[<?php echo $slot; ?>]" class="student-ids">
    </div>
<?php endforeach; ?>
                    <button type="submit" class="btn">Salva Lezione</button>
                </form>
            </div>
        </div>

        <!-- Modale per selezionare gli studenti -->
        <div id="student-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close-btn" id="close-student-modal">&times;</span>
                <h2>Seleziona Alunni</h2>
                <input type="text" id="student-search" placeholder="Cerca alunni..." onkeyup="filterStudents()">
                <form id="student-selection-form">
                    <div id="student-list">
                        <?php foreach ($alunni as $alunno): ?>
                            <label>
                                <input type="checkbox" class="student-checkbox" value="<?php echo $alunno['id']; ?>">
                                <?php echo $alunno['nome_completo']; ?>
                            </label><br>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="save-students" class="btn">Salva</button>
                </form>
            </div>
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
	
	<?php include 'calendar_view.php'; ?>

    <script>
        let currentSlot = null;

        // Gestione apertura e chiusura modale per aggiungere studenti
        document.querySelectorAll('.add-student-btn').forEach(button => {
            button.addEventListener('click', () => {
                currentSlot = button.closest('.slot-group');
                document.getElementById('student-modal').style.display = 'block';
            });
        });

        document.getElementById('close-student-modal').addEventListener('click', () => {
            document.getElementById('student-modal').style.display = 'none';
        });

        // Salva gli studenti selezionati
       document.getElementById('save-students').addEventListener('click', () => {
    // Ottieni gli studenti selezionati
    const selectedStudents = Array.from(document.querySelectorAll('.student-checkbox:checked'))
        .map(checkbox => checkbox.value);

    // Aggiorna la visualizzazione degli studenti
    const studentContainer = currentSlot.querySelector('.selected-students');
    studentContainer.innerHTML = ''; // Resetta la lista visibile
    selectedStudents.forEach(studentId => {
        const studentLabel = document.querySelector(`label input[value="${studentId}"]`).nextSibling.textContent.trim();
        const studentItem = document.createElement('div');
        studentItem.textContent = studentLabel;
        studentContainer.appendChild(studentItem);
    });

    // Aggiorna il campo nascosto con gli ID degli studenti
    const hiddenInput = currentSlot.querySelector('.student-ids');
    hiddenInput.value = selectedStudents.join(',');

    // Chiudi il modale
    document.getElementById('student-modal').style.display = 'none';
});

        // Filtro studenti
        function filterStudents() {
            const search = document.getElementById('student-search').value.toLowerCase();
            document.querySelectorAll('#student-list label').forEach(label => {
                const text = label.textContent.toLowerCase();
                label.style.display = text.includes(search) ? '' : 'none';
            });
        }

        // Gestione apertura e chiusura modale per aggiungere lezioni
        document.getElementById('add-lesson-btn').addEventListener('click', () => {
            document.getElementById('add-lesson-modal').style.display = 'block';
        });

        document.getElementById('close-add-lesson').addEventListener('click', () => {
            document.getElementById('add-lesson-modal').style.display = 'none';
        });

        window.onclick = function(event) {
            const studentModal = document.getElementById('student-modal');
            const lessonModal = document.getElementById('add-lesson-modal');
            if (event.target === studentModal) studentModal.style.display = 'none';
            if (event.target === lessonModal) lessonModal.style.display = 'none';
        };
		

    </script>
	
	
	<script>
    // Script per il modale "Aggiungi Tutor"
    const addTutorBtn = document.getElementById('add-tutor-btn');
    const addTutorModal = document.getElementById('add-tutor-modal');
    const closeAddTutor = document.getElementById('close-add-tutor');

    // Apertura del modale "Aggiungi Tutor"
    addTutorBtn.addEventListener('click', () => {
        addTutorModal.style.display = 'block';
    });

    // Chiusura del modale "Aggiungi Tutor"
    closeAddTutor.addEventListener('click', () => {
        addTutorModal.style.display = 'none';
    });

    // Chiudi il modale cliccando fuori dal contenuto
    window.onclick = function(event) {
        if (event.target === addTutorModal) {
            addTutorModal.style.display = 'none';
        }
    };
</script>
</body>
</html>