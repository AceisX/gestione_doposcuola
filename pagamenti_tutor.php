<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: pages/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamenti Tutor - Gestione Avanzata</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link href="assets/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f6f8fa;
    margin: 0;
}

.container {
    max-width: 1200px;
    background: #fff;
    margin: 40px auto;
    border-radius: 16px;
    box-shadow: 0 2px 24px 0 rgba(0,0,0,0.1);
    padding: 30px 40px 40px 40px;
}

/* Dashboard Summary */
.dashboard-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 0.9em;
    opacity: 0.9;
    font-weight: 500;
}

.stat-card .amount {
    font-size: 2em;
    font-weight: bold;
    margin: 0;
}

.stat-card .count {
    font-size: 2.5em;
    font-weight: bold;
    margin: 0;
}

/* Filters Section */
.filters-section {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    align-items: center;
    flex-wrap: wrap;
}

.filters-section select {
    padding: 8px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1em;
    background: white;
}

.btn-export {
    background: #28a745;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.btn-export:hover {
    background: #218838;
}

/* Summary Table */
.all-tutors-view {
    margin-top: 40px;
    margin-bottom: 40px;
}

.all-tutors-view h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.3em;
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.summary-table th,
.summary-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.summary-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-table tr:hover {
    background: #f8f9fa;
}

.btn-view {
    background: #007bff;
    color: white;
    padding: 6px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    transition: background 0.2s;
}

.btn-view:hover {
    background: #0056b3;
}

.btn-pay-selected {
    background: #28a745;
    color: white;
    padding: 6px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    margin-left: 5px;
    transition: background 0.2s;
}

.btn-pay-selected:hover {
    background: #218838;
}

/* Search Bar */
.search-bar {
    text-align: center;
    margin-bottom: 30px;
    position: relative;
}

#search-tutor {
    width: 400px;
    padding: 12px 20px;
    border-radius: 25px;
    border: 2px solid #e1e4e8;
    outline: none;
    font-size: 1.1em;
    transition: border-color 0.3s;
}

#search-tutor:focus {
    border-color: #667eea;
}

#search-results {
    list-style: none;
    padding: 0;
    margin: 0;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 400px;
    background: #fff;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 10px 10px;
    z-index: 120;
    box-shadow: 0 6px 24px 0 rgba(0,0,0,0.1);
    max-height: 300px;
    overflow-y: auto;
}

#search-results li {
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
}

#search-results li:hover {
    background: #f6f8fa;
}

/* Mensilità Table */
.mensilita-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.mensilita-table th,
.mensilita-table td {
    text-align: center;
    padding: 13px 10px;
    border-bottom: 1px solid #eee;
}

