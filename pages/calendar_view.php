<?php
require_once '../config.php';

// Funzione per ottenere le festività italiane
function getItalianHolidays($year) {
    return [
        "$year-01-01" => "Capodanno",
        "$year-01-06" => "Epifania",
        "$year-04-25" => "Festa della Liberazione",
        "$year-05-01" => "Festa dei Lavoratori",
        "$year-06-02" => "Festa della Repubblica",
        "$year-08-15" => "Ferragosto",
        "$year-11-01" => "Tutti i Santi",
        "$year-12-08" => "Immacolata Concezione",
        "$year-12-25" => "Natale",
        "$year-12-26" => "Santo Stefano"
    ];
}

// Ottieni le lezioni dal database
function getLessons($month, $year, $conn) {
    $lessons = [];
    $startDate = "$year-$month-01";
    $endDate = date("Y-m-t", strtotime($startDate));

    // Mappa per associare gli orari degli slot a numeri
    $slotMapping = [
        '15:30-16:30' => 1,
        '16:30-17:30' => 2,
        '17:30-18:30' => 3,
    ];

   $sql = "SELECT l.data, l.slot_orario, l.durata, l.id as lesson_id, t.nome AS tutor_nome, t.cognome AS tutor_cognome,
               GROUP_CONCAT(CONCAT(a.id, ':', a.nome, ' ', a.cognome) SEPARATOR '|') AS studenti, l.id_tutor
        FROM lezioni l
        LEFT JOIN tutor t ON l.id_tutor = t.id
        LEFT JOIN lezioni_alunni la ON l.id = la.id_lezione
        LEFT JOIN alunni a ON la.id_alunno = a.id
        WHERE l.data BETWEEN ? AND ?
        GROUP BY l.data, l.slot_orario, l.id_tutor, l.id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Converti lo slot orario in un valore numerico utilizzando la mappa
        $slotNumber = $slotMapping[$row['slot_orario']] ?? null;
        if ($slotNumber !== null) {
            $lessons[$row['data']][$row['id_tutor']][$slotNumber] = [
                'lesson_id' => $row['lesson_id'],
                'tutor' => $row['tutor_nome'] . ' ' . $row['tutor_cognome'],
                'studenti' => $row['studenti'],
                'durata' => $row['durata']
            ];
        }
    }

    return $lessons;
}

// Parametri iniziali
// Aggiungi DOPO le funzioni esistenti, PRIMA di $month = ...

// Ottieni lista tutor per dropdown
$tutors = [];
$sql = "SELECT id, CONCAT(nome, ' ', cognome) AS nome_completo FROM tutor ORDER BY nome ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $tutors[] = $row;
}

// Ottieni lista alunni per dropdown
$alunni = [];
$sql = "SELECT id, CONCAT(nome, ' ', cognome) AS nome_completo FROM alunni ORDER BY nome ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $alunni[] = $row;
}


$month = isset($_GET['month']) ? date('m', strtotime($_GET['month'])) : date('m');
$year = isset($_GET['month']) ? date('Y', strtotime($_GET['month'])) : date('Y');
$lessons = getLessons($month, $year, $conn);
$holidays = getItalianHolidays($year);

// Genera il calendario
$daysInMonth = date("t", strtotime("$year-$month-01"));
?>

