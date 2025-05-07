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

    $sql = "SELECT l.data, l.slot_orario, l.durata, t.nome AS tutor_nome, t.cognome AS tutor_cognome,
                   GROUP_CONCAT(a.nome, ' ', a.cognome) AS studenti, l.id_tutor
            FROM lezioni l
            LEFT JOIN tutor t ON l.id_tutor = t.id
            LEFT JOIN lezioni_alunni la ON l.id = la.id_lezione
            LEFT JOIN alunni a ON la.id_alunno = a.id
            WHERE l.data BETWEEN ? AND ?
            GROUP BY l.data, l.slot_orario, l.id_tutor";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Converti lo slot orario in un valore numerico utilizzando la mappa
        $slotNumber = $slotMapping[$row['slot_orario']] ?? null;
        if ($slotNumber !== null) {
            $lessons[$row['data']][$row['id_tutor']][$slotNumber] = [
                'tutor' => $row['tutor_nome'] . ' ' . $row['tutor_cognome'],
                'studenti' => $row['studenti'],
                'durata' => $row['durata']
            ];
        }
    }

    return $lessons;
}

// Parametri iniziali
$month = isset($_GET['month']) ? date('m', strtotime($_GET['month'])) : date('m');
$year = isset($_GET['month']) ? date('Y', strtotime($_GET['month'])) : date('Y');
$lessons = getLessons($month, $year, $conn);
$holidays = getItalianHolidays($year);

// Genera il calendario
$daysInMonth = date("t", strtotime("$year-$month-01"));
?>

<div class="calendar">
    <div class="calendar-header">
        <h2>Calendario Lezioni - <?php echo date("F Y", strtotime("$year-$month-01")); ?></h2>
        <form method="GET" >
            <label for="month">Seleziona mese:</label>
            <input type="month" id="month" name="month" style="width: 70%; padding: 10px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; margin-top: 5px; margin-right: 10px;" value="<?php echo "$year-$month"; ?>">
            <button type="submit" class="btn">Vai</button>
        </form>
    </div>

    <div class="calendar-rows">
        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
            <?php
            $currentDate = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
            $isHoliday = isset($holidays[$currentDate]) || date("N", strtotime($currentDate)) >= 6;
            $lessonsForDay = $lessons[$currentDate] ?? [];
            ?>
            <div class="calendar-day <?php echo $isHoliday ? 'holiday' : ''; ?>">
                <div class="day-header">
                    <span class="date"><?php echo $day . ' ' . date("F Y", strtotime($currentDate)); ?></span>
                    <?php if ($isHoliday): ?>
                        <span class="holiday-label">
                            <?php echo $holidays[$currentDate] ?? 'Weekend'; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!$isHoliday): ?>
                    <table class="slots-table">
                        <thead>
                            <tr>
                                <th>Tutor</th>
                                <th>Slot 1 (15:30-16:30)</th>
                                <th>Slot 2 (16:30-17:30)</th>
                                <th>Slot 3 (17:30-18:30)</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessonsForDay as $tutorId => $slots): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        echo isset($slots[1]) ? $slots[1]['tutor'] : 
                                             (isset($slots[2]) ? $slots[2]['tutor'] : 
                                             (isset($slots[3]) ? $slots[3]['tutor'] : 'Nessun Tutor'));
                                        ?>
                                    </td>
                                    <?php for ($slot = 1; $slot <= 3; $slot++): ?>
                                        <td class="<?php echo (isset($slots[$slot]) && $slots[$slot]['durata']) ? 'half-lesson' : ''; ?>">
                                            <?php echo isset($slots[$slot]) ? $slots[$slot]['studenti'] : 'Vuoto'; ?>
                                        </td>
                                    <?php endfor; ?>
                                    <td>
                                        <button class="delete-btn" data-date="<?php echo $currentDate; ?>" data-tutor="<?php echo $tutorId; ?>"><i class="fa-solid fa-trash-can" style="font-size: 12px;"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        margin-top: 20px;
        padding-top: 20px;
		padding-left:100px;
		padding-right:100px;
        background-color: #f9f9f9;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .calendar-rows {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .calendar-day {
        border: 1px solid #ccc;
        padding: 15px;
        background-color: #fff;
        border-radius: 8px;
    }

    .calendar-day.holiday {
        background-color: #273469;
        color: white;
        text-align: center;
    }

    .day-header {
        font-weight: bold;
        margin-bottom: 10px;
    }

    .slots-table {
        width: 100%;
        border-collapse: collapse;
    }

    .slots-table th, .slots-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
    }

    .slots-table th {
        background-color: #f4f4f4;
        font-weight: bold;
    }

    .half-lesson {
        color: #C1666B;
    }

    .delete-btn {
        padding: 5px 10px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .delete-btn:hover {
        background-color: #c9302c;
    }
</style>