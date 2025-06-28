<?php
// This script generates student reports and exports them as CSV
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) {
    die('Accesso negato');
}

// Get parameters from both GET and POST
$students = [];
$period = '';
$month = null;
$year = null;
$periodo = '';

// Handle GET request (from JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $periodo = $_GET['periodo'] ?? '';
    $alunni = $_GET['alunni'] ?? '';
    
    if ($alunni) {
        $students = explode(',', $alunni);
    }
    
    // Parse periodo
    if (strpos($periodo, 'anno-') === 0) {
        $period = 'annual';
        $year = substr($periodo, 5);
    } else {
        $period = 'monthly';
        list($year, $month) = explode('-', $periodo);
    }
} else {
    // Handle POST request
    $students = $_POST['students'] ?? [];
    $period = $_POST['period'] ?? 'monthly';
    $month = $_POST['month'] ?? null;
    $year = $_POST['year'] ?? null;
    
    // Create periodo for filename
    if ($period === 'annual') {
        $periodo = 'anno-' . $year;
    } else {
        $periodo = $year . '-' . sprintf('%02d', $month);
    }
}

// Validate input
if (!is_array($students) || count($students) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No students selected']);
    exit;
}

if ($period === 'monthly' && (empty($month) || empty($year))) {
    http_response_code(400);
    echo json_encode(['error' => 'Month and year are required for monthly reports']);
    exit;
}

if ($period === 'annual' && empty($year)) {
    http_response_code(400);
    echo json_encode(['error' => 'Year is required for annual reports']);
    exit;
}

// Check if this is an export request
$isExport = isset($_GET['export']) && $_GET['export'] === '1';