<!-- Modale per modificare lezione esistente -->
<div id="edit-lesson-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <span class="close-btn" onclick="closeEditLessonModal()">&times;</span>
        <h3><i class="fas fa-edit"></i> Modifica Lezione</h3>
        
        <form id="edit-lesson-form">
            <input type="hidden" id="edit-lesson-id" name="lesson_id">
            <input type="hidden" id="edit-date" name="date">
            <input type="hidden" id="edit-tutor" name="tutor_id">
            <input type="hidden" id="edit-slot" name="slot">
            
            <div class="modal-grid">
                <!-- Colonna Sinistra - Info Lezione -->
                <div class="modal-column" style="flex: 1;">
                    <h4><i class="fas fa-info-circle"></i> Dettagli Lezione</h4>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <p style="margin: 0 0 8px 0;"><strong>Data:</strong> <span id="edit-info-date"></span></p>
                        <p style="margin: 0 0 8px 0;"><strong>Orario:</strong> <span id="edit-info-slot"></span></p>
                        <p style="margin: 0;"><strong>Tutor:</strong> <span id="edit-info-tutor"></span></p>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                            <input type="checkbox" 
                                   id="edit-half-lesson"
                                   name="half_lesson" 
                                   value="1"
                                   style="width: auto; margin-right: 10px;">
                            <span style="font-weight: 500;">
                                <i class="fas fa-clock" style="margin-right: 8px; color: #667eea;"></i>
                                Mezza Lezione
                            </span>
                        </label>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="button" 
                                onclick="deleteLessonSlot()" 
                                class="btn"
                                style="background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%); width: 100%;">
                            <i class="fas fa-trash"></i> Elimina Lezione
                        </button>
                    </div>
                </div>
                
                <!-- Colonna Destra - Alunni -->
                <div class="modal-column" style="flex: 2;">
                    <h4><i class="fas fa-users"></i> Alunni Iscritti</h4>
                    
                    <div id="current-students-list" style="margin-bottom: 20px;">
                        <!-- Verrà popolato dinamicamente -->
                    </div>
                    
                    <h4><i class="fas fa-user-plus"></i> Aggiungi Altri Alunni</h4>
                    
                    <div class="form-group">
                        <div class="search-bar" style="max-width: 100%;">
                            <input type="text" 
                                   id="edit-search" 
                                   placeholder="Cerca alunno da aggiungere..." 
                                   onkeyup="filterEditStudents()"
                                   style="width: 100%;">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    
                    <div class="student-list" id="edit-available-students" style="max-height: 200px; overflow-y: auto;">
                        <!-- Verrà popolato dinamicamente -->
                    </div>
                </div>
            </div>
            
            <div class="form-submit">
                <button type="submit" class="btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: auto; padding: 12px 30px;">
                    <i class="fas fa-save" style="margin-right: 8px;"></i>
                    Salva Modifiche
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modale veloce per aggiungere tutor -->
<div id="quick-tutor-modal" class="modal" style="display: none; z-index: 10000;">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close-btn" onclick="closeQuickTutorModal()">&times;</span>
        <h3><i class="fas fa-user-plus"></i> Aggiungi Tutor al Giorno</h3>
        <form id="quick-tutor-form">
            <input type="hidden" id="quick-date" name="date">
            <div class="form-group">
                <label>Seleziona Tutor:</label>
                <select id="quick-tutor-select" name="tutor_id" required>
                    <option value="">-- Seleziona --</option>
                    <?php foreach ($tutors as $tutor): ?>
                        <option value="<?php echo $tutor['id']; ?>"><?php echo $tutor['nome_completo']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Aggiungi</button>
        </form>
    </div>
</div>