.mensilita-table th {
    background: #f8f9fa;
    color: #333;
    font-size: 0.9em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mensilita-table tr:hover {
    background: #f8f9fa;
}

.checkbox-column {
    width: 40px;
}

/* Badges */
.paga-badge {
    padding: 6px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.95em;
    display: inline-block;
    letter-spacing: 0.3px;
}

.paga-badge.pagato {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.paga-badge.non-pagato {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Buttons */
button.paga-btn,
button.reset-btn {
    padding: 6px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    margin: 0 3px;
    transition: all 0.2s;
    font-size: 0.9em;
}

button.paga-btn {
    background: #28a745;
    color: #fff;
}

button.paga-btn:hover {
    background: #218838;
    transform: translateY(-1px);
}

button.reset-btn {
    background: #ffc107;
    color: #212529;
}

button.reset-btn:hover {
    background: #e0a800;
    transform: translateY(-1px);
}

/* Modals */
.modal {
    display: none;
    position: fixed;
    z-index: 1500;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: #fff;
    margin: 50px auto;
    padding: 30px 40px;
    border-radius: 16px;
    width: 450px;
    position: relative;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-large {
    width: 650px;
    max-width: 90%;
}

.close-btn {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 28px;
    color: #666;
    cursor: pointer;
    transition: color 0.2s;
}

.close-btn:hover {
    color: #000;
}

/* Form Groups */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 1em;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #667eea;
    outline: none;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.btn {
    padding: 10px 25px;
    border: none;
    border-radius: 8px;
    background: #667eea;
    color: #fff;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn:hover {
    background: #5a67d8;
    transform: translateY(-1px);
}

/* Mensilità Selezionate */
#mensilita-selezionate {
    margin-bottom: 20px;
    max-height: 200px;
    overflow-y: auto;
}

.mensilita-item {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.totale-section {
    background: #e8f5e9;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}

.totale-section h3 {
    margin: 0;
    color: #2e7d32;
}

/* Alert Messages */
.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #bee5eb;
}

/* Loading State */
.loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.loading i {
    font-size: 3em;
    color: #667eea;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Pay Multiple Button */
.btn-pay-multiple {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    margin-top: 15px;
    display: none;
    transition: background 0.2s;
}

.btn-pay-multiple:hover {
    background: #218838;
}

.btn-pay-multiple.show {
    display: inline-block;
}
</style>

<body>
<?php include __DIR__ . '/assets/header.php'; ?>   

<div class="container">
    <!-- Dashboard Summary -->
    <div class="dashboard-summary">
        <div class="stat-card">
            <h3><i class="fas fa-euro-sign"></i> Totale da Pagare</h3>
            <p class="amount">€ <span id="totale-da-pagare">0</span></p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h3><i class="fas fa-calendar-check"></i> Pagamenti Questo Mese</h3>
            <p class="amount">€ <span id="pagamenti-mese">0</span></p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h3><i class="fas fa-users"></i> Tutor Attivi</h3>
            <p class="count"><span id="tutor-attivi">0</span></p>
        </div>
    </div>

   <!-- Filters Section -->
<div class="filters-section">
    <select id="filter-anno">
        <?php 
        $currentYear = date('Y');
        for ($year = $currentYear + 1; $year >= 2022; $year--) {
            $selected = ($year == $currentYear) ? 'selected' : '';
            echo "<option value='$year' $selected>$year</option>";
        }
        ?>
    </select>
    <select id="filter-stato">
        <option value="">Tutti</option>
        <option value="pagato">Solo Pagati</option>
        <option value="non-pagato">Solo Non Pagati</option>
    </select>
    <button id="export-excel" class="btn-export">
        <i class="fas fa-file-excel"></i> Export Excel
    </button>
    <!-- NUOVO BOTTONE -->
    <button id="add-tutor-btn" class="btn-add-tutor">
        <i class="fas fa-user-plus"></i> Nuovo Tutor
    </button>
</div>

    <!-- All Tutors View -->
    <div class="all-tutors-view">
        <h3>Riepilogo Tutti i Tutor</h3>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Tutor</th>
                    <th>Mesi Non Pagati</th>
                    <th>Totale Dovuto</th>
                    <th>Ultimo Pagamento</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody id="all-tutors-tbody">
                <tr>
                    <td colspan="5" class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Caricamento dati...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <h3>Ricerca Dettaglio Tutor</h3>
        <input type="text" id="search-tutor" placeholder="Cerca tutor per nome o cognome...">
        <ul id="search-results"></ul>
    </div>

    <!-- Tutor Info -->
    <div id="tutor-info" style="display: none;">
        <h2 id="tutor-name"></h2>
        <button class="btn-pay-multiple" id="pay-multiple-btn">
            <i class="fas fa-money-check-alt"></i> Paga Mensilità Selezionate
        </button>
        <table class="mensilita-table">
            <thead>
                <tr>
                    <th class="checkbox-column">
                        <input type="checkbox" id="select-all-checkbox">
                    </th>
                    <th>Mese</th>
                    <th>Paga</th>
                    <th>Ore Singole</th>
                    <th>Ore Gruppo</th>
                    <th>Data Pagamento</th>
                    <th>Note</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody id="mensilita-rows">
                <!-- Dati mensilità caricati dinamicamente -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modale per il pagamento singolo -->
<div id="modale-paga" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" onclick="chiudiModalePaga()">&times;</span>
        <h2>Paga Mensilità</h2>
        <form id="form-paga">
            <input type="hidden" id="mensilita-id" name="mensilita_id">
            <div class="form-group">
                <label for="data-pagamento">Data Pagamento:</label>
                <input type="date" id="data-pagamento" name="data_pagamento" required>
            </div>
            <div class="form-group">
                <label for="importo-paga">Importo (€):</label>
                <input type="number" id="importo-paga" name="importo_paga" min="0" step="0.01">
            </div>
            <div class="form-group">
                <label for="note-paga">Note:</label>
                <textarea id="note-paga" name="note_paga"></textarea>
            </div>
            <button type="submit" class="btn">Salva</button>
        </form>
    </div>
</div>

<!-- Modale pagamenti multipli -->
<div id="modale-paga-multipli" class="modal">
    <div class="modal-content modal-large">
        <span class="close-btn" onclick="chiudiModaleMultipli()">&times;</span>
        <h2>Paga Mensilità Multiple</h2>
        <div id="mensilita-selezionate">
            <!-- Popolato dinamicamente -->
        </div>
        <div class="totale-section">
            <h3>Totale: €<span id="totale-multiplo">0</span></h3>
        </div>
        <form id="form-paga-multipli">
            <input type="hidden" id="tutor-id-multipli" name="tutor_id">
            <input type="hidden" id="mensilita-ids" name="mensilita_ids">
            <div class="form-group">
                <label for="data-pagamento-multipli">Data Pagamento:</label>
                <input type="date" id="data-pagamento-multipli" name="data_pagamento" required>
            </div>
            <div class="form-group">
                <label for="note-multipli">Note:</label>
                <textarea id="note-multipli" name="note"></textarea>
            </div>
            <button type="submit" class="btn">Conferma Pagamento</button>
        </form>
    </div>
</div>

<!-- Modale Dettaglio Ore -->
<div id="modale-ore-dettaglio" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="chiudiModaleOreDettaglio()">&times;</span>
        <h2>Dettaglio Ore - <span id="mese-dettaglio"></span></h2>
        <div class="ore-dettaglio-content">
            <div class="ore-row">
                <span class="ore-label">Ore Singole:</span>
                <span class="ore-value" id="ore-singole-dettaglio">0</span>
            </div>
            <div class="ore-row">
                <span class="ore-label">Ore di Gruppo:</span>
                <span class="ore-value" id="ore-gruppo-dettaglio">0</span>
            </div>
            <div class="ore-row">
                <span class="ore-label">Mezze Ore Singole:</span>
                <span class="ore-value" id="mezze-ore-singole-dettaglio">0</span>
            </div>
            <div class="ore-row">
                <span class="ore-label">Mezze Ore di Gruppo:</span>
                <span class="ore-value" id="mezze-ore-gruppo-dettaglio">0</span>
            </div>
            <hr>
            <div class="ore-row totale">
                <span class="ore-label"><strong>Totale Ore Singole:</strong></span>
                <span class="ore-value" id="totale-ore-singole">0</span>
            </div>
            <div class="ore-row totale">
                <span class="ore-label"><strong>Totale Ore di Gruppo:</strong></span>
                <span class="ore-value" id="totale-ore-gruppo">0</span>
            </div>
        </div>
    </div>
</div>

<!-- Modale Nuovo/Modifica Tutor -->
<div id="modale-tutor" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="chiudiModaleTutor()">&times;</span>
        <h2 id="modal-tutor-title">Nuovo Tutor</h2>
        <form id="form-tutor">
            <input type="hidden" id="tutor-id" name="tutor_id">
            <div class="form-group">
                <label for="tutor-nome">Nome: <span style="color: red;">*</span></label>
                <input type="text" id="tutor-nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="tutor-cognome">Cognome: <span style="color: red;">*</span></label>
                <input type="text" id="tutor-cognome" name="cognome" required>
            </div>
            <div class="form-group">
                <label for="tutor-email">Email:</label>
                <input type="email" id="tutor-email" name="email">
            </div>
            <div class="form-group">
                <label for="tutor-telefono">Telefono:</label>
                <input type="tel" id="tutor-telefono" name="telefono" pattern="[0-9+\-\s]*">
            </div>
            <button type="submit" class="btn">Salva Tutor</button>
        </form>
    </div>
</div>

<script>
    function showNotification(message, type = 'info') {
    // Rimuovi notifiche esistenti
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Animazione di entrata
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Rimuovi dopo 3 secondi
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}


let currentTutorId = null;
let selectedMensilita = [];

// Funzione per caricare la dashboard
function loadDashboard() {
    const anno = document.getElementById('filter-anno').value;
    const stato = document.getElementById('filter-stato').value;
    
    // Mostra stato di caricamento
    const tbody = document.getElementById('all-tutors-tbody');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="loading">
                <i class="fas fa-spinner"></i>
                <p>Caricamento dati...</p>
            </td>
        </tr>
    `;
    
    fetch(`scripts/get_dashboard_stats.php?anno=${anno}&stato=${stato}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Errore nella risposta del server');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Aggiorna statistiche
                document.getElementById('totale-da-pagare').textContent = 
                    parseFloat(data.stats.totale_da_pagare || 0).toFixed(2);
                document.getElementById('pagamenti-mese').textContent = 
                    parseFloat(data.stats.pagamenti_mese || 0).toFixed(2);
                document.getElementById('tutor-attivi').textContent = 
                    data.stats.tutor_attivi || 0;
                
                // Popola tabella riepilogo
                if (data.tutors && data.tutors.length > 0) {
                    tbody.innerHTML = data.tutors.map(tutor => {
                        const nomeCompleto = `${tutor.nome || ''} ${tutor.cognome || ''}`.trim();
                        const mesiNonPagati = tutor.mesi_non_pagati || 0;
                        const totaleDovuto = parseFloat(tutor.totale_dovuto || 0).toFixed(2);
                        const ultimoPagamento = tutor.ultimo_pagamento 
                            ? new Date(tutor.ultimo_pagamento).toLocaleDateString('it-IT')
                            : '-';
                            
                            const visualizzaButton = mesiNonPagati > 0 
                            ? `<button class="btn-view" onclick="viewTutor(${tutor.id}, '${nomeCompleto.replace(/'/g, "\\'")}')">
                                   <i class="fas fa-eye"></i> Visualizza
                               </button>`
                            : `<button class="btn-view" disabled 
                                       style="opacity: 0.5; cursor: not-allowed;" 
                                       title="Nessun pagamento pendente">
                                   <i class="fas fa-eye-slash"></i> Visualizza
                               </button>`;
                        return `
                            <tr>
                                <td>${nomeCompleto}</td>
                                <td>${mesiNonPagati}</td>
                                <td>€${totaleDovuto}</td>
                                <td>${ultimoPagamento}</td>
                                <td>
                                     ${visualizzaButton}
                                    <button class="btn-edit-tutor" onclick="editTutor(${tutor.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-delete-tutor" onclick="deleteTutor(${tutor.id}, '${nomeCompleto.replace(/'/g, "\\'")}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    // Nessun dato trovato
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #666;">
                                <i class="fas fa-info-circle" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                                Nessun dato disponibile per i filtri selezionati
                            </td>
                        </tr>
                    `;
                }
            } else {
                // Errore dal server
                throw new Error(data.message || 'Errore nel caricamento dei dati');
            }
        })
        .catch(error => {
            console.error('Errore nel caricamento dashboard:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #dc3545;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                        Errore nel caricamento dei dati<br>
                        <small>${error.message}</small>
                    </td>
                </tr>
            `;
            
            // Mostra notifica di errore
            showNotification('Errore nel caricamento dei dati. Riprova più tardi.', 'error');
        });
}

    
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-tutor');
    const searchResults = document.getElementById('search-results');
    const tutorInfo = document.getElementById('tutor-info');
    const tutorName = document.getElementById('tutor-name');
    const mensilitaRows = document.getElementById('mensilita-rows');
    const modalePaga = document.getElementById('modale-paga');
    const formPaga = document.getElementById('form-paga');
    const mensilitaIdInput = document.getElementById('mensilita-id');
    const importoPagaInput = document.getElementById('importo-paga');
    const notePagaInput = document.getElementById('note-paga');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const payMultipleBtn = document.getElementById('pay-multiple-btn');
    let currentTutorId = null;
    let selectedMensilita = [];

    // Carica dashboard all'avvio
    loadDashboard();
    // Funzione per visualizzare un tutor dalla tabella riepilogo
window.viewTutor = function(tutorId, nome) {
    currentTutorId = tutorId;
    searchResults.innerHTML = '';
    searchInput.value = '';
    
    // Carica i dati del tutor
    fetch(`scripts/get_tutor_details.php?tutor_id=${tutorId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showTutorDetails(data);
                
                // AGGIUNGI QUESTO: Scroll automatico alla sezione tutor-info
                setTimeout(() => {
                    document.getElementById('tutor-info').scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Opzionale: evidenzia brevemente la sezione
                    const tutorInfo = document.getElementById('tutor-info');
                    tutorInfo.style.transition = 'background-color 0.3s';
                    tutorInfo.style.backgroundColor = '#f0f8ff';
                    setTimeout(() => {
                        tutorInfo.style.backgroundColor = '';
                    }, 1000);
                }, 100);
            }
        });
};
    // Cerca tutor dinamicamente
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();

        if (query.length === 0) {
            searchResults.innerHTML = '';
            return;
        }

        fetch(`scripts/search_tutors.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    searchResults.innerHTML = data.tutors
                        .map(tutor => `<li data-id="${tutor.id}">${tutor.nome} ${tutor.cognome}</li>`)
                        .join('');
                } else {
                    searchResults.innerHTML = '<li>Nessun risultato trovato</li>';
                }
            });
    });

    // Carica informazioni del tutor cliccato
    searchResults.addEventListener('click', (e) => {
        if (e.target.tagName === 'LI') {
            const tutorId = e.target.dataset.id;
            currentTutorId = tutorId;

            // Nasconde i risultati di ricerca
            searchResults.innerHTML = '';
            searchInput.value = '';

            // Recupera i dati del tutor selezionato
            fetch(`scripts/get_tutor_details.php?tutor_id=${tutorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showTutorDetails(data);
                    } else {
                        alert('Impossibile recuperare i dati del tutor.');
                    }
                });
        }
    });

    // Funzione per mostrare i dettagli del tutor
    function showTutorDetails(data) {
        tutorInfo.style.display = 'block';
        tutorName.textContent = `${data.tutor.nome} ${data.tutor.cognome}`;
        
        // Reset checkbox select all
        selectAllCheckbox.checked = false;
        selectedMensilita = [];
        payMultipleBtn.classList.remove('show');
        
        mensilitaRows.innerHTML = data.mensilita
            .map(row => `
                <tr>
                    <td class="checkbox-column">
                        ${row.stato === 0 ? `<input type="checkbox" class="mensilita-checkbox" data-id="${row.id}" data-paga="${row.paga}">` : ''}
                    </td>
                    <td>${row.mese}</td>
                    <td>
                        <span class="paga-badge ${row.stato === 1 ? 'pagato' : 'non-pagato'}">
                            €${row.paga}
                        </span>
                    </td>
                    <td>${row.ore_singole_totali}</td>
                    <td>
                        ${row.ore_gruppo_totali}
                        <button class="btn-info-ore" onclick="showOreDetails('${row.mese}', ${row.ore_singole}, ${row.ore_gruppo}, ${row.mezze_ore_singole}, ${row.mezze_ore_gruppo})" title="Dettaglio ore">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </td>
                    <td>${row.stato === 1 && row.data_pagamento ? row.data_pagamento : '-'}</td>
                    <td>${row.note ? row.note.replace(/\n/g, '<br>') : '-'}</td>
                    <td>
                        ${row.stato === 1
                            ? '<button class="reset-btn" data-id="' + row.id + '">Reset</button>'
                            : '<button class="paga-btn" data-id="' + row.id + '" data-paga="' + row.paga + '">Paga</button>'
                        }
                    </td>
                </tr>
            `).join('');

        // Aggiungi listener ai bottoni e checkbox
        addListenersToButtons();
        addCheckboxListeners();
    }

    // Aggiungi listener ai checkbox
    function addCheckboxListeners() {
        const checkboxes = document.querySelectorAll('.mensilita-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateSelectedMensilita();
            });
        });
    }

    // Update selected mensilità
    function updateSelectedMensilita() {
        const checkboxes = document.querySelectorAll('.mensilita-checkbox:checked');
        selectedMensilita = Array.from(checkboxes).map(cb => ({
            id: cb.dataset.id,
            paga: parseFloat(cb.dataset.paga)
        }));
        
        if (selectedMensilita.length > 0) {
            payMultipleBtn.classList.add('show');
        } else {
            payMultipleBtn.classList.remove('show');
        }
    }

    // Select all checkbox
    selectAllCheckbox.addEventListener('change', () => {
        const checkboxes = document.querySelectorAll('.mensilita-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = selectAllCheckbox.checked;
        });
        updateSelectedMensilita();
    });

    // Pay multiple button
    payMultipleBtn.addEventListener('click', () => {
        if (selectedMensilita.length === 0) return;
        
        const modaleMultipli = document.getElementById('modale-paga-multipli');
        const mensilitaSelezionate = document.getElementById('mensilita-selezionate');
        const totaleMultiplo = document.getElementById('totale-multiplo');
        const mensilitaIdsInput = document.getElementById('mensilita-ids');
        const tutorIdMultipliInput = document.getElementById('tutor-id-multipli');

        // Calcola totale
        const totale = selectedMensilita.reduce((sum, m) => sum + m.paga, 0);
        
        // Mostra mensilità selezionate
        mensilitaSelezionate.innerHTML = selectedMensilita.map(m => `
            <div class="mensilita-item">
                <span>Mensilità ID: ${m.id}</span>
                <span>€${m.paga.toFixed(2)}</span>
            </div>
        `).join('');
        
                totaleMultiplo.textContent = totale.toFixed(2);
        mensilitaIdsInput.value = selectedMensilita.map(m => m.id).join(',');
        tutorIdMultipliInput.value = currentTutorId;
                document.getElementById('data-pagamento-multipli').value = new Date().toISOString().split('T')[0];

        modaleMultipli.style.display = 'block';
    });
