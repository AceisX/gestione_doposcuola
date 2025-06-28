<?php
// dashboard.php - Dashboard Analytics Avanzata
session_start();
require_once 'config.php';

// Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: pages/login.php');
    exit;
}

// Classe per gestire le statistiche della dashboard
class DashboardStats {
    private $conn;
    private $anno;
    private $mese;
    
    public function __construct($conn, $anno = null, $mese = null) {
        $this->conn = $conn;
        $this->anno = $anno ?: date('Y');
        $this->mese = $mese ?: date('n');
    }
    
    // Ottieni statistiche generali
    public function getGeneralStats() {
        $stats = [
            'alunni_totali' => 0,
            'alunni_attivi' => 0,
            'alunni_nuovi_mese' => 0,
            'tutor_totali' => 0,
            'tutor_attivi_mese' => 0,
            'lezioni_mese' => 0,
            'ore_erogate_mese' => 0,
            'tasso_occupazione' => 0,
            'media_alunni_lezione' => 0
        ];
        
        // Alunni
        $query = "SELECT 
                    COUNT(*) as totale, 
                    SUM(stato = 'attivo') as attivi,
                    SUM(MONTH(data_iscrizione) = ? AND YEAR(data_iscrizione) = ? AND stato = 'attivo') as nuovi_mese
                  FROM alunni";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->mese, $this->anno);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['alunni_totali'] = $row['totale'];
            $stats['alunni_attivi'] = $row['attivi'];
            $stats['alunni_nuovi_mese'] = $row['nuovi_mese'];
        }
        
        // Tutor
        $query = "SELECT 
                    COUNT(DISTINCT t.id) as totali,
                    COUNT(DISTINCT l.id_tutor) as attivi_mese
                  FROM tutor t
                  LEFT JOIN lezioni l ON t.id = l.id_tutor 
                    AND MONTH(l.data) = ? AND YEAR(l.data) = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->mese, $this->anno);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['tutor_totali'] = $row['totali'];
            $stats['tutor_attivi_mese'] = $row['attivi_mese'];
        }
        
        // Lezioni e statistiche avanzate
        $query = "SELECT 
                    COUNT(DISTINCT l.id) as lezioni,
                    SUM(CASE WHEN l.durata = 0 THEN 1 ELSE 0.5 END) as ore,
                    AVG(alunni_per_lezione.count) as media_alunni
                  FROM lezioni l
                  LEFT JOIN (
                    SELECT id_lezione, COUNT(*) as count
                    FROM lezioni_alunni
                    GROUP BY id_lezione
                  ) alunni_per_lezione ON l.id = alunni_per_lezione.id_lezione
                  WHERE MONTH(l.data) = ? AND YEAR(l.data) = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->mese, $this->anno);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['lezioni_mese'] = $row['lezioni'];
            $stats['ore_erogate_mese'] = $row['ore'];
            $stats['media_alunni_lezione'] = round($row['media_alunni'] ?: 0, 1);
        }
        
        // Calcola tasso di occupazione (assumendo 3 slot x 20 giorni lavorativi)
        $slot_disponibili = 3 * 20 * $stats['tutor_totali'];
        if ($slot_disponibili > 0) {
            $stats['tasso_occupazione'] = round(($stats['lezioni_mese'] / $slot_disponibili) * 100, 1);
        }
        
        return $stats;
    }
    
    // Ottieni statistiche finanziarie dettagliate
    public function getFinancialStats() {
        $stats = [
            'entrate_mese' => 0,
            'uscite_mese' => 0,
            'entrate_anno' => 0,
            'uscite_anno' => 0,
            'previsione_mese' => 0,
            'pagamenti_in_sospeso' => 0,
            'tasso_morosita' => 0
        ];
        
        // Entrate mese corrente
        $query = "SELECT 
                    COALESCE(SUM(totale_pagato), 0) as totale,
                    COUNT(DISTINCT id_alunno) as alunni_paganti
                  FROM pagamenti 
                  WHERE MONTH(data_pagamento) = ? AND YEAR(data_pagamento) = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->mese, $this->anno);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['entrate_mese'] = $row['totale'];
        $alunni_paganti = $row['alunni_paganti'];
        
        // Calcola pagamenti in sospeso e tasso morosità (SOLO PACCHETTI MENSILI)
$query = "SELECT 
            COUNT(DISTINCT a.id) as alunni_mensili_attivi,
            COALESCE(SUM(CASE 
                WHEN NOT EXISTS (
                    SELECT 1 FROM pagamenti p 
                    WHERE p.id_alunno = a.id 
                    AND MONTH(p.data_pagamento) = ? 
                    AND YEAR(p.data_pagamento) = ?
                ) THEN a.prezzo_finale 
                ELSE 0 
            END), 0) as totale_dovuto,
            COUNT(DISTINCT CASE 
                WHEN EXISTS (
                    SELECT 1 FROM pagamenti p 
                    WHERE p.id_alunno = a.id 
                    AND MONTH(p.data_pagamento) = ? 
                    AND YEAR(p.data_pagamento) = ?
                ) THEN a.id 
            END) as alunni_paganti
          FROM alunni a
          JOIN pacchetti pac ON a.id_pacchetto = pac.id
          WHERE a.stato = 'attivo'
          AND pac.tipo = 'mensile'";

$stmt = $this->conn->prepare($query);
$stmt->bind_param("iiii", $this->mese, $this->anno, $this->mese, $this->anno);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Ora usiamo direttamente i valori dalla query
$stats['pagamenti_in_sospeso'] = $row['totale_dovuto'];