<!-- Modale veloce per aggiungere alunni RESTYLED -->
<div id="quick-student-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close-btn" onclick="closeQuickStudentModal()">&times;</span>
        <h3><i class="fas fa-users"></i> Aggiungi Alunni allo Slot</h3>
        
        <form id="quick-student-form">
            <input type="hidden" id="quick-student-date" name="date">
            <input type="hidden" id="quick-student-tutor" name="tutor_id">
            <input type="hidden" id="quick-student-slot" name="slot">
            
            <div class="modal-grid">
                <!-- Colonna Sinistra - Ricerca e Lista -->
                <div class="modal-column" style="flex: 2;">
                    <h4><i class="fas fa-search"></i> Seleziona Alunni</h4>
                    
                    <div class="form-group">
                        <div class="search-bar" style="max-width: 100%;">
                            <input type="text" 
                                   id="quick-search" 
                                   placeholder="Cerca alunno..." 
                                   onkeyup="filterQuickStudents()"
                                   style="width: 100%;">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    
                    <div class="student-list" style="max-height: 250px; overflow-y: auto;">
                        <?php foreach ($alunni as $alunno): ?>
                            <div class="student-item student-quick-item" style="padding: 10px; border-bottom: 1px solid #edf2f7;">
                                <label style="display: flex; align-items: center; cursor: pointer; margin: 0;">
                                    <input type="checkbox" 
                                           name="students[]" 
                                           value="<?php echo $alunno['id']; ?>"
                                           style="width: auto; margin-right: 10px;">
                                    <i class="fas fa-user-graduate" style="margin-right: 10px; color: #667eea;"></i>
                                    <span style="flex: 1;"><?php echo $alunno['nome_completo']; ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Colonna Destra - Opzioni -->
                <div class="modal-column" style="flex: 1;">
                    <h4><i class="fas fa-cog"></i> Opzioni</h4>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                            <input type="checkbox" 
                                   name="half_lesson" 
                                   value="1"
                                   style="width: auto; margin-right: 10px;">
                            <span style="font-weight: 500;">
                                <i class="fas fa-clock" style="margin-right: 8px; color: #667eea;"></i>
                                Mezza Lezione
                            </span>
                        </label>
                    </div>
                    
                    <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <p style="margin: 0; font-size: 0.9em; color: #4a5568;">
                            <i class="fas fa-info-circle" style="color: #667eea;"></i>
                            Seleziona uno o più alunni per questo slot orario
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="form-submit">
                <button type="submit" class="btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: auto; padding: 12px 30px;">
                    <i class="fas fa-save" style="margin-right: 8px;"></i>
                    Salva Lezione
                </button>
            </div>
        </form>
    </div>
</div>


