<?php
// export_dashboard.php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) {
    die('Accesso negato');
}

$tipo = $_GET['tipo'] ?? 'excel';
$anno = intval($_GET['anno'] ?? date('Y'));
$mese = intval($_GET['mese'] ?? date('n'));

// Definizione della classe DashboardStats (copiata da dashboard.php)
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

        $stats['pagamenti_in_sospeso'] = $row['totale_dovuto'];

        // Calcola tasso morosità
        $alunni_mensili_non_paganti = $row['alunni_mensili_attivi'] - $row['alunni_paganti'];
        if ($row['alunni_mensili_attivi'] > 0) {
            $stats['tasso_morosita'] = round(($alunni_mensili_non_paganti / $row['alunni_mensili_attivi']) * 100, 1);
        } else {
            $stats['tasso_morosita'] = 0;
        }

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
}

// Crea istanza e ottieni dati
$dashboard = new DashboardStats($conn, $anno, $mese);
$general_stats = $dashboard->getGeneralStats();
$financial_stats = $dashboard->getFinancialStats();

// Headers per Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="dashboard_' . $anno . '_' . $mese . '.xls"');
header('Cache-Control: max-age=0');

$mesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4f46e5; color: white; font-weight: bold; }
        h1, h2 { color: #333; }
        .number { text-align: right; }
        .header { background-color: #667eea; color: white; }
        .section { margin-top: 30px; }
        .positive { color: #10b981; font-weight: bold; }
        .negative { color: #ef4444; font-weight: bold; }
        .summary { background-color: #f3f4f6; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Dashboard Report - <?php echo $mesi[$mese] . ' ' . $anno; ?></h1>
    <p>Generato il: <?php echo date('d/m/Y H:i:s'); ?></p>
    
    <div class="section">
        <h2>Statistiche Generali</h2>
        <table>
            <tr>
                <th>Metrica</th>
                <th class="number">Valore</th>
                <th>Note</th>
            </tr>
            <tr>
                <td>Alunni Attivi</td>
                <td class="number"><?php echo $general_stats['alunni_attivi']; ?></td>
                <td>Su un totale di <?php echo $general_stats['alunni_totali']; ?> alunni</td>
            </tr>
            <tr>
                <td>Nuovi Alunni (mese)</td>
                <td class="number"><?php echo $general_stats['alunni_nuovi_mese']; ?></td>
                <td>Iscritti questo mese</td>
            </tr>
            <tr>
                <td>Tutor Attivi</td>
                <td class="number"><?php echo $general_stats['tutor_attivi_mese']; ?></td>
                <td>Su <?php echo $general_stats['tutor_totali']; ?> tutor totali</td>
            </tr>
            <tr>
                <td>Lezioni Erogate</td>
                <td class="number"><?php echo $general_stats['lezioni_mese']; ?></td>
                <td>Nel mese corrente</td>
            </tr>
            <tr>
                <td>Ore Totali</td>
                                <td class="number"><?php echo number_format($general_stats['ore_erogate_mese'], 1, ',', '.'); ?></td>
                <td>Ore di lezione erogate</td>
            </tr>
            <tr>
                <td>Tasso Occupazione</td>
                <td class="number"><?php echo $general_stats['tasso_occupazione']; ?>%</td>
                <td>Utilizzo slot disponibili</td>
            </tr>
            <tr>
                <td>Media Alunni/Lezione</td>
                <td class="number"><?php echo $general_stats['media_alunni_lezione']; ?></td>
                <td>Numero medio di alunni per lezione</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Dati Finanziari</h2>
        <table>
            <tr>
                <th>Metrica</th>
                <th class="number">Valore</th>
                <th>Dettagli</th>
            </tr>
            <tr>
                <td>Entrate Mese</td>
                <td class="number">€ <?php echo number_format($financial_stats['entrate_mese'], 2, ',', '.'); ?></td>
                <td>Totale incassi del mese</td>
            </tr>
            <tr>
                <td>Uscite Mese</td>
                <td class="number">€ <?php echo number_format($financial_stats['uscite_mese'], 2, ',', '.'); ?></td>
                <td>Pagamenti tutor</td>
            </tr>
            <tr class="summary">
                <td>Utile Mese</td>
                <td class="number <?php echo ($financial_stats['entrate_mese'] - $financial_stats['uscite_mese']) >= 0 ? 'positive' : 'negative'; ?>">
                    € <?php echo number_format($financial_stats['entrate_mese'] - $financial_stats['uscite_mese'], 2, ',', '.'); ?>
                </td>
                <td>Differenza entrate-uscite</td>
            </tr>
            <tr>
                <td colspan="3" style="background-color: #e5e7eb; height: 10px;"></td>
            </tr>
            <tr>
                <td>Entrate Anno</td>
                <td class="number">€ <?php echo number_format($financial_stats['entrate_anno'], 2, ',', '.'); ?></td>
                <td>Totale da inizio anno</td>
            </tr>
            <tr>
                <td>Uscite Anno</td>
                <td class="number">€ <?php echo number_format($financial_stats['uscite_anno'], 2, ',', '.'); ?></td>
                <td>Totale da inizio anno</td>
            </tr>
            <tr class="summary">
                <td>Utile Anno</td>
                <td class="number <?php echo ($financial_stats['entrate_anno'] - $financial_stats['uscite_anno']) >= 0 ? 'positive' : 'negative'; ?>">
                    € <?php echo number_format($financial_stats['entrate_anno'] - $financial_stats['uscite_anno'], 2, ',', '.'); ?>
                </td>
                <td>Utile complessivo annuale</td>
            </tr>
            <tr>
                <td colspan="3" style="background-color: #e5e7eb; height: 10px;"></td>
            </tr>
            <tr>
                <td>Pagamenti in Sospeso</td>
                <td class="number negative">€ <?php echo number_format($financial_stats['pagamenti_in_sospeso'], 2, ',', '.'); ?></td>
                <td>Da incassare (solo mensili)</td>
            </tr>
            <tr>
                <td>Tasso Morosità</td>
                <td class="number <?php echo $financial_stats['tasso_morosita'] > 20 ? 'negative' : ''; ?>">
                    <?php echo $financial_stats['tasso_morosita']; ?>%
                </td>
                <td>Alunni mensili non paganti</td>
            </tr>
            <tr>
                <td>Previsione Mensile</td>
                <td class="number">€ <?php echo number_format($financial_stats['previsione_mese'], 2, ',', '.'); ?></td>
                <td>Entrate attese da alunni attivi</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Indicatori di Performance</h2>
        <table>
            <tr>
                <th>Indicatore</th>
                <th class="number">Valore</th>
                <th>Stato</th>
            </tr>
            <tr>
                <td>Margine di Profitto (Mese)</td>
                <td class="number">
                    <?php 
                    $margine_mese = $financial_stats['entrate_mese'] > 0 
                        ? round((($financial_stats['entrate_mese'] - $financial_stats['uscite_mese']) / $financial_stats['entrate_mese']) * 100, 1)
                        : 0;
                    echo $margine_mese . '%';
                    ?>
                </td>
                <td class="<?php echo $margine_mese > 30 ? 'positive' : ($margine_mese < 20 ? 'negative' : ''); ?>">
                    <?php echo $margine_mese > 30 ? 'Ottimo' : ($margine_mese < 20 ? 'Attenzione' : 'Buono'); ?>
                </td>
            </tr>
            <tr>
                <td>Efficienza Raccolta Pagamenti</td>
                <td class="number">
                    <?php 
                    $efficienza = $financial_stats['previsione_mese'] > 0 
                        ? round(($financial_stats['entrate_mese'] / $financial_stats['previsione_mese']) * 100, 1)
                        : 0;
                    echo $efficienza . '%';
                    ?>
                </td>
                <td class="<?php echo $efficienza > 80 ? 'positive' : ($efficienza < 60 ? 'negative' : ''); ?>">
                    <?php echo $efficienza > 80 ? 'Ottima' : ($efficienza < 60 ? 'Critica' : 'Sufficiente'); ?>
                </td>
            </tr>
            <tr>
                <td>Utilizzo Risorse</td>
                <td class="number"><?php echo $general_stats['tasso_occupazione']; ?>%</td>
                <td class="<?php echo $general_stats['tasso_occupazione'] > 70 ? 'positive' : ($general_stats['tasso_occupazione'] < 40 ? 'negative' : ''); ?>">
                    <?php echo $general_stats['tasso_occupazione'] > 70 ? 'Alto' : ($general_stats['tasso_occupazione'] < 40 ? 'Basso' : 'Medio'); ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Riepilogo Mensile</h2>
        <table>
            <tr>
                <th>Mese</th>
                <th class="number">Entrate</th>
                <th class="number">Uscite</th>
                <th class="number">Utile</th>
                <th class="number">Margine %</th>
            </tr>
            <?php
            // Ottieni dati mensili per confronto
            for ($i = 1; $i <= 12; $i++) {
                $query = "SELECT 
                            COALESCE(SUM(p.totale_pagato), 0) as entrate,
                            COALESCE((SELECT SUM(paga) FROM pagamenti_tutor 
                                     WHERE stato = 1 AND MONTH(data_pagamento) = ? 
                                     AND YEAR(data_pagamento) = ?), 0) as uscite
                          FROM pagamenti p
                          WHERE MONTH(p.data_pagamento) = ? AND YEAR(p.data_pagamento) = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiii", $i, $anno, $i, $anno);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['entrate'] > 0 || $row['uscite'] > 0) {
                    $utile = $row['entrate'] - $row['uscite'];
                    $margine = $row['entrate'] > 0 ? round(($utile / $row['entrate']) * 100, 1) : 0;
                    
                    echo '<tr' . ($i == $mese ? ' class="summary"' : '') . '>';
                    echo '<td>' . $mesi[$i] . '</td>';
                    echo '<td class="number">€ ' . number_format($row['entrate'], 2, ',', '.') . '</td>';
                    echo '<td class="number">€ ' . number_format($row['uscite'], 2, ',', '.') . '</td>';
                    echo '<td class="number ' . ($utile >= 0 ? 'positive' : 'negative') . '">€ ' . number_format($utile, 2, ',', '.') . '</td>';
                    echo '<td class="number">' . $margine . '%</td>';
                    echo '</tr>';
                }
            }
            ?>
        </table>
    </div>
    
    <div class="section">
        <p style="margin-top: 40px; font-style: italic; color: #666;">
            Report generato automaticamente dal sistema di gestione doposcuola.<br>
            Per informazioni dettagliate, accedere alla dashboard online.
        </p>
    </div>
</body>
</html>
<?php
$conn->close();
?>