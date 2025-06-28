<?php
require_once 'config.php';
session_start();

// Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: pages/login.php');
    exit;
}

// Verifica permessi - solo admin può vedere contabilità
if ($_SESSION['username'] !== 'alessandro') {
    header('Location: index.php');
    exit;
}

// Parametri temporali e vista
$vista = $_GET['vista'] ?? 'mensile'; // 'mensile' o 'annuale'
$anno = isset($_GET['anno']) ? (int)$_GET['anno'] : date('Y');
$mese = isset($_GET['mese']) ? (int)$_GET['mese'] : date('n');
$categoria_filter = $_GET['categoria'] ?? '';

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_movement') {
        $tipo = $_POST['tipo'];
        $importo = (float)$_POST['importo'];
        $categoria = $_POST['categoria'];
        $descrizione = $_POST['descrizione'];
        $metodo_pagamento = $_POST['metodo_pagamento'] ?: null;
        $data_movimento = $_POST['data_movimento'];
        $fattura_emessa = isset($_POST['fattura_emessa']) ? 1 : 0;
        
        $query = "INSERT INTO movimenti_contabili (tipo, importo, categoria, descrizione, metodo_pagamento, data_movimento, fattura_emessa) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sdssssi", $tipo, $importo, $categoria, $descrizione, $metodo_pagamento, $data_movimento, $fattura_emessa);
        $stmt->execute();
    }
}

// Recupera categorie uniche
$categorie = [];
$query = "SELECT DISTINCT categoria FROM movimenti_contabili WHERE categoria IS NOT NULL ORDER BY categoria";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $categorie[] = $row['categoria'];
}

// STATISTICHE PRINCIPALI (adattate per vista mensile/annuale)
$stats = [
    'entrate_periodo' => 0,
    'uscite_periodo' => 0,
    'entrate_anno' => 0,
    'uscite_anno' => 0,
    'entrate_alunni_periodo' => 0,
    'uscite_tutor_periodo' => 0,
    'entrate_contanti' => 0,
    'entrate_digitali' => 0,
    'uscite_contanti' => 0,
    'uscite_digitali' => 0
];