<div class="calendar">
    <div class="calendar-header">
        <h2><i class="fas fa-calendar-alt"></i> Calendario Lezioni - <?php echo date("F Y", strtotime("$year-$month-01")); ?></h2>
        <form method="GET" id="month-form">
            <label for="month"><i class="fas fa-filter"></i> Seleziona mese:</label>
            <input type="month" id="month" name="month" value="<?php echo "$year-$month"; ?>" onchange="this.form.submit()">
        </form>
    </div>

    <div class="calendar-container">
        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
            <?php
            $currentDate = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
            $isHoliday = isset($holidays[$currentDate]) || date("N", strtotime($currentDate)) >= 6;
            $lessonsForDay = $lessons[$currentDate] ?? [];
            $dayName = date("l", strtotime($currentDate));
            $dayNameIta = [
                'Monday' => 'Lunedì',
                'Tuesday' => 'Martedì', 
                'Wednesday' => 'Mercoledì',
                'Thursday' => 'Giovedì',
                'Friday' => 'Venerdì',
                'Saturday' => 'Sabato',
                'Sunday' => 'Domenica'
            ][$dayName];
            ?>
            
            <div class="day-section <?php echo $isHoliday ? 'holiday' : ''; ?> <?php echo empty($lessonsForDay) && !$isHoliday ? 'empty-day' : ''; ?>">
              
                <div class="day-header">
                    <div class="date-info">
                        <span class="date"><?php echo $day; ?></span>
                        <span class="day-name"><?php echo $dayNameIta; ?></span>
                        <span class="full-date"><?php echo date("d/m/Y", strtotime($currentDate)); ?></span>
                    </div>
                    <?php if ($isHoliday): ?>
                        <div class="day-status holiday-status">
                            <i class="fas fa-star"></i>
                            <?php echo $holidays[$currentDate] ?? 'Weekend'; ?>
                        </div>
                    <?php elseif (empty($lessonsForDay)): ?>
                        <div class="day-status empty-status">
                            <i class="fas fa-calendar-times"></i>
                            Nessuna lezione
                            <button class="quick-add-tutor-btn" onclick="openQuickTutorModal('<?php echo $currentDate; ?>')" title="Aggiungi tutor">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="day-status active-status">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <?php echo count($lessonsForDay); ?> tutor attivi
                            <button class="quick-add-tutor-btn" onclick="openQuickTutorModal('<?php echo $currentDate; ?>')" title="Aggiungi tutor">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$isHoliday && !empty($lessonsForDay)): ?>
                    <div class="lessons-table-wrapper">
                        <table class="modern-lessons-table">
                            <thead>
                                <tr>
                                    <th class="tutor-column">
                                        <i class="fas fa-user-tie"></i> Tutor
                                    </th>
                                    <th class="slot-column">
                                        <i class="fas fa-clock"></i> 15:30-16:30
                                    </th>
                                    <th class="slot-column">
                                        <i class="fas fa-clock"></i> 16:30-17:30
                                    </th>
                                    <th class="slot-column">
                                        <i class="fas fa-clock"></i> 17:30-18:30
                                    </th>
                                    <th class="actions-column">
                                        <i class="fas fa-cogs"></i> Azioni
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessonsForDay as $tutorId => $slots): ?>
                                    <tr class="tutor-row">
                                        <td class="tutor-cell">
                                            <div class="tutor-info">
                                                <i class="fas fa-user-circle"></i>
                                                <span class="tutor-name">
                                                    <?php 
                                                    echo isset($slots[1]) ? $slots[1]['tutor'] : 
                                                         (isset($slots[2]) ? $slots[2]['tutor'] : 
                                                         (isset($slots[3]) ? $slots[3]['tutor'] : 'Nessun Tutor'));
                                                    ?>
                                                </span>
                                            </div>
                                        </td>
                                        <?php for ($slot = 1; $slot <= 3; $slot++): ?>
                                            <td class="slot-cell <?php echo isset($slots[$slot]) ? 'has-lesson clickable-edit' : 'empty-slot clickable-slot'; ?> <?php echo (isset($slots[$slot]) && $slots[$slot]['durata']) ? 'half-lesson' : ''; ?>"
                                            <?php if (isset($slots[$slot])): ?>
                                                onclick="openEditLessonModal(<?php echo $slots[$slot]['lesson_id']; ?>, '<?php echo $currentDate; ?>', <?php echo $tutorId; ?>, <?php echo $slot; ?>, '<?php echo htmlspecialchars($slots[$slot]['tutor'], ENT_QUOTES); ?>')"
                                                style="cursor: pointer;"
                                                data-lesson-id="<?php echo $slots[$slot]['lesson_id']; ?>"
                                            <?php else: ?>
                                                onclick="openQuickStudentModal('<?php echo $currentDate; ?>', <?php echo $tutorId; ?>, <?php echo $slot; ?>)"
                                                style="cursor: pointer;"
                                            <?php endif; ?>>
                                            
                                            <?php if (isset($slots[$slot])): ?>
                                                <!-- Contenuto esistente -->
                                                <div class="students-list">
                                                    <i class="fas fa-users"></i>
                                                    <span class="students-names">
                                                        <?php 
                                                        $studentNames = [];
                                                        if ($slots[$slot]['studenti']) {
                                                            $studentPairs = explode('|', $slots[$slot]['studenti']);
                                                            foreach ($studentPairs as $pair) {
                                                                $parts = explode(':', $pair);
                                                                if (count($parts) == 2) {
                                                                    $studentNames[] = $parts[1];
                                                                }
                                                            }
                                                        }
                                                        echo implode(', ', $studentNames);
                                                        ?>
                                                    </span>
                                                    <?php if ($slots[$slot]['durata']): ?>
                                                        <span class="half-lesson-badge">½</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="empty-slot-content">
                                                    <i class="fas fa-plus-circle"></i>
                                                    <span>Aggiungi</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endfor; ?>
                                        <td class="actions-cell">
                                            <button class="delete-btn" data-date="<?php echo $currentDate; ?>" data-tutor="<?php echo $tutorId; ?>" title="Elimina tutte le lezioni del tutor">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const tutorId = this.getAttribute('data-tutor');
                const date = this.getAttribute('data-date');

                const confirmDelete = confirm(`Sei sicuro di voler eliminare tutte le lezioni del tutor per il giorno ${date}?`);
                if (confirmDelete) {
                    // Effettua la richiesta al backend
                    fetch('../scripts/elimina_lezioni.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ tutor_id: tutorId, date: date }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Lezioni eliminate con successo.');
                            location.reload(); // Ricarica la pagina per aggiornare il calendario
                        } else {
                            alert('Errore durante l\'eliminazione delle lezioni.');
                        }
                    })
                    .catch(error => {
                        console.error('Errore:', error);
                        alert('Si è verificato un errore.');
                    });
                }
            });
        });
    });