// Aggiungi listener ai bottoni "Paga" e "Reset"
const addListenersToButtons = () => {
    // Listener per bottoni "Paga"
    document.querySelectorAll('.paga-btn').forEach(button => {
        button.addEventListener('click', () => {
            const mensilitaId = button.dataset.id;
            const importoPaga = button.dataset.paga;

            // Popola il modale con i dati
            mensilitaIdInput.value = mensilitaId;
            importoPagaInput.value = importoPaga;
            notePagaInput.value = '';
            
            // Imposta data di oggi
            document.getElementById('data-pagamento').value = new Date().toISOString().split('T')[0];

            modalePaga.style.display = 'block';
        });
    });

    // Listener per bottoni "Reset" - MODIFICATO
    document.querySelectorAll('.reset-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.stopPropagation(); // Previene la propagazione dell'evento
            const id = button.getAttribute('data-id');
            
            if (confirm('Vuoi davvero resettare lo stato di pagamento per questa mensilità?')) {
                fetch('scripts/reset_pagamento_tutor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(id)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Reset effettuato con successo!', 'success');
                        loadDashboard();
                        // Ricarica i dati del tutor corrente
                        if (currentTutorId) {
                            viewTutor(currentTutorId, tutorName.textContent);
                        }
                    } else {
                        showNotification('Errore: ' + (data.message || 'Impossibile effettuare il reset.'), 'error');
                    }
                })
                .catch(() => showNotification('Errore di rete.', 'error'));
            }
        });
    });
};

    // Chiudi il modale paga
    window.chiudiModalePaga = function () {
        modalePaga.style.display = 'none';
    };

    // Chiudi il modale multipli
    window.chiudiModaleMultipli = function() {
        document.getElementById('modale-paga-multipli').style.display = 'none';
    };

    // Salva i dati del pagamento singolo
    formPaga.addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(formPaga);

        fetch('scripts/process_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Pagamento registrato con successo!', 'success');
                chiudiModalePaga();
                loadDashboard();
                // Ricarica i dati del tutor corrente
                if (currentTutorId) {
                    viewTutor(currentTutorId, tutorName.textContent);
                }
            } else {
                showNotification(data.message || 'Errore nel pagamento', 'error');
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showNotification('Si è verificato un problema. Riprova più tardi.', 'error');
        });
    });

    // Salva pagamenti multipli
    document.getElementById('form-paga-multipli').addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);

        fetch('scripts/process_multiple_payments.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Pagamenti multipli registrati con successo!', 'success');
                chiudiModaleMultipli();
                loadDashboard();
                // Ricarica i dati del tutor corrente
                if (currentTutorId) {
                    viewTutor(currentTutorId, tutorName.textContent);
                }
            } else {
                showNotification(data.message || 'Errore nei pagamenti multipli', 'error');
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showNotification('Si è verificato un problema. Riprova più tardi.', 'error');
        });
    });

    // Export Excel
    document.getElementById('export-excel').addEventListener('click', () => {
        const anno = document.getElementById('filter-anno').value;
        const stato = document.getElementById('filter-stato').value;
        
        window.location.href = `scripts/export_pagamenti_tutor.php?anno=${anno}&stato=${stato}`;
    });

    // Filtri
    document.getElementById('filter-anno').addEventListener('change', loadDashboard);
    document.getElementById('filter-stato').addEventListener('change', loadDashboard);

 

    // Chiudi modali cliccando fuori
    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });



});