// Query base per periodo (mensile o annuale)
if ($vista === 'mensile') {
    // Entrate/Uscite del mese
    $query = "SELECT 
        SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
        SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite
        FROM movimenti_contabili 
        WHERE MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mese, $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['entrate_periodo'] = $row['entrate'] ?: 0;
        $stats['uscite_periodo'] = $row['uscite'] ?: 0;
    }
    
    // Entrate da alunni nel mese
    $query = "SELECT SUM(totale_pagato) as totale FROM pagamenti 
              WHERE MONTH(data_pagamento) = ? AND YEAR(data_pagamento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mese, $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['entrate_alunni_periodo'] = $row['totale'] ?: 0;
    }
    
    // Uscite tutor nel mese
    $query = "SELECT SUM(paga) as totale FROM pagamenti_tutor 
              WHERE stato = 1 AND MONTH(data_pagamento) = ? AND YEAR(data_pagamento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mese, $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['uscite_tutor_periodo'] = $row['totale'] ?: 0;
    }
    
    // Dettaglio entrate per metodo di pagamento mensile
    $query = "SELECT 
        SUM(CASE WHEN metodo_pagamento = 'contanti' THEN importo ELSE 0 END) as entrate_contanti,
        SUM(CASE WHEN metodo_pagamento IN ('bonifico', 'pos') THEN importo ELSE 0 END) as entrate_digitali
        FROM movimenti_contabili 
        WHERE tipo = 'entrata' AND MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mese, $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['entrate_contanti'] = $row['entrate_contanti'] ?: 0;
        $stats['entrate_digitali'] = $row['entrate_digitali'] ?: 0;
    }
    
    // Dettaglio uscite per metodo di pagamento mensile
    $query = "SELECT 
        SUM(CASE WHEN metodo_pagamento = 'contanti' THEN importo ELSE 0 END) as uscite_contanti,
        SUM(CASE WHEN metodo_pagamento IN ('bonifico', 'pos') THEN importo ELSE 0 END) as uscite_digitali
        FROM movimenti_contabili 
        WHERE tipo = 'uscita' AND MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mese, $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['uscite_contanti'] = $row['uscite_contanti'] ?: 0;
        $stats['uscite_digitali'] = $row['uscite_digitali'] ?: 0;
    }
    
    // Entrate/Uscite dell'anno (per confronto)
    $query = "SELECT 
        SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
        SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite
        FROM movimenti_contabili 
        WHERE YEAR(data_movimento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['entrate_anno'] = $row['entrate'] ?: 0;
        $stats['uscite_anno'] = $row['uscite'] ?: 0;
    }
    
} else { // Vista annuale
    // Entrate/Uscite dell'anno
    $query = "SELECT 
        SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
        SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite
        FROM movimenti_contabili 
        WHERE YEAR(data_movimento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['entrate_periodo'] = $row['entrate'] ?: 0;
        $stats['uscite_periodo'] = $row['uscite'] ?: 0;
        $stats['entrate_anno'] = $row['entrate'] ?: 0;
        $stats['uscite_anno'] = $row['uscite'] ?: 0;
    }
    
    // Entrate da alunni nell'anno
    $query = "SELECT SUM(totale_pagato) as totale FROM pagamenti 
              WHERE YEAR(data_pagamento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['entrate_alunni_periodo'] = $row['totale'] ?: 0;
    }
    
    // Uscite tutor nell'anno
    $query = "SELECT SUM(paga) as totale FROM pagamenti_tutor 
              WHERE stato = 1 AND YEAR(data_pagamento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['uscite_tutor_periodo'] = $row['totale'] ?: 0;
    }
    
    // Dettaglio entrate per metodo di pagamento annuale
    $query = "SELECT 
        SUM(CASE WHEN metodo_pagamento = 'contanti' THEN importo ELSE 0 END) as entrate_contanti,
        SUM(CASE WHEN metodo_pagamento IN ('bonifico', 'pos') THEN importo ELSE 0 END) as entrate_digitali
        FROM movimenti_contabili 
        WHERE tipo = 'entrata' AND YEAR(data_movimento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['entrate_contanti'] = $row['entrate_contanti'] ?: 0;
        $stats['entrate_digitali'] = $row['entrate_digitali'] ?: 0;
    }
    
    // Dettaglio uscite per metodo di pagamento annuale
    $query = "SELECT 
        SUM(CASE WHEN metodo_pagamento = 'contanti' THEN importo ELSE 0 END) as uscite_contanti,
        SUM(CASE WHEN metodo_pagamento IN ('bonifico', 'pos') THEN importo ELSE 0 END) as uscite_digitali
        FROM movimenti_contabili 
        WHERE tipo = 'uscita' AND YEAR(data_movimento) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $anno);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['uscite_contanti'] = $row['uscite_contanti'] ?: 0;
        $stats['uscite_digitali'] = $row['uscite_digitali'] ?: 0;
    }
}

// Recupera movimenti con filtri
// Recupera movimenti con filtri
$query = "SELECT m.*, 
          CASE 
            WHEN m.riferimento_tipo = 'alunno' THEN CONCAT(a.nome, ' ', a.cognome)
            WHEN m.riferimento_tipo = 'tutor' THEN CONCAT(t.nome, ' ', t.cognome)
            ELSE NULL
          END as riferimento_nome
          FROM movimenti_contabili m
          LEFT JOIN alunni a ON m.riferimento_tipo = 'alunno' AND m.riferimento_id = a.id
          LEFT JOIN tutor t ON m.riferimento_tipo = 'tutor' AND m.riferimento_id = t.id
          WHERE ";

if ($vista === 'mensile') {
    $query .= "MONTH(m.data_movimento) = ? AND YEAR(m.data_movimento) = ?";
    $params = [$mese, $anno];
    $types = "ii";
} else {
    $query .= "YEAR(m.data_movimento) = ?";
    $params = [$anno];
    $types = "i";
}

if ($categoria_filter) {
    $query .= " AND m.categoria = ?";
    $params[] = $categoria_filter;
    $types .= "s";
}

$query .= " ORDER BY m.data_movimento DESC, m.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$movimenti = [];
while ($row = $result->fetch_assoc()) {
    $movimenti[] = $row;
}

// Statistiche per categoria
// Statistiche per categoria
if ($vista === 'mensile') {
    $query = "SELECT categoria, 
              SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
              SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite
              FROM movimenti_contabili
              WHERE MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?
              GROUP BY categoria
              ORDER BY categoria";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mese, $anno);
} else {
    $query = "SELECT categoria, 
              SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
              SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite
              FROM movimenti_contabili
              WHERE YEAR(data_movimento) = ?
              GROUP BY categoria
              ORDER BY categoria";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $anno);
}