</script>

<style>
    .calendar {
        margin: 20px auto;
        padding: 20px;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(250, 250, 250, 0.95));
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        max-width: 1400px;
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 12px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .calendar-header h2 {
        font-size: 1.8em;
        color: #2d3748;
        margin: 0;
    }

    .calendar-header form {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .calendar-header input[type="month"] {
        padding: 8px 15px;
        border: 2px solid transparent;
        border-radius: 12px;
        font-size: 0.9em;
        outline: none;
        transition: all 0.3s ease;
        background: linear-gradient(white, white) padding-box, linear-gradient(135deg, #667eea, #764ba2) border-box;
        box-shadow: 0 4px 15px rgba(102,126,234,0.1);
        cursor: pointer;
    }

    .calendar-header input[type="month"]:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(102,126,234,0.15);
    }

    .calendar-header input[type="month"]:focus {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102,126,234,0.2);
    }


    .calendar-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 10px;
    }

    .day-section {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(102,126,234,0.1);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .day-section:not(.holiday):hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(102,126,234,0.15);
    }

    .day-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .day-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            rgba(255,255,255,0) 0%,
            rgba(255,255,255,0.1) 50%,
            rgba(255,255,255,0) 100%);
        transform: skewX(-20deg) translateX(-100%);
        transition: transform 0.7s ease;
    }

    .day-section:hover .day-header::after {
        transform: skewX(-20deg) translateX(100%);
    }

    .date-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .date {
        font-size: 1.5em;
        font-weight: 700;
    }

    .day-name {
        font-size: 1em;
        font-weight: 500;
        opacity: 0.9;
    }

    .full-date {
        font-size: 0.85em;
        opacity: 0.8;
    }

    .day-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.85em;
        font-weight: 500;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .day-status i {
        font-size: 1.1em;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    .holiday-status {
        background: linear-gradient(135deg, rgba(245,101,101,0.15) 0%, rgba(229,62,62,0.15) 100%);
        border-color: rgba(245,101,101,0.3);
    }

    .empty-status {
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    }

    .active-status {
        background: linear-gradient(135deg, rgba(72,187,120,0.15) 0%, rgba(56,161,105,0.15) 100%);
        border-color: rgba(72,187,120,0.3);
    }

    .day-status:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }

    .lessons-table-wrapper {
        padding: 12px;
    }

    .modern-lessons-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 10px;
        background: white;
        border-radius: 12px;
        overflow: hidden;
    }

    .modern-lessons-table th {
        background: #f8fafc;
        color: #4a5568;
        padding: 12px 16px;
        font-weight: 600;
        text-align: left;
        font-size: 0.9em;
        border-bottom: 1px solid #e2e8f0;
    }

    .modern-lessons-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #edf2f7;
        font-size: 0.9em;
    }

    .tutor-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .tutor-name {
        font-weight: 500;
        color: #2d3748;
    }

    .slot-cell {
        position: relative;
        transition: background-color 0.3s ease;
    }

    .slot-cell.has-lesson {
        background: #ffffff;
    }

    .slot-cell.empty-slot {
        background: #ffffff;
    }

    .slot-cell.half-lesson {
        background: #ffffff;
    }

    .students-list {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9em;
        color: #4a5568;
        padding: 6px 8px;
    }

    .students-list i {
        color: #667eea;
    }

    .students-names {
        font-weight: 500;
        color: #2d3748;
    }

    .half-lesson-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.8em;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(102,126,234,0.3);
        border: 1px solid rgba(255,255,255,0.2);
        backdrop-filter: blur(4px);
        transition: all 0.3s ease;
    }

    .half-lesson-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102,126,234,0.4);
    }

    .empty-slot-content {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #a0aec0;
        font-size: 0.9em;
        padding: 6px 8px;
        border-radius: 6px;
        background: rgba(247,250,252,0.5);
        border: 1px solid rgba(102,126,234,0.05);
    }

    .empty-slot-content i {
        opacity: 0.5;
    }

    .delete-btn {
        padding: 8px;
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .delete-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(229,62,62,0.3);
    }

    .holiday {
        opacity: 0.7;
        pointer-events: none;
    }

    .empty-day {
        background: #f7fafc;
    }

    @media (max-width: 1400px) {
        .calendar {
            margin: 20px;
        }
    }

    @media (max-width: 768px) {
        .calendar {
            margin: 10px;
            padding: 15px;
        }

        .calendar-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
            padding: 15px;
        }

        .calendar-header form {
            flex-direction: column;
            width: 100%;
        }

        .calendar-header input[type="month"] {
            width: 100%;
        }

        .day-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        .modern-lessons-table {
            font-size: 0.9em;
        }

        .slot-cell {
            padding: 8px;
        }
    }

    @media (max-width: 480px) {
        .calendar-header h2 {
            font-size: 1.5em;
        }

        .date-info {
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .modern-lessons-table {
            font-size: 0.8em;
        }

        .students-list {
            flex-direction: column;
            align-items: flex-start;
        }
    }

  /* Stili specifici per modal veloci */
#quick-student-modal .modal-content {
    animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

#quick-student-modal .student-item:hover {
    background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05));
}