// Calcola tasso morosità
$alunni_mensili_non_paganti = $row['alunni_mensili_attivi'] - $row['alunni_paganti'];
if ($row['alunni_mensili_attivi'] > 0) {
    $stats['tasso_morosita'] = round(($alunni_mensili_non_paganti / $row['alunni_mensili_attivi']) * 100, 1);
} else {
    $stats['tasso_morosita'] = 0;
}

            // Se vuoi anche il totale generale degli alunni paganti (per altre statistiche)
            $query_totale = "SELECT COUNT(DISTINCT id_alunno) as totale_paganti
                            FROM pagamenti 
                            WHERE MONTH(data_pagamento) = ? AND YEAR(data_pagamento) = ?";
            $stmt_tot = $this->conn->prepare($query_totale);
            $stmt_tot->bind_param("ii", $this->mese, $this->anno);
            $stmt_tot->execute();
            $result_tot = $stmt_tot->get_result();
            $alunni_paganti_totali = $result_tot->fetch_assoc()['totale_paganti'];
        // Previsione entrate mese (basata su alunni attivi)
        $query = "SELECT COALESCE(SUM(prezzo_finale), 0) as previsione
                  FROM alunni 
                  WHERE stato = 'attivo'";
        $result = $this->conn->query($query);
        $stats['previsione_mese'] = $result->fetch_assoc()['previsione'];
        
        // Uscite (pagamenti tutor)
        $query = "SELECT COALESCE(SUM(paga), 0) as totale 
                  FROM pagamenti_tutor 
                  WHERE stato = 1 AND MONTH(data_pagamento) = ? AND YEAR(data_pagamento) = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->mese, $this->anno);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['uscite_mese'] = $result->fetch_assoc()['totale'];
        
        // Totali annuali
        $query = "SELECT 
                    (SELECT COALESCE(SUM(totale_pagato), 0) FROM pagamenti WHERE YEAR(data_pagamento) = ?) as entrate,
                    (SELECT COALESCE(SUM(paga), 0) FROM pagamenti_tutor WHERE stato = 1 AND YEAR(data_pagamento) = ?) as uscite";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->anno, $this->anno);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['entrate_anno'] = $row['entrate'];
        $stats['uscite_anno'] = $row['uscite'];
        
        return $stats;
    }
    
    // Ottieni dati per i grafici
    public function getChartData() {
        $data = [];
        
        // Entrate vs Uscite mensili
        $data['monthly_comparison'] = $this->getMonthlyComparison();
        
        // Distribuzione pacchetti
        $data['package_distribution'] = $this->getPackageDistribution();
        
        // Trend orario lezioni
        $data['lesson_hours_trend'] = $this->getLessonHoursTrend();
        
        // Performance tutor
        $data['tutor_performance'] = $this->getTutorPerformance();
        
        // Previsioni
        $data['forecasts'] = $this->getForecasts();
        
        return $data;
    }
    
    private function getMonthlyComparison() {
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $query = "SELECT 
                        COALESCE(SUM(p.totale_pagato), 0) as entrate,
                        COALESCE((SELECT SUM(paga) FROM pagamenti_tutor 
                                 WHERE stato = 1 AND MONTH(data_pagamento) = ? 
                                 AND YEAR(data_pagamento) = ?), 0) as uscite
                      FROM pagamenti p
                      WHERE MONTH(p.data_pagamento) = ? AND YEAR(p.data_pagamento) = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iiii", $i, $this->anno, $i, $this->anno);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $data[] = [
                'mese' => $i,
                'entrate' => $row['entrate'],
                'uscite' => $row['uscite'],
                'utile' => $row['entrate'] - $row['uscite']
            ];
        }
        return $data;
    }
    
    private function getPackageDistribution() {
        $query = "SELECT 
                    p.nome, 
                    p.tipo,
                    COUNT(a.id) as count,
                    SUM(a.prezzo_finale) as valore_totale
                  FROM pacchetti p
                  LEFT JOIN alunni a ON p.id = a.id_pacchetto AND a.stato = 'attivo'
                  GROUP BY p.id
                  HAVING count > 0
                  ORDER BY count DESC";
        $result = $this->conn->query($query);
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
        private function getLessonHoursTrend() {
        $data = [];
        
        // Ultimi 12 mesi
        for ($i = 11; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-$i months");
            $mese = $date->format('n');
            $anno = $date->format('Y');
            
            $query = "SELECT 
                        COUNT(DISTINCT l.id) as lezioni,
                        SUM(CASE WHEN l.durata = 0 THEN 1 ELSE 0.5 END) as ore,
                        COUNT(DISTINCT l.id_tutor) as tutor_attivi,
                        COUNT(DISTINCT la.id_alunno) as alunni_serviti
                      FROM lezioni l
                      LEFT JOIN lezioni_alunni la ON l.id = la.id_lezione
                      WHERE MONTH(l.data) = ? AND YEAR(l.data) = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $mese, $anno);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $data[] = [
                'periodo' => $date->format('M Y'),
                'lezioni' => $row['lezioni'] ?: 0,
                'ore' => $row['ore'] ?: 0,
                'tutor_attivi' => $row['tutor_attivi'] ?: 0,
                'alunni_serviti' => $row['alunni_serviti'] ?: 0
            ];
        }
        
        return $data;
    }
    
    private function getTutorPerformance() {
    $query = "SELECT 
                t.id,
                CONCAT(t.nome, ' ', t.cognome) as nome,
                -- Conta solo le lezioni del mese selezionato
                COUNT(DISTINCT CASE 
                    WHEN YEAR(l.data) = ? AND MONTH(l.data) = ? 
                    THEN l.id 
                    ELSE NULL 
                END) as lezioni_totali,
                
                -- Conta lezioni singole del mese
                SUM(CASE 
                    WHEN l.tipo = 'singolo' AND YEAR(l.data) = ? AND MONTH(l.data) = ? 
                    THEN 1 
                    ELSE 0 
                END) as lezioni_singole,
                
                -- Conta lezioni gruppo del mese
                SUM(CASE 
                    WHEN l.tipo = 'gruppo' AND YEAR(l.data) = ? AND MONTH(l.data) = ? 
                    THEN 1 
                    ELSE 0 
                END) as lezioni_gruppo,
                
                -- Calcola ore totali effettive del mese
                SUM(CASE 
                    WHEN YEAR(l.data) = ? AND MONTH(l.data) = ? 
                    THEN (CASE WHEN l.durata = 0 THEN 1 ELSE 0.5 END)
                    ELSE 0 
                END) as ore_effettive,
                
                -- Conta alunni unici del mese
                COUNT(DISTINCT CASE 
                    WHEN YEAR(l.data) = ? AND MONTH(l.data) = ? 
                    THEN la.id_alunno 
                    ELSE NULL 
                END) as alunni_unici,
                
                -- Conta totale alunni per tutte le lezioni del mese
                (SELECT COUNT(*) 
                 FROM lezioni_alunni la2 
                 JOIN lezioni l2 ON la2.id_lezione = l2.id 
                 WHERE l2.id_tutor = t.id 
                 AND YEAR(l2.data) = ? 
                 AND MONTH(l2.data) = ?) as totale_presenze,
                
                COALESCE(vt.valutazione, 0) as rating,
                COALESCE(pt.paga, 0) as guadagno_totale
                
              FROM tutor t
              LEFT JOIN lezioni l ON t.id = l.id_tutor
              LEFT JOIN lezioni_alunni la ON l.id = la.id_lezione
              LEFT JOIN valutazioni_tutor vt ON t.id = vt.tutor_id
              LEFT JOIN pagamenti_tutor pt ON t.id = pt.tutor_id 
                AND YEAR(pt.mensilita) = ? 
                AND MONTH(pt.mensilita) = ?
                AND pt.stato = 1
              GROUP BY t.id
              HAVING lezioni_totali > 0 OR guadagno_totale > 0
              ORDER BY lezioni_totali DESC";
    
    // Conta i parametri: sono 14 (7 coppie di anno/mese)
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("iiiiiiiiiiiiii",  // 14 "i"
        $this->anno, $this->mese,  // per lezioni_totali
        $this->anno, $this->mese,  // per lezioni_singole
        $this->anno, $this->mese,  // per lezioni_gruppo
        $this->anno, $this->mese,  // per ore_effettive
        $this->anno, $this->mese,  // per alunni_unici
        $this->anno, $this->mese,  // per totale_presenze (subquery)
        $this->anno, $this->mese   // per pagamenti_tutor JOIN
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Calcola efficienza: media alunni per lezione (non per ora!)
        $row['efficienza'] = $row['lezioni_totali'] > 0 
            ? round($row['totale_presenze'] / $row['lezioni_totali'], 2) 
            : 0;
        
        // Calcola €/ora (non €/lezione)
        $row['euro_ora'] = $row['ore_effettive'] > 0
            ? round($row['guadagno_totale'] / $row['ore_effettive'], 2)
            : 0;
            
        // Revenue per lezione è diverso da €/ora
        $row['revenue_per_lezione'] = $row['lezioni_totali'] > 0
            ? round($row['guadagno_totale'] / $row['lezioni_totali'], 2)
            : 0;
            
        $data[] = $row;
    }
    
    return $data;
}
    private function getForecasts() {
        // Previsione basata su trend storico
        $query = "SELECT 
                    AVG(totale) as media_mensile,
                    STDDEV(totale) as deviazione
                  FROM (
                    SELECT SUM(totale_pagato) as totale
                    FROM pagamenti
                    WHERE data_pagamento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY YEAR(data_pagamento), MONTH(data_pagamento)
                  ) as monthly_totals";
        
        $result = $this->conn->query($query);
        $stats = $result->fetch_assoc();
        
        return [
            'prossimo_mese' => [
                'previsto' => $stats['media_mensile'],
                'min' => $stats['media_mensile'] - $stats['deviazione'],
                'max' => $stats['media_mensile'] + $stats['deviazione']
            ],
            'trend' => $this->calculateTrend()
        ];
    }
    
    private function calculateTrend() {
        // Calcola trend usando regressione lineare semplice
        $query = "SELECT 
                    MONTH(data_pagamento) as x,
                    SUM(totale_pagato) as y
                  FROM pagamenti
                  WHERE data_pagamento >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  GROUP BY YEAR(data_pagamento), MONTH(data_pagamento)
                  ORDER BY data_pagamento";
        
        $result = $this->conn->query($query);
        
        $x_values = [];
        $y_values = [];
        $i = 1;
        
        while ($row = $result->fetch_assoc()) {
            $x_values[] = $i++;
            $y_values[] = $row['y'];
        }
        
        if (count($x_values) < 2) return 'stabile';
        
        // Calcola coefficiente di correlazione
        $n = count($x_values);
        $sum_x = array_sum($x_values);
        $sum_y = array_sum($y_values);
        $sum_xy = 0;
        $sum_x2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sum_xy += $x_values[$i] * $y_values[$i];
            $sum_x2 += $x_values[$i] * $x_values[$i];
        }
        
        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
        
        if ($slope > 50) return 'crescita';
        if ($slope < -50) return 'decrescita';
        return 'stabile';
    }
}