$stmt->execute();
$result = $stmt->get_result();

$stats_categorie = [];
while ($row = $result->fetch_assoc()) {
    $stats_categorie[] = $row;
}

// Funzione per i nomi dei mesi
function getNomeMese($numero) {
    $mesi = ['', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 
             'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
    return $mesi[$numero];
}

// Aggiungi queste query dopo le statistiche principali
// Dettaglio entrate per metodo di pagamento
$query = "SELECT 
    SUM(CASE WHEN metodo_pagamento = 'contanti' THEN importo ELSE 0 END) as entrate_contanti,
    SUM(CASE WHEN metodo_pagamento IN ('bonifico', 'pos') THEN importo ELSE 0 END) as entrate_digitali
    FROM movimenti_contabili 
    WHERE tipo = 'entrata' AND MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $mese, $anno);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['entrate_contanti'] = $row['entrate_contanti'] ?: 0;
    $stats['entrate_digitali'] = $row['entrate_digitali'] ?: 0;
}

// Dettaglio uscite per metodo di pagamento
$query = "SELECT 
    SUM(CASE WHEN metodo_pagamento = 'contanti' THEN importo ELSE 0 END) as uscite_contanti,
    SUM(CASE WHEN metodo_pagamento IN ('bonifico', 'pos') THEN importo ELSE 0 END) as uscite_digitali
    FROM movimenti_contabili 
    WHERE tipo = 'uscita' AND MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $mese, $anno);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['uscite_contanti'] = $row['uscite_contanti'] ?: 0;
    $stats['uscite_digitali'] = $row['uscite_digitali'] ?: 0;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilità - Gestione Doposcuola</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link href="assets/fontawesome/css/all.min.css" rel="stylesheet">
    <style>
        /* Stili specifici per contabilità */
        .accounting-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .date-filters {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .date-filters select {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            min-width: 120px;
        }
        
        /* Cards riassuntive */
        .accounting-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .stat-card.entrata {
            border-left: 4px solid #22c55e;
        }
        
        .stat-card.uscita {
            border-left: 4px solid #ef4444;
        }
        
        .stat-card.bilancio {
            border-left: 4px solid #3b82f6;
        }
        
        .stat-card h4 {
            color: #64748b;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .amount {
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card .amount.positive {
            color: #22c55e;
        }
        
        .stat-card .amount.negative {
            color: #ef4444;
        }
        
        .stat-card .amount.neutral {
            color: #3b82f6;
        }
        
        .stat-card .sub-info {
            font-size: 0.85em;
            color: #94a3b8;
        }
        
        /* Tabella movimenti */
        .movements-table {
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        
        .movements-table th {
            background: linear-gradient(135deg, rgba(102,126,234,0.08), rgba(118,75,162,0.08));
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9em;
            color: #2d3748;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .movements-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .movement-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .movement-type.entrata {
            background: #dcfce7;
            color: #166534;
        }
        
        .movement-type.uscita {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .category-tag {
            background: #f3f4f6;
            color: #374151;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            display: inline-block;
        }
        
        .payment-method {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #64748b;
            font-size: 0.9em;
        }
        
        .invoice-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 8px;
        }
        
        .invoice-indicator.yes {
            background: #22c55e;
            box-shadow: 0 0 4px rgba(34, 197, 94, 0.5);
        }
        
        .invoice-indicator.no {
            background: #f97316;
            box-shadow: 0 0 4px rgba(249, 115, 22, 0.5);
        }
        
        /* Sezione categorie */
        .categories-section {
            margin-top: 40px;
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .category-card {
            background: #f9fafb;
                        padding: 15px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .category-card h5 {
            color: #374151;
            font-size: 0.95em;
            margin-bottom: 10px;
        }
        
        .category-card .in {
            color: #22c55e;
            font-size: 0.9em;
        }
        
        .category-card .out {
            color: #ef4444;
            font-size: 0.9em;
        }
        
        .category-card .balance {
            font-weight: 600;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid #e5e7eb;
        }
        
        /* Pulsanti azione */
        .btn-add-movement {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-add-movement:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-sync {
            background: white;
            color: #3b82f6;
            padding: 10px 20px;
            border: 2px solid #3b82f6;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-sync:hover {
            background: #3b82f6;
            color: white;
        }
        
        .btn-export {
            background: white;
            color: #22c55e;
            padding: 10px 20px;
            border: 2px solid #22c55e;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-export:hover {
            background: #22c55e;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-edit-small {
            background: #f59e0b;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
        }
        
        .btn-delete-small {
            background: #ef4444;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
        }
        
        /* Modal form */
        .movement-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .movement-form-grid .full-width {
            grid-column: 1 / -1;
        }
        
        .tipo-selector {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .tipo-selector label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .tipo-selector input[type="radio"]:checked + label {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        @media (max-width: 768px) {
            .accounting-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .date-filters {
                flex-wrap: wrap;
            }
            
            .movement-form-grid {
                grid-template-columns: 1fr;
            }
            
            .movements-table {
                font-size: 0.9em;
            }
            
            .movements-table th, .movements-table td {
                padding: 10px;
            }
        }
        .payment-method-breakdown {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.method-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85em;
    color: #475569;
}

.method-item i {
    width: 20px;
    text-align: center;
    color: #64748b;
}

/* Selettore vista */
.view-selector {
    display: flex;
    gap: 5px;
    background: rgba(255, 255, 255, 0.7);
    padding: 4px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.view-btn {
    padding: 8px 16px;
    border: none;
    background: transparent;
    color: #64748b;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.9em;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.view-btn:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.view-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

/* Tabella riepilogo mensile */
.monthly-summary {
    margin-top: 40px;
    background: rgba(255, 255, 255, 0.95);
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
}

.monthly-summary h3 {
    margin-bottom: 20px;
    color: #2d3748;
    font-size: 1.2em;
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
}

.summary-table th {
    background: linear-gradient(135deg, rgba(102,126,234,0.08), rgba(118,75,162,0.08));
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.9em;
    color: #2d3748;
}

.summary-table td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.summary-table tfoot tr {
    background: #f8fafc;
    font-weight: 600;
}

.summary-table tfoot th {
    background: transparent;
        border-top: 2px solid #e2e8f0;
    padding-top: 15px;
}

    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/header.php'; ?>
    
    <main class="container">
        <div class="accounting-header">
            <h2><i class="fas fa-calculator"></i> Contabilità</h2>
            
            <div class="date-filters">
                <!-- Selettore Vista -->
                <div class="view-selector">
                    <button class="view-btn <?php echo $vista === 'mensile' ? 'active' : ''; ?>" 
                            onclick="cambiaVista('mensile')">
                        <i class="fas fa-calendar-alt"></i> Vista Mensile
                    </button>
                    <button class="view-btn <?php echo $vista === 'annuale' ? 'active' : ''; ?>" 
                            onclick="cambiaVista('annuale')">
                        <i class="fas fa-calendar"></i> Vista Annuale
                    </button>
                </div>
                
                <?php if($vista === 'mensile'): ?>
                <select id="filterMese" onchange="updateFilters()">
                    <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $mese ? 'selected' : ''; ?>>
                            <?php echo getNomeMese($m); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <?php endif; ?>

                <?php if($vista === 'annuale'): ?>
                        <!-- Riepilogo mensile per vista annuale -->
                        <div class="monthly-summary">
                            <h3><i class="fas fa-chart-bar"></i> Riepilogo Mensile <?php echo $anno; ?></h3>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        <th>Mese</th>
                                        <th>Entrate</th>
                                        <th>Uscite</th>
                                        <th>Bilancio</th>
                                        <th>Trend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totale_entrate_anno = 0;
                                    $totale_uscite_anno = 0;
                                    
                                    for($m = 1; $m <= 12; $m++):
                                        $query = "SELECT 
                                            SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
                                            SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite
                                            FROM movimenti_contabili 
                                            WHERE MONTH(data_movimento) = ? AND YEAR(data_movimento) = ?";
                                        $stmt = $conn->prepare($query);
                                        $stmt->bind_param("ii", $m, $anno);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $row = $result->fetch_assoc();
                                        
                                        $entrate_mese = $row['entrate'] ?: 0;
                                        $uscite_mese = $row['uscite'] ?: 0;
                                        $bilancio_mese = $entrate_mese - $uscite_mese;
                                        
                                        $totale_entrate_anno += $entrate_mese;
                                        $totale_uscite_anno += $uscite_mese;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo getNomeMese($m); ?></strong></td>
                                        <td class="positive">€<?php echo number_format($entrate_mese, 2); ?></td>
                                        <td class="negative">€<?php echo number_format($uscite_mese, 2); ?></td>
                                        <td class="<?php echo $bilancio_mese >= 0 ? 'positive' : 'negative'; ?>">
                                            €<?php echo number_format($bilancio_mese, 2); ?>
                                        </td>
                                        <td>
                                            <?php if($bilancio_mese > 0): ?>
                                                <i class="fas fa-arrow-up" style="color: #22c55e;"></i>
                                            <?php elseif($bilancio_mese < 0): ?>
                                                <i class="fas fa-arrow-down" style="color: #ef4444;"></i>
                                            <?php else: ?>
                                                <i class="fas fa-minus" style="color: #64748b;"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>TOTALE</th>
                                        <th class="positive">€<?php echo number_format($totale_entrate_anno, 2); ?></th>
                                        <th class="negative">€<?php echo number_format($totale_uscite_anno, 2); ?></th>
                                        <th class="<?php echo ($totale_entrate_anno - $totale_uscite_anno) >= 0 ? 'positive' : 'negative'; ?>">
                                            €<?php echo number_format($totale_entrate_anno - $totale_uscite_anno, 2); ?>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>
                
                <select id="filterAnno" onchange="updateFilters()">
                    <?php for($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $anno ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <select id="filterCategoria" onchange="updateFilters()">
                    <option value="">Tutte le categorie</option>
                    <?php foreach($categorie as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                                <?php echo $categoria_filter == $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="action-buttons">
                <button class="btn-add-movement" onclick="openAddMovementModal()">
                    <i class="fas fa-plus"></i> Nuovo Movimento
                </button>
                <button class="btn-sync" onclick="syncPayments()">
                    <i class="fas fa-sync"></i> Sincronizza
                </button>
                <button class="btn-export" onclick="exportData()">
                    <i class="fas fa-file-excel"></i> Esporta
                </button>
            </div>
        </div>
        
        <!-- Statistiche principali -->
        <!-- Statistiche principali con sotto-card -->
        <div class="accounting-stats">
            <div class="stat-card entrata">
                <h4>Entrate <?php echo $vista === 'mensile' ? getNomeMese($mese) . ' ' . $anno : 'Anno ' . $anno; ?></h4>
                <div class="amount positive">€<?php echo number_format($stats['entrate_periodo'], 2); ?></div>
                <div class="sub-info">
                    di cui €<?php echo number_format($stats['entrate_alunni_periodo'], 2); ?> da alunni
                </div>
                
                <!-- Sotto-card per metodi di pagamento -->
                <div class="payment-method-breakdown">
                    <div class="method-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Contanti: €<?php echo number_format($stats['entrate_contanti'], 2); ?></span>
                    </div>
                    <div class="method-item">
                        <i class="fas fa-credit-card"></i>
                        <span>Digitale: €<?php echo number_format($stats['entrate_digitali'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card uscita">
                <h4>Uscite <?php echo $vista === 'mensile' ? getNomeMese($mese) . ' ' . $anno : 'Anno ' . $anno; ?></h4>
                <div class="amount negative">€<?php echo number_format($stats['uscite_periodo'], 2); ?></div>
                <div class="sub-info">
                    di cui €<?php echo number_format($stats['uscite_tutor_periodo'], 2); ?> per tutor
                </div>
                
                <!-- Sotto-card per metodi di pagamento -->
                <div class="payment-method-breakdown">
                    <div class="method-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Contanti: €<?php echo number_format($stats['uscite_contanti'], 2); ?></span>
                    </div>
                    <div class="method-item">
                        <i class="fas fa-university"></i>
                        <span>Digitale: €<?php echo number_format($stats['uscite_digitali'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bilancio">
                <h4>Bilancio <?php echo $vista === 'mensile' ? getNomeMese($mese) . ' ' . $anno : 'Anno ' . $anno; ?></h4>
                <div class="amount <?php echo ($stats['entrate_periodo'] - $stats['uscite_periodo']) >= 0 ? 'positive' : 'negative'; ?>">
                    €<?php echo number_format($stats['entrate_periodo'] - $stats['uscite_periodo'], 2); ?>
                </div>
                <?php if($vista === 'mensile'): ?>
                <div class="sub-info">
                    Anno: €<?php echo number_format($stats['entrate_anno'] - $stats['uscite_anno'], 2); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tabella movimenti -->
        <table class="movements-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Importo</th>
                    <th>Categoria</th>
                    <th>Descrizione</th>
                    <th>Metodo</th>
                    <th>Fattura</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($movimenti as $mov): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($mov['data_movimento'])); ?></td>
                    <td>
                        <span class="movement-type <?php echo $mov['tipo']; ?>">
                            <i class="fas fa-<?php echo $mov['tipo'] == 'entrata' ? 'arrow-down' : 'arrow-up'; ?>"></i>
                            <?php echo ucfirst($mov['tipo']); ?>
                        </span>
                    </td>
                    <td style="font-weight: 600; color: <?php echo $mov['tipo'] == 'entrata' ? '#22c55e' : '#ef4444'; ?>">
                        €<?php echo number_format($mov['importo'], 2); ?>
                    </td>
                    <td>
                        <span class="category-tag"><?php echo htmlspecialchars($mov['categoria'] ?: 'Non categorizzato'); ?></span>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($mov['descrizione']); ?>
                        <?php if($mov['riferimento_nome']): ?>
                            <br><small class="text-muted">Rif: <?php echo htmlspecialchars($mov['riferimento_nome']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($mov['metodo_pagamento']): ?>
                            <span class="payment-method">
                                <i class="fas fa-<?php 
                                    echo $mov['metodo_pagamento'] == 'bonifico' ? 'university' : 
                                        ($mov['metodo_pagamento'] == 'contanti' ? 'money-bill-wave' : 
                                        ($mov['metodo_pagamento'] == 'pos' ? 'credit-card' : 'question')); 
                                ?>"></i>
                                <?php echo ucfirst($mov['metodo_pagamento']); ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="invoice-indicator <?php echo $mov['fattura_emessa'] ? 'yes' : 'no'; ?>" 
                              title="<?php echo $mov['fattura_emessa'] ? 'Fattura emessa' : 'Fattura non emessa'; ?>"></span>
                    </td>
                    <td>
                        <button class="btn-edit-small" onclick="editMovement(<?php echo $mov['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete-small" onclick="deleteMovement(<?php echo $mov['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Riepilogo per categoria -->
        <div class="categories-section">
            <h3><i class="fas fa-chart-pie"></i> Riepilogo per Categoria</h3>
            <div class="categories-grid">
                <?php foreach($stats_categorie as $cat_stat): ?>
                <div class="category-card">
                    <h5><?php echo htmlspecialchars($cat_stat['categoria'] ?: 'Non categorizzato'); ?></h5>
                    <div class="in">↓ €<?php echo number_format($cat_stat['entrate'], 2); ?></div>
                    <div class="out">↑ €<?php echo number_format($cat_stat['uscite'], 2); ?></div>
                    <div class="balance <?php echo ($cat_stat['entrate'] - $cat_stat['uscite']) >= 0 ? 'positive' : 'negative'; ?>">
                        €<?php echo number_format($cat_stat['entrate'] - $cat_stat['uscite'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <!-- Modale Aggiungi Movimento -->
    <div id="addMovementModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addMovementModal')">&times;</span>
            <h2>Nuovo Movimento Contabile</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_movement">
                
                <div class="tipo-selector">
                    <div>
                        <input type="radio" name="tipo" id="tipo_entrata" value="entrata" checked>
                        <label for="tipo_entrata">
                            <i class="fas fa-arrow-down" style="color: #22c55e;"></i> Entrata
                        </label>
                    </div>
                    <div>
                        <input type="radio" name="tipo" id="tipo_uscita" value="uscita">
                        <label for="tipo_uscita">
                            <i class="fas fa-arrow-up" style="color: #ef4444;"></i> Uscita
                                               </label>
                    </div>
                </div>
                
                <div class="movement-form-grid">
                    <div class="form-group">
                        <label for="importo">Importo (€)*</label>
                        <input type="number" name="importo" id="importo" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_movimento">Data*</label>
                        <input type="date" name="data_movimento" id="data_movimento" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria">Categoria*</label>
                        <input type="text" name="categoria" id="categoria" list="categorie_list" required>
                        <datalist id="categorie_list">
                            <?php foreach($categorie as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                            <option value="Affitto">
                            <option value="Utenze">
                            <option value="Materiali">
                            <option value="Stipendi">
                            <option value="Tasse">
                            <option value="Altro">
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label for="metodo_pagamento">Metodo Pagamento</label>
                        <select name="metodo_pagamento" id="metodo_pagamento">
                            <option value="">Seleziona...</option>
                            <option value="bonifico">Bonifico</option>
                            <option value="contanti">Contanti</option>
                            <option value="pos">POS</option>
                            <option value="altro">Altro</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="descrizione">Descrizione*</label>
                        <textarea name="descrizione" id="descrizione" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="fattura_emessa" id="fattura_emessa">
                            Fattura emessa
                        </label>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Salva Movimento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale Modifica Movimento -->
<div id="editMovementModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editMovementModal')">&times;</span>
        <h2>Modifica Movimento Contabile</h2>
        
        <form id="editMovementForm">
            <input type="hidden" id="edit_movement_id" name="movement_id">
            
            <div class="tipo-selector">
                <div>
                    <input type="radio" name="edit_tipo" id="edit_tipo_entrata" value="entrata">
                    <label for="edit_tipo_entrata">
                        <i class="fas fa-arrow-down" style="color: #22c55e;"></i> Entrata
                    </label>
                </div>
                <div>
                    <input type="radio" name="edit_tipo" id="edit_tipo_uscita" value="uscita">
                    <label for="edit_tipo_uscita">
                        <i class="fas fa-arrow-up" style="color: #ef4444;"></i> Uscita
                    </label>
                </div>
            </div>
            
            <div class="movement-form-grid">
                <div class="form-group">
                    <label for="edit_importo">Importo (€)*</label>
                    <input type="number" name="importo" id="edit_importo" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_data_movimento">Data*</label>
                    <input type="date" name="data_movimento" id="edit_data_movimento" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_categoria">Categoria*</label>
                    <input type="text" name="categoria" id="edit_categoria" list="categorie_list" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_metodo_pagamento">Metodo Pagamento</label>
                    <select name="metodo_pagamento" id="edit_metodo_pagamento">
                        <option value="">Seleziona...</option>
                        <option value="bonifico">Bonifico</option>                        
                        <option value="contanti">Contanti</option>
                        <option value="pos">POS</option>
                        <option value="altro">Altro</option>
                    </select>
                </div>
                
                <div class="form-group full-width">
                    <label for="edit_descrizione">Descrizione*</label>
                    <textarea name="descrizione" id="edit_descrizione" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="fattura_emessa" id="edit_fattura_emessa">
                        Fattura emessa
                    </label>
                </div>
            </div>
            
            <div class="form-submit">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Salva Modifiche
                </button>
            </div>
        </form>
    </div>
</div>
    
    <script>
        // Filtri
        function updateFilters() {
            const mese = document.getElementById('filterMese').value;
            const anno = document.getElementById('filterAnno').value;
            const categoria = document.getElementById('filterCategoria').value;
            
            let url = `contabilita.php?mese=${mese}&anno=${anno}`;
            if (categoria) url += `&categoria=${encodeURIComponent(categoria)}`;
            
            window.location.href = url;
        }
        
        // Apertura modale
        function openAddMovementModal() {
            document.getElementById('addMovementModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Sincronizza pagamenti automatici
        function syncPayments() {
            if (!confirm('Vuoi sincronizzare i pagamenti di alunni e tutor non ancora registrati in contabilità?')) {
                return;
            }
            
            fetch('scripts/sync_payments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    mese: <?php echo $mese; ?>,
                    anno: <?php echo $anno; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Sincronizzazione completata!\n${data.alunni_sincronizzati} pagamenti alunni\n${data.tutor_sincronizzati} pagamenti tutor`);
                    location.reload();
                } else {
                    alert('Errore nella sincronizzazione: ' + data.message);
                }
            });
        }
        
        // Esporta dati
        function exportData() {
            const mese = document.getElementById('filterMese').value;
            const anno = document.getElementById('filterAnno').value;
            window.location.href = `scripts/export_contabilita.php?mese=${mese}&anno=${anno}`;
        }
        
        // Modifica movimento
        function editMovement(id) {
            // Implementare la logica di modifica
            alert('Funzione di modifica in sviluppo');
        }
        
        // Elimina movimento
        function deleteMovement(id) {
            if (confirm('Sei sicuro di voler eliminare questo movimento?')) {
                fetch('scripts/delete_movement.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ movement_id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Movimento eliminato con successo!');
                        location.reload();
                    } else {
                        alert('Errore: ' + data.message);
                    }
                });
            }
        }
        
        // Chiudi modali cliccando fuori
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Modifica movimento
function editMovement(id) {
    // Recupera i dati del movimento
    fetch('scripts/get_movement.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const mov = data.movement;
                
                // Popola il form
                document.getElementById('edit_movement_id').value = mov.id;
                document.getElementById('edit_importo').value = mov.importo;
                document.getElementById('edit_data_movimento').value = mov.data_movimento;
                document.getElementById('edit_categoria').value = mov.categoria;
                document.getElementById('edit_metodo_pagamento').value = mov.metodo_pagamento || '';
                document.getElementById('edit_descrizione').value = mov.descrizione;
                document.getElementById('edit_fattura_emessa').checked = mov.fattura_emessa == 1;
                
                // Seleziona il tipo corretto
                if (mov.tipo === 'entrata') {
                    document.getElementById('edit_tipo_entrata').checked = true;
                } else {
                    document.getElementById('edit_tipo_uscita').checked = true;
                }
                
                // Apri il modale
                document.getElementById('editMovementModal').style.display = 'block';
            } else {
                alert('Errore nel caricamento dei dati');
            }
        });
}

// Gestione form modifica
document.getElementById('editMovementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const tipo = document.querySelector('input[name="edit_tipo"]:checked').value;
    formData.append('tipo', tipo);
    
    fetch('scripts/update_movement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Movimento aggiornato con successo!');
            closeModal('editMovementModal');
            location.reload();
        } else {
            alert('Errore: ' + data.message);
        }
    });
});

// Aggiungi la funzione per cambiare vista
function cambiaVista(nuovaVista) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('vista', nuovaVista);
    
    // Se passiamo a vista annuale, rimuovi il parametro mese
    if (nuovaVista === 'annuale') {
        urlParams.delete('mese');
    } else {
        // Se torniamo a vista mensile, aggiungi il mese corrente
        urlParams.set('mese', <?php echo date('n'); ?>);
    }
    
    window.location.href = 'contabilita.php?' + urlParams.toString();
}

// Modifica la funzione updateFilters
function updateFilters() {
    const vista = '<?php echo $vista; ?>';
    const anno = document.getElementById('filterAnno').value;
    const categoria = document.getElementById('filterCategoria').value;
    
    let url = `contabilita.php?vista=${vista}&anno=${anno}`;
    
    if (vista === 'mensile') {
        const mese = document.getElementById('filterMese').value;
        url += `&mese=${mese}`;
    }
    
    if (categoria) {
        url += `&categoria=${encodeURIComponent(categoria)}`;
    }
    
    window.location.href = url;
}

// Modifica la funzione syncPayments per gestire la vista
function syncPayments() {
    const vista = '<?php echo $vista; ?>';
    let message = vista === 'mensile' 
        ? 'Vuoi sincronizzare i pagamenti del mese selezionato?' 
        : 'Vuoi sincronizzare tutti i pagamenti dell\'anno selezionato?';
    
    if (!confirm(message)) {
        return;
    }
    
    const requestData = {
        anno: <?php echo $anno; ?>
    };
    
    if (vista === 'mensile') {
        requestData.mese = <?php echo $mese; ?>;
    }
    
    fetch('scripts/sync_payments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Sincronizzazione completata!\n${data.alunni_sincronizzati} pagamenti alunni\n${data.tutor_sincronizzati} pagamenti tutor`);
            location.reload();
        } else {
            alert('Errore nella sincronizzazione: ' + data.message);
        }
    });
}

// Modifica exportData per includere la vista
function exportData() {
    const vista = '<?php echo $vista; ?>';
    const anno = document.getElementById('filterAnno').value;
    
    let url = `scripts/export_contabilita.php?vista=${vista}&anno=${anno}`;
    
    if (vista === 'mensile') {
        const mese = document.getElementById('filterMese').value;
        url += `&mese=${mese}`;
    }
    
    window.location.href = url;
}
    </script>
</body>
</html>