#quick-student-modal .student-item input[type="checkbox"]:checked + i + span {
    font-weight: 600;
    color: #667eea;
}

/* Migliora il bottone + nel calendario */
.quick-add-tutor-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    cursor: pointer;
    margin-left: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(102,126,234,0.3);
}

.quick-add-tutor-btn:hover {
    transform: scale(1.15);
    box-shadow: 0 4px 15px rgba(102,126,234,0.4);
}

/* Migliora gli slot cliccabili */
.clickable-slot {
    position: relative;
    overflow: hidden;
}

.clickable-slot::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(102,126,234,0.1), rgba(118,75,162,0.1));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.clickable-slot:hover::before {
    opacity: 1;
}

.clickable-slot .empty-slot-content {
    position: relative;
    z-index: 1;
} 

/* Stile per celle modificabili */
.clickable-edit {
    position: relative;
}

.clickable-edit::after {
    content: '\f303';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    top: 5px;
    right: 5px;
    color: #667eea;
    opacity: 0;
    transition: opacity 0.3s ease;
    font-size: 12px;
}

.clickable-edit:hover::after {
    opacity: 1;
}

.clickable-edit:hover {
    background: rgba(102,126,234,0.1) !important;
}

@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(-5px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

/* Migliora lo stile del modal di modifica */
#edit-lesson-modal .modal-content {
    animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

#edit-lesson-modal .student-item {
    transition: all 0.3s ease;
}

#edit-lesson-modal .student-item:hover {
    transform: translateX(5px);
}

#edit-lesson-modal .btn-remove:hover {
    transform: scale(1.1);
}

#edit-lesson-modal .edit-available-item:hover {
    background: linear-gradient(135deg, rgba(72,187,120,0.05), rgba(56,161,105,0.05));
}
</style>
