<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera i dati dal form
    $data = $_POST['date']; // Data della lezione
    $id_tutor = intval($_POST['tutor_id']);
    $slots = $_POST['students'] ?? [];
    $half_lessons = $_POST['half_lesson'] ?? [];

    // Elenco dei giorni festivi (aggiungi i tuoi giorni festivi qui)
    $publicHolidays = [
	'2025-01-01', // Capodanno
    '2025-01-06', // Epifania
    '2025-04-20', // Pasqua
    '2025-04-21', // Lunedì dell'Angelo (Pasquetta)
    '2025-04-25', // Festa della Liberazione
    '2025-05-01', // Festa del Lavoro
    '2025-06-02', // Festa della Repubblica
    '2025-08-15', // Ferragosto
    '2025-11-01', // Ognissanti
    '2025-12-08', // Immacolata Concezione
    '2025-12-25', // Natale
    '2025-12-26'  // Santo Stefano
    ];

    // Controlla se la data è un fine settimana (sabato o domenica) o un giorno festivo
    $giornoSettimana = date('w', strtotime($data)); // 0 = Domenica, 6 = Sabato
    if ($giornoSettimana == 0 || $giornoSettimana == 6 || in_array($data, $publicHolidays)) {
        header("Location: ../pages/gestione_lezioni.php?error=Data non valida (fine settimana o giorno festivo)");
        exit();
    }

    $conn->begin_transaction();

    try {
        foreach ($slots as $slot => $students) {
            if (!empty($students)) {
                // Determina il tipo di lezione
                $tipo = count(explode(',', $students)) === 1 ? 'singolo' : 'gruppo';
                $durata = isset($half_lessons[$slot]) ? 1 : 0; // 1: Mezza Lezione, 0: Lezione Intera

                // Inserisci la lezione nella tabella `lezioni`
                $sql_lezione = "INSERT INTO lezioni (data, id_tutor, slot_orario, durata, tipo) VALUES (?, ?, ?, ?, ?)";
                $stmt_lezione = $conn->prepare($sql_lezione);
                $stmt_lezione->bind_param("siiss", $data, $id_tutor, $slot, $durata, $tipo);
                $stmt_lezione->execute();
                $id_lezione = $stmt_lezione->insert_id;

                // Inserisci gli studenti associati nella tabella `lezioni_alunni`
                foreach (explode(',', $students) as $id_alunno) {
                    $sql_alunni = "INSERT INTO lezioni_alunni (id_lezione, id_alunno) VALUES (?, ?)";
                    $stmt_alunni = $conn->prepare($sql_alunni);
                    $stmt_alunni->bind_param("ii", $id_lezione, $id_alunno);
                    $stmt_alunni->execute();
                }
            }
        }

        $conn->commit();
        header("Location: ../pages/gestione_lezioni.php?success=1");
    } catch (Exception $e) {
        $conn->rollback();
        die("Errore durante il salvataggio della lezione: " . $e->getMessage());
    }
}
?>