if ($isExport) {
    // For export requests, prepare Excel HTML output
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="report_alunni_' . str_replace('-', '_', $periodo) . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Start HTML output for Excel
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        .student-name { background-color: #2196F3; color: white; font-size: 20px; padding: 10px; margin: 20px 0 10px 0; font-weight: bold; }
        .date-cell { background-color: #f0f0f0; font-weight: bold; white-space: nowrap; }
        .half-hour { background-color: #FFA500 !important; color: #000; }
        .summary-row { background-color: #e7f3ff; font-weight: bold; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>';
    
    $mesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    $periodoText = $period === 'annual' ? $year : $mesi[intval($month)] . " " . $year;
    
    echo '<h1>Report Lezioni Alunni - ' . $periodoText . '</h1>';
    echo '<p>Generato il: ' . date('d/m/Y H:i') . '</p>';
} else {
    // For GET requests, prepare JSON response headers
    header('Content-Type: application/json');
}

// Initialize data array for JSON response
$student_data = [];

// Prepare statement for fetching student name with surname
$name_stmt = $conn->prepare("SELECT CONCAT(nome, ' ', cognome) as nome_completo FROM alunni WHERE id = ?");

// Fetch and output data
foreach ($students as $index => $student_id) {
    // Validate student_id as integer
    $student_id = intval($student_id);
    if ($student_id <= 0) {
        continue;
    }

    // Fetch student name
    $name_stmt->bind_param('i', $student_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    $student_name = '';
    if ($name_row = $name_result->fetch_assoc()) {
        $student_name = $name_row['nome_completo'];
    } else {
        $student_name = "Unknown Student (ID: $student_id)";
    }

    if ($isExport) {
        // For CSV export, get individual lesson records
        if ($period === 'monthly') {
            $sql = "SELECT l.data, 
                           l.slot_orario,
                           CONCAT(t.nome, ' ', t.cognome) as tutor_name,
                           CASE WHEN l.durata = 1 THEN 0.5 ELSE 1 END as ore
                    FROM lezioni l 
                    JOIN lezioni_alunni la ON l.id = la.id_lezione 
                    JOIN tutor t ON l.id_tutor = t.id
                    WHERE la.id_alunno = ? AND MONTH(l.data) = ? AND YEAR(l.data) = ? 
                    ORDER BY l.data, l.slot_orario";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $student_id, $month, $year);
        } else {
            $sql = "SELECT l.data,
                           l.slot_orario,
                           CONCAT(t.nome, ' ', t.cognome) as tutor_name,
                           CASE WHEN l.durata = 1 THEN 0.5 ELSE 1 END as ore,
                           MONTH(l.data) as month,
                           YEAR(l.data) as year
                    FROM lezioni l 
                    JOIN lezioni_alunni la ON l.id = la.id_lezione 
                    JOIN tutor t ON l.id_tutor = t.id
                    WHERE la.id_alunno = ? AND YEAR(l.data) = ? 
                    ORDER BY l.data, l.slot_orario";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $student_id, $year);
        }
    } else {
        // For JSON response, get grouped data for display
        if ($period === 'monthly') {
            $sql = "SELECT l.data, 
                           COUNT(*) as lesson_count, 
                           SUM(CASE WHEN l.durata = 1 THEN 0.5 ELSE 1 END) as total_hours,
                           GROUP_CONCAT(
                               CONCAT(t.nome, ' ', t.cognome, ' (', 
                               CASE l.slot_orario 
                                   WHEN '15:30-16:30' THEN 'Slot 1'
                                   WHEN '16:30-17:30' THEN 'Slot 2' 
                                   WHEN '17:30-18:30' THEN 'Slot 3'
                                   ELSE l.slot_orario
                               END, ')')
                               ORDER BY l.slot_orario SEPARATOR ', '
                           ) as dettagli_lezioni
                    FROM lezioni l 
                    JOIN lezioni_alunni la ON l.id = la.id_lezione 
                    JOIN tutor t ON l.id_tutor = t.id
                    WHERE la.id_alunno = ? AND MONTH(l.data) = ? AND YEAR(l.data) = ? 
                    GROUP BY l.data ORDER BY l.data";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $student_id, $month, $year);
        } else {
            $sql = "SELECT MONTH(l.data) as month, 
                           YEAR(l.data) as year,
                           COUNT(*) as lesson_count, 
                           SUM(CASE WHEN l.durata = 1 THEN 0.5 ELSE 1 END) as total_hours,
                           COUNT(DISTINCT l.data) as giorni_lezione
                    FROM lezioni l 
                    JOIN lezioni_alunni la ON l.id = la.id_lezione 
                    WHERE la.id_alunno = ? AND YEAR(l.data) = ? 
                    GROUP BY MONTH(l.data), YEAR(l.data) ORDER BY MONTH(l.data)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $student_id, $year);
        }
    }

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        continue;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($isExport) {
        // For export requests, write HTML table
        echo '<div class="student-name">' . htmlspecialchars($student_name) . '</div>';
        
        if ($result->num_rows === 0) {
            echo '<p>Nessuna lezione trovata per questo periodo.</p>';
        } else {
            echo '<table>';
            echo '<tr>';
            echo '<th>Data</th>';
            echo '<th>Slot Orario</th>';
            echo '<th>Tutor</th>';
            echo '<th>Ore</th>';
            if ($period === 'annual') {
                echo '<th>Mese</th>';
            }
            echo '</tr>';
            
            $total_hours = 0;
            $total_lessons = 0;
            
            while ($row = $result->fetch_assoc()) {
                $formatted_date = date('d/m/Y', strtotime($row['data']));
                $class = ($row['ore'] == 0.5) ? 'half-hour' : '';
                
                echo '<tr class="' . $class . '">';
                echo '<td class="date-cell">' . $formatted_date . '</td>';
                echo '<td>' . htmlspecialchars($row['slot_orario']) . '</td>';
                echo '<td>' . htmlspecialchars($row['tutor_name']) . '</td>';
                echo '<td>' . $row['ore'];
                if ($row['ore'] == 0.5) {
                    echo '<br><small>(½ ora)</small>';
                }
                echo '</td>';
                
                if ($period === 'annual') {
                    $mesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
                    $month_name = $mesi[intval($row['month'])];
                    echo '<td>' . $month_name . '</td>';
                }
                echo '</tr>';
                
                $total_hours += floatval($row['ore']);
                $total_lessons++;
            }
            
            // Summary row
            echo '<tr class="summary-row">';
            $colspan = $period === 'annual' ? 5 : 4;
            echo '<td colspan="' . $colspan . '">';
            echo 'Totale lezioni: ' . $total_lessons . ' | Totale ore: ' . $total_hours;
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            
            // Add page break between students (except for the last one)
            if ($index < count($students) - 1) {
                echo '<div class="page-break"></div>';
            }
        }
    } else {
        // For JSON response, collect only total hours
        $total_hours = 0;
        
        while ($row = $result->fetch_assoc()) {
            $total_hours += floatval($row['total_hours']);
        }

        $student_data[] = [
            'id' => $student_id,
            'nome' => $student_name,
            'ore' => number_format($total_hours, 1)
        ];
    }
    $stmt->close();
}

$name_stmt->close();

// Return appropriate response based on request type
if ($isExport) {
    // For export requests, add legend and close HTML
    echo '<div style="margin-top: 40px; padding: 20px; background-color: #f0f0f0; border: 1px solid #ccc;">
        <h4>Legenda:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li><strong>Celle arancioni</strong> = Lezioni di mezz\'ora</li>
            <li>Le ore sono calcolate come: 1 ora per lezioni normali, 0.5 ore per lezioni di mezz\'ora</li>
        </ul>
    </div>';
    
    echo '</body></html>';
} else {
    // For JSON requests, return JSON with student data
    $mesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    $meseText = $period === 'annual' ? $year : $mesi[intval($month)] . " " . $year;
    
    echo json_encode([
        'success' => true,
        'mese' => $meseText,
        'data' => $student_data
    ]);
}

$conn->close();
?>