// Funzione per aprire il modale nuovo tutor
document.getElementById('add-tutor-btn').addEventListener('click', () => {
    document.getElementById('modal-tutor-title').textContent = 'Nuovo Tutor';
    document.getElementById('form-tutor').reset();
    document.getElementById('tutor-id').value = '';
    document.getElementById('modale-tutor').style.display = 'block';
});

// Funzione per chiudere il modale tutor
window.chiudiModaleTutor = function() {
    document.getElementById('modale-tutor').style.display = 'none';
};

// Funzione per mostrare il dettaglio delle ore
window.showOreDetails = function(mese, oreSingole, oreGruppo, mezzeOreSingole, mezzeOreGruppo) {
    document.getElementById('mese-dettaglio').textContent = mese;
    document.getElementById('ore-singole-dettaglio').textContent = oreSingole || 0;
    document.getElementById('ore-gruppo-dettaglio').textContent = oreGruppo || 0;
    document.getElementById('mezze-ore-singole-dettaglio').textContent = mezzeOreSingole || 0;
    document.getElementById('mezze-ore-gruppo-dettaglio').textContent = mezzeOreGruppo || 0;
    
    // Calcola i totali
    const totaleOreSingole = (oreSingole || 0) + Math.floor((mezzeOreSingole || 0) / 2);
    const totaleOreGruppo = (oreGruppo || 0) + Math.floor((mezzeOreGruppo || 0) / 2);
    
    document.getElementById('totale-ore-singole').textContent = totaleOreSingole;
    document.getElementById('totale-ore-gruppo').textContent = totaleOreGruppo;
    
    document.getElementById('modale-ore-dettaglio').style.display = 'block';
};