// Parametri dalla richiesta
$anno_corrente = isset($_GET['anno']) ? (int)$_GET['anno'] : date('Y');
$mese_corrente = isset($_GET['mese']) ? (int)$_GET['mese'] : date('n');
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'generale';

// Inizializza classe statistiche
$dashboard = new DashboardStats($conn, $anno_corrente, $mese_corrente);

// Ottieni dati
$general_stats = $dashboard->getGeneralStats();
$financial_stats = $dashboard->getFinancialStats();
$chart_data = $dashboard->getChartData();

// Funzioni helper
function formatCurrency($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

function formatNumber($num) {
    return number_format($num, 0, ',', '.');
}

function getStatusClass($value, $threshold = 0) {
    if ($value > $threshold) return 'positive';
    if ($value < $threshold) return 'negative';
    return 'neutral';
}

// Array di tooltip per le metriche
$tooltips = [
    'alunni_attivi' => 'Numero totale di alunni con stato attivo nel sistema',
    'entrate_mese' => 'Totale dei pagamenti ricevuti nel mese corrente',
    'pagamenti_sospeso' => 'Importo totale dei pagamenti non ancora ricevuti dagli alunni attivi',
    'tasso_occupazione' => 'Percentuale di slot orari utilizzati rispetto al totale disponibile',
    'ore_erogate' => 'Somma delle ore di lezione erogate nel mese (1h = lezione intera, 0.5h = mezza lezione)',
    'kpi_score' => 'Rapporto tra entrate generate e costo del tutor (>100% = profittevole)',
    'efficienza' => 'Numero medio di alunni gestiti per ora di lezione',
    'tasso_morosita' => 'Percentuale di alunni che non hanno ancora pagato questo mese',
    'media_alunni' => 'Numero medio di alunni presenti per lezione',
    'margine' => 'Percentuale di profitto calcolata su entrate totali'
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analytics</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/dashboard.css">
    <link href="assets/fontawesome/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
/* Fix per i tooltip */
.kpi-card {
    overflow: visible !important;
}

.kpi-content {
    overflow: visible !important;
}

.kpi-label {
    overflow: visible !important;
    position: relative;
    z-index: 100;
}

/* Assicura che il tooltip sia sopra tutto */
.tooltip-box {
    z-index: 10000 !important;
    min-width: 220px;
    text-align: center;
    line-height: 1.4;
    font-weight: normal;
    text-transform: none;
    letter-spacing: normal;
}

/* Posizionamento alternativo per card a destra */
.kpi-card:nth-child(3) .tooltip-box,
.kpi-card:nth-child(4) .tooltip-box {
    left: auto !important;
    right: 0 !important;
    transform: translateX(0) !important;
}
</style>
    
</head>


<style>
.rating-stars .star {
    cursor: pointer !important;
    user-select: none;
}
.rating-stars .star:active {
    transform: scale(0.9);
}
</style>
<body>
    <?php include __DIR__ . '/assets/header.php'; ?>
    
    <main class="dashboard-container">
        <!-- Header Dashboard -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1><i class="fas fa-chart-line"></i> Dashboard Analytics</h1>
                <p class="dashboard-subtitle">Monitoraggio in tempo reale delle performance</p>
            </div>
            
            <!-- Filtri -->
            <div class="dashboard-filters">
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> Periodo</label>
                    <div class="filter-controls">
                        <select id="anno" onchange="updateDashboard()">
                            <?php for($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $anno_corrente ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select id="mese" onchange="updateDashboard()">
                            <?php 
                            $mesi = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 
                                    'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
                            for($m = 1; $m <= 12; $m++): 
                            ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $mese_corrente ? 'selected' : ''; ?>>
                                    <?php echo $mesi[$m-1]; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Switch Vista -->
                <div class="view-switcher">
                    <button class="view-btn <?php echo $vista == 'generale' ? 'active' : ''; ?>" 
                            onclick="switchView('generale')">
                        <i class="fas fa-th-large"></i> Generale
                    </button>
                    <button class="view-btn <?php echo $vista == 'finanziaria' ? 'active' : ''; ?>" 
                            onclick="switchView('finanziaria')">
                        <i class="fas fa-euro-sign"></i> Finanziaria
                    </button>
                    <button class="view-btn <?php echo $vista == 'performance' ? 'active' : ''; ?>" 
                            onclick="switchView('performance')">
                        <i class="fas fa-trophy"></i> Performance
                    </button>
                </div>
                
                <!-- Azioni Rapide -->
                <div class="quick-actions">
                    <button class="action-btn" onclick="exportDashboard()">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="action-btn" onclick="printDashboard()">
                        <i class="fas fa-print"></i>
                    </button>
                    <button class="action-btn" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        
       <!-- KPI Cards -->
<div class="kpi-cards">
    <div class="kpi-card">
        <div class="kpi-icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">
                Alunni Attivi 
                <span style="position: relative; display: inline-block;">
                    <i class="fas fa-info-circle" 
                    style="font-size: 0.75em; color: #9ca3af; cursor: help; margin-left: 5px;"
                    onmouseover="showTooltip(this, 'Numero totale di alunni con stato attivo nel sistema')"
                    onmouseout="hideTooltip(this)"></i>
                    <span class="tooltip-box" style="display: none; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: #1f2937; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; white-space: normal; width: 220px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);z-index: 10;"></span>
                </span>
            </div>
            <div class="kpi-value"><?php echo $general_stats['alunni_attivi']; ?></div>
            <div class="kpi-change <?php echo getStatusClass($general_stats['alunni_nuovi_mese']); ?>">
                <i class="fas fa-arrow-up"></i> 
                +<?php echo $general_stats['alunni_nuovi_mese']; ?> questo mese
            </div>
        </div>
        <div class="kpi-progress">
            <div class="progress-bar" style="width: <?php echo ($general_stats['alunni_attivi'] / max($general_stats['alunni_totali'], 1)) * 100; ?>%"></div>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon success">
            <i class="fas fa-euro-sign"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">
                Entrate Mese
                <span style="position: relative; display: inline-block;">
                    <i class="fas fa-info-circle" 
                    style="font-size: 0.75em; color: #9ca3af; cursor: help; margin-left: 5px;"
                    onmouseover="showTooltip(this, 'Totale dei pagamenti ricevuti nel mese corrente')"
                    onmouseout="hideTooltip(this)"></i>
                    <span class="tooltip-box" style="display: none; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: #1f2937; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; white-space: normal; width: 220px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></span>
                </span>
            </div>
            <div class="kpi-value"><?php echo formatCurrency($financial_stats['entrate_mese']); ?></div>
            <div class="kpi-change">
                <span class="kpi-percentage">
                    <?php 
                    $percentuale = $financial_stats['previsione_mese'] > 0 
                        ? round(($financial_stats['entrate_mese'] / $financial_stats['previsione_mese']) * 100, 1)
                        : 0;
                    echo $percentuale . '%';
                    ?>
                </span> del previsto
            </div>
        </div>
        <div class="kpi-progress">
            <div class="progress-bar success" style="width: <?php echo min($percentuale, 100); ?>%"></div>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">
                Pagamenti in Sospeso
                <span style="position: relative; display: inline-block;">
                    <i class="fas fa-info-circle" 
                    style="font-size: 0.75em; color: #9ca3af; cursor: help; margin-left: 5px;"
                    onmouseover="showTooltip(this, 'Importo totale dei pagamenti mensili non ancora ricevuti (solo alunni con pacchetto mensile)')"
                    onmouseout="hideTooltip(this)"></i>
                    <span class="tooltip-box" style="display: none; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: #1f2937; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; white-space: normal; width: 220px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></span>
                </span>
            </div>
            <div class="kpi-value"><?php echo formatCurrency($financial_stats['pagamenti_in_sospeso']); ?></div>
            <div class="kpi-change negative">
            <i class="fas fa-percentage"></i> 
            <?php echo $financial_stats['tasso_morosita']; ?>% morosità
            <span style="position: relative; display: inline-block;">
                <i class="fas fa-info-circle" 
                style="font-size: 0.75em; color: #9ca3af; cursor: help; margin-left: 5px;"
                onmouseover="showTooltip(this, 'Percentuale di alunni con pacchetto mensile che non hanno ancora pagato questo mese')"
                onmouseout="hideTooltip(this)"></i>
                <span class="tooltip-box" style="display: none; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: #1f2937; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; white-space: normal; width: 220px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></span>
            </span>
        </div>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon primary">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">
                Tasso Occupazione
                <span style="position: relative; display: inline-block;">
                    <i class="fas fa-info-circle" 
                    style="font-size: 0.75em; color: #9ca3af; cursor: help; margin-left: 5px;"
                    onmouseover="showTooltip(this, 'Percentuale di slot orari utilizzati rispetto al totale disponibile (3 slot x 20 giorni x tutor)')"
                    onmouseout="hideTooltip(this)"></i>
                    <span class="tooltip-box" style="display: none; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: #1f2937; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; white-space: normal; width: 220px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></span>
                </span>
            </div>
            <div class="kpi-value"><?php echo $general_stats['tasso_occupazione']; ?>%</div>
            <div class="kpi-change">
            <i class="fas fa-users"></i> 
            <?php echo $general_stats['media_alunni_lezione']; ?> media/lezione
            <span style="position: relative; display: inline-block;">
                <i class="fas fa-info-circle" 
                style="font-size: 0.75em; color: #9ca3af; cursor: help; margin-left: 5px;"
                onmouseover="showTooltip(this, 'Numero medio di alunni presenti per lezione')"
                onmouseout="hideTooltip(this)"></i>
                <span class="tooltip-box" style="display: none; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: #1f2937; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; white-space: normal; width: 220px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></span>
            </span>
        </div>
        </div>
        <div class="kpi-progress">
            <div class="progress-bar primary" style="width: <?php echo $general_stats['tasso_occupazione']; ?>%"></div>
        </div>
    </div>
</div>
        
        <!-- Vista Generale -->
        <div id="vistaGenerale" class="dashboard-view <?php echo $vista == 'generale' ? 'active' : ''; ?>">
            <!-- Grafici principali -->
            <div class="charts-row">
                <div class="chart-card large">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Trend Annuale</h3>
                        <div class="chart-actions">
                            <button onclick="changeChartType('trendChart', 'line')"><i class="fas fa-chart-line"></i></button>
                            <button onclick="changeChartType('trendChart', 'bar')"><i class="fas fa-chart-bar"></i></button>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Distribuzione Pacchetti</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="packageChart"></canvas>
                    </div>
                    <div class="chart-legend" id="packageLegend"></div>
                </div>
            </div>
            
            <!-- Statistiche rapide -->
            <div class="quick-stats">
                <div class="stat-box">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="stat-value"><?php echo formatNumber($general_stats['ore_erogate_mese']); ?></span>
                        <span class="stat-label">
                        Ore Erogate
                        <span style="position: relative; display: inline-block;">
                            <i class="fas fa-info-circle" 
                            style="font-size: 0.65em; color: #9ca3af; cursor: help; margin-left: 3px;"
                            onmouseover="showTooltip(this, 'Totale ore di lezione erogate nel mese (1h = lezione intera, 0.5h = mezza lezione)')"
                            onmouseout="hideTooltip(this)"></i>
                            <span class="tooltip-box" style="display: none; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: #1f2937; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; white-space: normal; width: 220px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></span>
                        </span>
                    </span>
                    </div>
                </div>
                <div class="stat-box">
                    <i class="fas fa-book"></i>
                    <div>
                        <span class="stat-value"><?php echo formatNumber($general_stats['lezioni_mese']); ?></span>
                        <span class="stat-label">Lezioni</span>
                    </div>
                </div>
                <div class="stat-box">
                    <i class="fas fa-user-tie"></i>
                    <div>
                        <span class="stat-value"><?php echo $general_stats['tutor_attivi_mese']; ?>/<?php echo $general_stats['tutor_totali']; ?></span>
                        <span class="stat-label">Tutor Attivi</span>
                    </div>
                </div>
                <div class="stat-box">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <span class="stat-value <?php echo getStatusClass($financial_stats['entrate_mese'] - $financial_stats['uscite_mese']); ?>">
                            <?php echo formatCurrency($financial_stats['entrate_mese'] - $financial_stats['uscite_mese']); ?>
                        </span>
                        <span class="stat-label">Utile Mese</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vista Finanziaria -->
        <div id="vistaFinanziaria" class="dashboard-view <?php echo $vista == 'finanziaria' ? 'active' : ''; ?>">
            <!-- Riepilogo Finanziario -->
            <div class="financial-summary">
                <div class="summary-card income">
                    <h4><i class="fas fa-arrow-up"></i> Entrate</h4>
                    <div class="summary-content">
                        <div class="summary-item">
                            <span>Mese Corrente</span>
                            <strong><?php echo formatCurrency($financial_stats['entrate_mese']); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Anno Corrente</span>
                            <strong><?php echo formatCurrency($financial_stats['entrate_anno']); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Media Mensile</span>
                            <strong><?php echo formatCurrency($financial_stats['entrate_anno'] / 12); ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card expense">
                    <h4><i class="fas fa-arrow-down"></i> Uscite</h4>
                    <div class="summary-content">
                        <div class="summary-item">
                            <span>Mese Corrente</span>
                            <strong><?php echo formatCurrency($financial_stats['uscite_mese']); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Anno Corrente</span>
                            <strong><?php echo formatCurrency($financial_stats['uscite_anno']); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Media Mensile</span>
                            <strong><?php echo formatCurrency($financial_stats['uscite_anno'] / 12); ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card profit">
                    <h4><i class="fas fa-chart-line"></i> Profitto</h4>
                    <div class="summary-content">
                        <div class="summary-item">
                            <span>Mese Corrente</span>
                            <strong class="<?php echo getStatusClass($financial_stats['entrate_mese'] - $financial_stats['uscite_mese']); ?>">
                                <?php echo formatCurrency($financial_stats['entrate_mese'] - $financial_stats['uscite_mese']); ?>
                            </strong>
                        </div>
                        <div class="summary-item">
                            <span>Anno Corrente</span>
                            <strong class="<?php echo getStatusClass($financial_stats['entrate_anno'] - $financial_stats['uscite_anno']); ?>">
                                <?php echo formatCurrency($financial_stats['entrate_anno'] - $financial_stats['uscite_anno']); ?>
                            </strong>
                        </div>
                        <div class="summary-item">
                            <span>Margine %</span>
                            <strong>
                                <?php 
                                $margine = $financial_stats['entrate_anno'] > 0 
                                    ? round((($financial_stats['entrate_anno'] - $financial_stats['uscite_anno']) / $financial_stats['entrate_anno']) * 100, 1)
                                    : 0;
                                echo $margine . '%';
                                ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grafici Finanziari -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-balance-scale"></i> Confronto Entrate/Uscite</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-area"></i> Cash Flow</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="cashFlowChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Previsioni -->
            <div class="forecast-section">
                <h3><i class="fas fa-crystal-ball"></i> Previsioni e Trend</h3>
                <div class="forecast-cards">
                    <div class="forecast-card">
                        <div class="forecast-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="forecast-content">
                                                        <h4>Previsione Prossimo Mese</h4>
                            <div class="forecast-value">
                                <?php echo formatCurrency($chart_data['forecasts']['prossimo_mese']['previsto']); ?>
                            </div>
                            <div class="forecast-range">
                                <span>Min: <?php echo formatCurrency($chart_data['forecasts']['prossimo_mese']['min']); ?></span>
                                <span>Max: <?php echo formatCurrency($chart_data['forecasts']['prossimo_mese']['max']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="forecast-card">
                        <div class="forecast-icon <?php echo $chart_data['forecasts']['trend']; ?>">
                            <i class="fas fa-<?php 
                                echo $chart_data['forecasts']['trend'] == 'crescita' ? 'arrow-trend-up' : 
                                    ($chart_data['forecasts']['trend'] == 'decrescita' ? 'arrow-trend-down' : 'minus'); 
                            ?>"></i>
                        </div>
                        <div class="forecast-content">
                            <h4>Trend Generale</h4>
                            <div class="forecast-value">
                                <?php 
                                $trend_text = [
                                    'crescita' => 'In Crescita',
                                    'stabile' => 'Stabile',
                                    'decrescita' => 'In Calo'
                                ];
                                echo $trend_text[$chart_data['forecasts']['trend']];
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vista Performance -->
        <div id="vistaPerformance" class="dashboard-view <?php echo $vista == 'performance' ? 'active' : ''; ?>">
            <!-- Top Tutor -->
            <div class="performance-section">
                <h3><i class="fas fa-trophy"></i> Top Performer Tutor</h3>
                <div class="tutor-grid">
                    <?php 
                    $top_tutors = array_slice($chart_data['tutor_performance'], 0, 6);
                    foreach($top_tutors as $index => $tutor): 
                    ?>
                    <div class="tutor-card <?php echo $index < 3 ? 'top-' . ($index + 1) : ''; ?>">
                        <div class="tutor-rank">#<?php echo $index + 1; ?></div>
                        <div class="tutor-info">
                            <h4><?php echo htmlspecialchars($tutor['nome']); ?></h4>
                            <div class="tutor-stats">
                                <div class="stat">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo $tutor['lezioni_totali']; ?> lezioni</span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $tutor['alunni_unici']; ?> alunni</span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo $tutor['rating'] ?: 'N/A'; ?>/5</span>
                                </div>
                            </div>
                            <div class="tutor-metrics">
                                <div class="metric">
                                    <label>Efficienza</label>
                                    <div class="metric-bar">
                                        <div class="metric-fill" style="width: <?php echo min($tutor['efficienza'] * 50, 100); ?>%"></div>
                                    </div>
                                    <span><?php echo $tutor['efficienza']; ?> al/lez</span>
                                </div>
                                <div class="metric">
                                    <label>Revenue/Lezione</label>
                                    <span class="metric-value"><?php echo formatCurrency($tutor['revenue_per_lezione']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Grafici Performance -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-clock"></i> Trend Ore Lavorate</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="hoursChart"></canvas>
                    </div>
                </div>
                
                            <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-user-graduate"></i> Alunni per Tutor</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="tutorStudentsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Tabella Performance Dettagliata -->
            <div class="performance-table-section">
                <h3><i class="fas fa-table"></i> Analisi Dettagliata Performance</h3>
                <div class="table-responsive">
                    <table class="performance-table">
                        <thead>
                            <tr>
                                <th>Tutor</th>
                                <th>Lezioni</th>
                                <th>Singole/Gruppo</th>
                                <th>Alunni</th>
                                <th>Ore Totali</th>
                                <th>Guadagno</th>
                                <th>€/Ora</th>
                                <th>Rating</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($chart_data['tutor_performance'] as $tutor): ?>
                            <tr>
                               <td>
                                        <div class="tutor-name">
                                            <i class="fas fa-user-circle"></i>
                                            <?php echo htmlspecialchars($tutor['nome']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo $tutor['lezioni_totali']; ?></td>
                                    <td>
                                        <span class="badge"><?php echo $tutor['lezioni_singole']; ?></span> / 
                                        <span class="badge"><?php echo $tutor['lezioni_gruppo']; ?></span>
                                    </td>
                                    <td><?php echo $tutor['alunni_unici']; ?></td>
                                    <td><?php echo number_format($tutor['ore_effettive'] ?? 0, 1); ?></td>
                                    <td class="text-right"><?php echo formatCurrency($tutor['guadagno_totale']); ?></td>
                                    <td class="text-right"><?php echo formatCurrency($tutor['euro_ora'] ?? 0); ?></td>
                                <td>
                                    <div class="rating-display">
                                        <div class="rating-stars" data-tutor-id="<?php echo $tutor['id']; ?>">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star star <?php echo $i <= $tutor['rating'] ? 'filled' : ''; ?>" 
                                                data-rating="<?php echo $i; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-value">(<?php echo $tutor['rating'] ?: 'N/A'; ?>)</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="trend-indicator positive">
                                        <i class="fas fa-arrow-up"></i>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Widget Notifiche -->
        <div class="notification-widget">
            <h3><i class="fas fa-bell"></i> Notifiche e Alert</h3>
            <div class="notifications">
                <?php if($financial_stats['pagamenti_in_sospeso'] > 1000): ?>
                <div class="notification warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Pagamenti in sospeso elevati</strong>
                        <p>Ci sono <?php echo formatCurrency($financial_stats['pagamenti_in_sospeso']); ?> di pagamenti da incassare</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($general_stats['tasso_occupazione'] < 50): ?>
                <div class="notification info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Basso tasso di occupazione</strong>
                        <p>Solo il <?php echo $general_stats['tasso_occupazione']; ?>% degli slot disponibili è utilizzato</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($chart_data['forecasts']['trend'] == 'crescita'): ?>
                <div class="notification success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Trend positivo</strong>
                        <p>Le entrate sono in crescita rispetto ai mesi precedenti</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
// Tooltip functions
function showTooltip(element, text) {
    const tooltip = element.nextElementSibling;
    if (tooltip && tooltip.classList.contains('tooltip-box')) {
        tooltip.textContent = text;
        tooltip.style.display = 'block';
    }
}

function hideTooltip(element) {
    const tooltip = element.nextElementSibling;
    if (tooltip && tooltip.classList.contains('tooltip-box')) {
        tooltip.style.display = 'none';
    }
}

// Rating system - assicurati che sia dopo il DOM
window.addEventListener('load', function() {
    <?php if($_SESSION['username'] == 'alessandro'): ?>
    const ratingContainers = document.querySelectorAll('.rating-stars');
    console.log('Found rating containers:', ratingContainers.length); // Debug
    
    ratingContainers.forEach(function(container) {
        const tutorId = container.getAttribute('data-tutor-id');
        console.log('Processing tutor ID:', tutorId); // Debug
        
        const stars = container.querySelectorAll('.star');
        
        stars.forEach(function(star, index) {
            star.style.cursor = 'pointer'; // Forza il cursore
            
            star.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const rating = parseInt(this.getAttribute('data-rating'));
                console.log('Clicked rating:', rating); // Debug
                
                // Aggiorna stelle visivamente
                stars.forEach((s, idx) => {
                    if (idx < rating) {
                        s.classList.add('filled');
                    } else {
                        s.classList.remove('filled');
                    }
                });
                
                // Invia al server
                fetch('scripts/update_tutor_rating.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tutor_id: parseInt(tutorId),
                        valutazione: rating
                    })
                })
                .then(response => {
                    console.log('Response status:', response.status); // Debug
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data); // Debug
                    if (data.success) {
                        // Trova e aggiorna il valore mostrato
                        const valueSpan = container.parentElement.querySelector('.rating-value');
                        if (valueSpan) {
                            valueSpan.textContent = '(' + rating + ')';
                        }
                    } else {
                        alert('Errore: ' + (data.error || 'Sconosciuto'));
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error); // Debug
                    alert('Errore di connessione: ' + error.message);
                });
            };
        });
    });
    <?php endif; ?>
});
</script>
    
    <script>
    // Configurazione Chart.js
    Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
    Chart.defaults.animation.duration = 1000;
    Chart.defaults.plugins.legend.display = false;
    
    // Dati per i grafici
    const chartData = <?php echo json_encode($chart_data); ?>;
    
    // Inizializza grafici
    let charts = {};
    
    // Grafico Trend Annuale
    function initTrendChart() {
        const ctx = document.getElementById('trendChart');
        if (!ctx) return;
        
        const monthlyData = chartData.monthly_comparison;
        
        charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => 'Mese ' + d.mese),
                datasets: [{
                    label: 'Entrate',
                    data: monthlyData.map(d => d.entrate),
                    borderColor: '#48bb78',
                    backgroundColor: 'rgba(72, 187, 120, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Uscite',
                    data: monthlyData.map(d => d.uscite),
                    borderColor: '#f56565',
                    backgroundColor: 'rgba(245, 101, 101, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Utile',
                    data: monthlyData.map(d => d.utile),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': €' + 
                                       context.parsed.y.toLocaleString('it-IT');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toLocaleString('it-IT');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Grafico Distribuzione Pacchetti
    function initPackageChart() {
        const ctx = document.getElementById('packageChart');
        if (!ctx) return;
        
        const packageData = chartData.package_distribution;
        
        charts.package = new Chart(ctx, {
                        type: 'doughnut',
            data: {
                labels: packageData.map(p => p.nome),
                datasets: [{
                    data: packageData.map(p => p.count),
                    backgroundColor: [
                        '#667eea', '#764ba2', '#48bb78', '#f6ad55',
                        '#ed8936', '#f56565', '#9f7aea', '#38b2ac'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // Crea legenda custom
        createCustomLegend('packageLegend', charts.package);
    }
    
    // Altri grafici
    function initFinancialCharts() {
        // Grafico Confronto
        const comparisonCtx = document.getElementById('comparisonChart');
        if (comparisonCtx) {
            const monthlyData = chartData.monthly_comparison;
            
            charts.comparison = new Chart(comparisonCtx, {
                type: 'bar',
                data: {
                    labels: monthlyData.map(d => 'Mese ' + d.mese),
                    datasets: [{
                        label: 'Entrate',
                        data: monthlyData.map(d => d.entrate),
                        backgroundColor: '#48bb78'
                    }, {
                        label: 'Uscite',
                        data: monthlyData.map(d => d.uscite),
                        backgroundColor: '#f56565'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toLocaleString('it-IT');
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Grafico Cash Flow
        const cashFlowCtx = document.getElementById('cashFlowChart');
        if (cashFlowCtx) {
            const monthlyData = chartData.monthly_comparison;
            
            charts.cashFlow = new Chart(cashFlowCtx, {
                type: 'line',
                data: {
                    labels: monthlyData.map(d => 'Mese ' + d.mese),
                    datasets: [{
                        label: 'Cash Flow',
                        data: monthlyData.map(d => d.utile),
                        borderColor: '#667eea',
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                            gradient.addColorStop(0, 'rgba(102, 126, 234, 0.5)');
                            gradient.addColorStop(1, 'rgba(102, 126, 234, 0)');
                            return gradient;
                        },
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toLocaleString('it-IT');
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
function initPerformanceCharts() {
    // Grafico Ore Lavorate
    const hoursCtx = document.getElementById('hoursChart');
    if (hoursCtx) {
        const lessonData = chartData.lesson_hours_trend;
        
        charts.hours = new Chart(hoursCtx, {
            type: 'bar',
            data: {
                labels: lessonData.map(d => d.periodo),
                datasets: [{
                    label: 'Ore Erogate',
                    data: lessonData.map(d => d.ore),
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Grafico Alunni per Tutor - VERSIONE COMPATIBILE
    const tutorStudentsCtx = document.getElementById('tutorStudentsChart');
    if (tutorStudentsCtx) {
        // Controlla se ci sono dati
        if (!chartData.tutor_performance || chartData.tutor_performance.length === 0) {
            tutorStudentsCtx.parentElement.innerHTML = '<p style="text-align:center; padding: 20px; color: #999;">Nessun dato disponibile</p>';
            return;
        }
        
        const tutorData = chartData.tutor_performance.slice(0, 10);
        
        // Verifica quale versione di Chart.js stai usando
        const chartVersion = Chart.version ? parseInt(Chart.version.split('.')[0]) : 2;
        
        if (chartVersion >= 3) {
            // Chart.js 3.x
            charts.tutorStudents = new Chart(tutorStudentsCtx, {
                type: 'bar',
                data: {
                    labels: tutorData.map(t => t.nome.split(' ')[0]),
                    datasets: [{
                        label: 'Alunni Gestiti',
                        data: tutorData.map(t => parseInt(t.alunni_unici) || 0),
                        backgroundColor: '#764ba2',
                        barPercentage: 0.8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Orizzontale in Chart.js 3
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        } else {
            // Chart.js 2.x
            charts.tutorStudents = new Chart(tutorStudentsCtx, {
                type: 'horizontalBar', // Usa horizontalBar per Chart.js 2.x
                data: {
                    labels: tutorData.map(t => t.nome.split(' ')[0]),
                    datasets: [{
                        label: 'Alunni Gestiti',
                        data: tutorData.map(t => parseInt(t.alunni_unici) || 0),
                        backgroundColor: '#764ba2'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: [{
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }]
                    }
                }
            });
        }
    }
}
    
    // Funzioni utility
    function createCustomLegend(containerId, chart) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const legendItems = chart.data.labels.map((label, index) => {
            const value = chart.data.datasets[0].data[index];
            const color = chart.data.datasets[0].backgroundColor[index];
            const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            
            return `
                <div class="legend-item">
                    <span class="legend-color" style="background-color: ${color}"></span>
                    <span class="legend-label">${label}</span>
                    <span class="legend-value">${value} (${percentage}%)</span>
                </div>
            `;
        });
        
        container.innerHTML = legendItems.join('');
    }
    
    function changeChartType(chartId, type) {
        const chart = charts[chartId.replace('Chart', '')];
        if (chart) {
            chart.config.type = type;
            chart.update();
        }
    }
    
    function updateDashboard() {
        const anno = document.getElementById('anno').value;
        const mese = document.getElementById('mese').value;
        const vista = '<?php echo $vista; ?>';
        
        window.location.href = `dashboard.php?anno=${anno}&mese=${mese}&vista=${vista}`;
    }
    
    function switchView(view) {
        const anno = document.getElementById('anno').value;
        const mese = document.getElementById('mese').value;
        
        window.location.href = `dashboard.php?anno=${anno}&mese=${mese}&vista=${view}`;
    }
    
    function exportDashboard() {
    const anno = document.getElementById('anno').value;
    const mese = document.getElementById('mese').value;
    
    // Mostra opzioni export
    const exportOptions = confirm('Vuoi esportare in Excel? (OK = Excel, Annulla = PDF)');
    const tipo = exportOptions ? 'excel' : 'pdf';
    
    if (tipo === 'excel') {
        window.location.href = `scripts/export_dashboard.php?tipo=excel&anno=${anno}&mese=${mese}`;
    } else {
        // Per PDF usa la stampa del browser
        window.print();
    }
}
    
    function printDashboard() {
        window.print();
    }
    
    function refreshDashboard() {
        location.reload();
    }
    
    // Auto-refresh ogni 5 minuti
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            refreshDashboard();
        }
    }, 300000);
    
    // Inizializza tutti i grafici
    document.addEventListener('DOMContentLoaded', function() {
        initTrendChart();
        initPackageChart();
        initFinancialCharts();
        initPerformanceCharts();
        
        // Animazioni per i KPI
        animateNumbers();
        
        // Tooltip per dispositivi touch
        if ('ontouchstart' in window) {
            document.querySelectorAll('[data-tooltip]').forEach(el => {
                el.addEventListener('touchstart', function() {
                    this.classList.toggle('show-tooltip');
                });
            });
        }
    });

    
    
    // Animazione numeri - VERSIONE CORRETTA
function animateNumbers() {
    const elements = document.querySelectorAll('.kpi-value, .stat-value');
    
    elements.forEach(el => {
        // Salva il valore originale
        const originalText = el.innerText;
        
        // Gestisci il formato italiano (. per migliaia, , per decimali)
        let cleanValue = originalText.replace(/[^\d,.-]/g, ''); // Mantieni virgola
        cleanValue = cleanValue.replace(/\./g, ''); // Rimuovi punti delle migliaia
        cleanValue = cleanValue.replace(',', '.'); // Converti virgola in punto per parseFloat
        
        const target = parseFloat(cleanValue);
        if (isNaN(target)) return;
        
        const duration = 1000;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (originalText.includes('€')) {
                el.innerText = current.toLocaleString('it-IT', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' €';
            } else if (originalText.includes('%')) {
                el.innerText = current.toFixed(1) + '%';
            } else {
                el.innerText = Math.round(current).toLocaleString('it-IT');
            }
        }, 16);
    });
}
    
    // Gestione responsive
    window.addEventListener('resize', function() {
        Object.values(charts).forEach(chart => {
            if (chart) chart.resize();
        });
    });

   
    </script>
</body>
</html>