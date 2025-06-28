<?php
require_once '../config.php';

// Determina se la richiesta vuole una risposta JSON
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera i dati dal form
    $data = $_POST['date']; // Data della lezione
    $id_tutor = intval($_POST['tutor_id']);
    $slots = $_POST['students'] ?? [];
    $half_lessons = $_POST['half_lesson'] ?? [];
    $return_month = $_POST['return_month'] ?? ''; // Mese di ritorno per mantenere la vista

    // Elenco dei giorni festivi
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

    // Controlla se la data è un fine settimana o un giorno festivo
    $giornoSettimana = date('w', strtotime($data));
    if ($giornoSettimana == 0 || $giornoSettimana == 6 || in_array($data, $publicHolidays)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Data non valida (fine settimana o giorno festivo)'
            ]);
            exit();
        } else {
            $errorUrl = "../pages/gestione_lezioni.php?error=" . urlencode("Data non valida (fine settimana o giorno festivo)");
            if (!empty($return_month)) {
                $errorUrl .= "&month=" . $return_month;
            }
            header("Location: " . $errorUrl);
            exit();
        }
    }

    $conn->begin_transaction();

    try {
        $lezioni_aggiunte = 0;
        
        foreach ($slots as $slot => $students) {
            if (!empty($students)) {
                // Determina il tipo di lezione
                $student_array = explode(',', $students);
                $tipo = count($student_array) === 1 ? 'singolo' : 'gruppo';
                $durata = isset($half_lessons[$slot]) ? 1 : 0;

                // Mappa slot a orario
                $slot_orari = [
                    1 => '15:30-16:30',
                    2 => '16:30-17:30',
                    3 => '17:30-18:30'
                ];
                $slot_orario = $slot_orari[$slot] ?? '';

                // Inserisci la lezione
                $sql_lezione = "INSERT INTO lezioni (data, id_tutor, slot_orario, durata, tipo) VALUES (?, ?, ?, ?, ?)";
                $stmt_lezione = $conn->prepare($sql_lezione);
                $stmt_lezione->bind_param("sisis", $data, $id_tutor, $slot_orario, $durata, $tipo);
                $stmt_lezione->execute();
                $id_lezione = $stmt_lezione->insert_id;

                // Inserisci gli studenti associati
                foreach ($student_array as $id_alunno) {
                    $id_alunno = trim($id_alunno);
                    if (!empty($id_alunno)) {
                        $sql_alunni = "INSERT INTO lezioni_alunni (id_lezione, id_alunno) VALUES (?, ?)";
                        $stmt_alunni = $conn->prepare($sql_alunni);
                        $stmt_alunni->bind_param("ii", $id_lezione, $id_alunno);
                        $stmt_alunni->execute();
                    }
                }
                
                $lezioni_aggiunte++;
            }
        }

        $conn->commit();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => "Lezione/i aggiunta/e con successo! ($lezioni_aggiunte slot compilati)",
                'lezioni_aggiunte' => $lezioni_aggiunte,
                'return_month' => $return_month
            ]);
        } else {
            // Costruisci l'URL di ritorno
            $successUrl = "../pages/gestione_lezioni.php?success=1";
            
            // Aggiungi il mese se presente
            if (!empty($return_month)) {
                $successUrl .= "&month=" . $return_month;
            }
            
            header("Location: " . $successUrl);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => "Errore durante il salvataggio: " . $e->getMessage()
            ]);
        } else {
            // In caso di errore, mantieni comunque il mese
            $errorUrl = "../pages/gestione_lezioni.php?error=" . urlencode("Errore durante il salvataggio: " . $e->getMessage());
            if (!empty($return_month)) {
                $errorUrl .= "&month=" . $return_month;
            }
            header("Location: " . $errorUrl);
            exit();
        }
    }
}
?>