// Funzione per chiudere il modale dettaglio ore
window.chiudiModaleOreDettaglio = function() {
    document.getElementById('modale-ore-dettaglio').style.display = 'none';
};

// Funzione per modificare un tutor
window.editTutor = function(tutorId) {
    fetch(`scripts/get_tutor_data.php?id=${tutorId}`)  // Cambiato da get_tutor_info.php
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modal-tutor-title').textContent = 'Modifica Tutor';
                document.getElementById('tutor-id').value = tutorId;
                document.getElementById('tutor-nome').value = data.tutor.nome || '';
                document.getElementById('tutor-cognome').value = data.tutor.cognome || '';
                document.getElementById('tutor-email').value = data.tutor.email || '';
                document.getElementById('tutor-telefono').value = data.tutor.telefono || '';
                document.getElementById('modale-tutor').style.display = 'block';
            } else {
                showNotification('Errore nel caricamento dei dati del tutor', 'error');
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showNotification('Errore nel caricamento dei dati', 'error');
        });
};


// Funzione per eliminare un tutor
window.deleteTutor = function(tutorId, nome) {
    if (confirm(`Sei sicuro di voler eliminare il tutor ${nome}?\n\nATTENZIONE: Verranno eliminati anche tutti i dati associati (lezioni, pagamenti, ecc.)`)) {
        fetch(`scripts/delete_tutor.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `tutor_id=${tutorId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Tutor eliminato con successo', 'success');
                loadDashboard();
            } else {
                showNotification(data.message || 'Errore durante l\'eliminazione', 'error');
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showNotification('Errore durante l\'eliminazione', 'error');
        });
    }
};

// Gestione submit form tutor
document.getElementById('form-tutor').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formElement = e.target;
    const formData = new FormData(formElement);
    const tutorId = formData.get('tutor_id');
    const isEdit = tutorId && tutorId !== '';
    
    // Debug
    console.group('Debug invio form');
    console.log('Form element:', formElement);
    console.log('Form action:', formElement.action);
    console.log('Form method:', formElement.method);
    console.log('Target URL:', `scripts/${isEdit ? 'update_tutor' : 'create_tutor'}.php`);
    
    // Mostra tutti i dati del form
    console.log('FormData contents:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    console.groupEnd();
    
    try {
        const response = await fetch(`scripts/${isEdit ? 'update_tutor' : 'create_tutor'}.php`, {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Risposta non valida dal server: ' + text);
        }
        
        console.log('Parsed response:', data);
        
        if (data.success) {
            showNotification(
                isEdit ? 'Tutor aggiornato con successo' : 'Tutor aggiunto con successo', 
                'success'
            );
            chiudiModaleTutor();
            loadDashboard();
        } else {
            console.error('Server error:', data);
            showNotification(data.message || 'Errore durante il salvataggio', 'error');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showNotification(`Errore: ${error.message}`, 'error');
    }
});
</script>

<!-- Stili per le notifiche -->
<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    z-index: 9999;
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.notification-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.notification-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.notification i {
    font-size: 1.2em;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        margin: 20px 10px;
        padding: 20px;
    }
    
    .dashboard-summary {
        grid-template-columns: 1fr;
    }
    
    #search-tutor {
        width: 100%;
        max-width: 400px;
    }
    
    .filters-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-section select,
    .btn-export {
        width: 100%;
    }
    
    .summary-table {
        font-size: 0.9em;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px auto;
        padding: 20px;
    }
    
    .notification {
        right: 10px;
        left: 10px;
        transform: translateY(-100%);
    }
    
    .notification.show {
        transform: translateY(0);
    }
}

/* Bottone Nuovo Tutor */
.btn-add-tutor {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-add-tutor:hover {
    background: linear-gradient(135deg, #5a6eea 0%, #6a3d9c 100%);
    transform: translateY(-1px);
}

/* Bottoni azione nella tabella */
.btn-edit-tutor,
.btn-delete-tutor {
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.85em;
    margin-left: 5px;
    transition: all 0.2s;
}

.btn-edit-tutor {
    background: #ffc107;
    color: #212529;
}

.btn-edit-tutor:hover {
    background: #e0a800;
    transform: translateY(-1px);
}

.btn-delete-tutor {
    background: #dc3545;
    color: white;
}

.btn-delete-tutor:hover {
    background: #c82333;
    transform: translateY(-1px);
}

/* Campo data */
input[type="date"] {
    width: 100%;
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 1em;
    transition: border-color 0.3s;
    font-family: 'Segoe UI', Arial, sans-serif;
}

input[type="date"]:focus {
    border-color: #667eea;
    outline: none;
}

/* Stile per webkit browsers (Chrome, Safari) */
input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    font-size: 1.2em;
    opacity: 0.7;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}

/* Bottone Info Ore */
.btn-info-ore {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    padding: 0;
    margin-left: 5px;
    font-size: 0.9em;
    transition: transform 0.2s;
}

.btn-info-ore:hover {
    transform: scale(1.1);
}

/* Modale Dettaglio Ore */
.ore-dettaglio-content {
    padding: 20px 0;
}

.ore-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.ore-row.totale {
    border-bottom: none;
    margin-top: 10px;
    padding-top: 15px;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
}

.ore-label {
    color: #333;
    font-weight: 500;
}

.ore-value {
    color: #007bff;
    font-weight: 600;
}

.ore-row.totale .ore-value {
    color: #28a745;
    font-size: 1.1em;
}
</style>

</body>
</html>