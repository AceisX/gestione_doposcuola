<?php
require_once 'config.php'; // Collegamento al database

// Inizializziamo la sessione
session_start();

// Verifichiamo se l'utente è loggato
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
    <title>Pagamenti Tutor</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f6f8fa;
    margin: 0;
}


.container {
    max-width: 1000px;
    background: #fff;
    margin: 40px auto;
    border-radius: 16px;
    box-shadow: 0 2px 24px 0 #0002;
    padding: 30px 40px 40px 40px;
}

.search-bar {
    text-align: center;
    margin-bottom: 30px;
    position: relative;
}
#search-tutor {
    width: 350px;
    padding: 10px 20px;
    border-radius: 22px;
    border: 1px solid #ccc;
    outline: none;
    font-size: 1.1em;
    margin-bottom: 10px;
}
#search-results {
    list-style: none;
    padding: 0;
    margin: 0;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 350px;
    background: #fff;
    border: 1px solid #ddd;
    border-top: none;
    z-index: 120;
    box-shadow: 0 6px 24px 0 #0001;
}
#search-results li {
    padding: 10px 16px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}
#search-results li:hover {
    background: #f6f8fa;
}

.mensilita-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}
.mensilita-table th, .mensilita-table td {
    text-align: center;
    padding: 13px 6px;
    border-bottom: 1px solid #eee;
}
.mensilita-table th {
    background: #f3f3f3;
    color: #333;
    font-size: 1.06em;
}
.mensilita-table tr:last-child td {
    border-bottom: none;
}

.paga-badge {
    padding: 4px 14px;
    border-radius: 14px;
    font-weight: bold;
    font-size: 1.1em;
    display: inline-block;
    letter-spacing: .5px;
}
.paga-badge.pagato {
    background: #24b47e;
    color: #fff;
}
.paga-badge.non-pagato {
    background: #e74c3c;
    color: #fff;
}
button.paga-btn, button.reset-btn {
    padding: 4px 13px;
    border: none;
    border-radius: 13px;
    background: #3498db;
    color: #fff;
    cursor: pointer;
    font-weight: 600;
    margin: 0 3px;
    transition: background .2s;
}
button.paga-btn:hover {
    background: #217dbb;
}
button.reset-btn {
    background: #f39c12;
}
button.reset-btn:hover {
    background: #d35400;
}
.modal {
    display: none;
    position: fixed;
    z-index: 1500;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background: #0008;
}
.modal-content {
    background: #fff;
    margin: 70px auto;
    padding: 30px 38px 20px 38px;
    border-radius: 14px;
    width: 370px;
    position: relative;
}
.close-btn {
    position: absolute;
    right: 18px;
    top: 13px;
    font-size: 22px;
    color: #666;
    cursor: pointer;
}
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #333;
    font-weight: 500;
}
.form-group input[type="number"], .form-group textarea {
    width: 100%;
    padding: 7px 11px;
    border-radius: 7px;
    border: 1px solid #ccc;
    font-size: 1.08em;
}
.form-group textarea { resize: vertical; }
.btn {
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    background: #24b47e;
    color: #fff;
    font-size: 1.08em;
    font-weight: 600;
    cursor: pointer;
    margin-top: 5px;
    transition: background .2s;
}
.btn:hover { background: #17835a; }
</style>
<body>
<?php include __DIR__ . '/assets/header.html'; ?>   

 <div class="container">
        <!-- Barra di ricerca -->
        <div class="search-bar">
            <input type="text" id="search-tutor" placeholder="Cerca tutor per nome o cognome...">
            <ul id="search-results"></ul>
        </div>

        <!-- Informazioni del tutor -->
        <div id="tutor-info" style="display: none;">
            <h2 id="tutor-name"></h2>
            <table class="mensilita-table">
                <thead>
                    <tr>
                        <th>Mese</th>
                        <th>Paga</th>
                        <th>Ore Singole</th>
                        <th>Ore Gruppo</th>
                        <th>Data Pagamento</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody id="mensilita-rows">
                    <!-- Dati mensilità caricati dinamicamente -->
                </tbody>
            </table>
        </div>
    </div>
	
	
	<!-- Modale per il pagamento -->
<div id="modale-paga" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" onclick="chiudiModalePaga()">&times;</span>
        <h2>Paga Mensilità</h2>
        <form id="form-paga">
            <input type="hidden" id="mensilita-id" name="mensilita_id">
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
</body>

<script>
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

            // Nasconde i risultati di ricerca
            searchResults.innerHTML = '';
            searchInput.value = '';

            // Recupera i dati del tutor selezionato
            fetch(`scripts/get_tutor_details.php?tutor_id=${tutorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        tutorInfo.style.display = 'block';
                        tutorName.textContent = `${data.tutor.nome} ${data.tutor.cognome}`;
                       mensilitaRows.innerHTML = data.mensilita
    .map(row => `
        <tr>
            <td>${row.mese}</td>
            <td>
                <span class="paga-badge ${row.stato === 1 ? 'pagato' : 'non-pagato'}">
                    €${row.paga}
                </span>
            </td>
            <td>${row.ore_singole}</td>
            <td>${row.ore_gruppo}</td>
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

                        // Aggiungi listener ai bottoni "Paga" e "Reset"
                        addListenersToButtons();
                    } else {
                        alert('Impossibile recuperare i dati del tutor.');
                    }
                });
        }
    });

    // Aggiungi listener ai bottoni "Paga" e "Reset"
    const addListenersToButtons = () => {
        document.querySelectorAll('.paga-btn').forEach(button => {
            button.addEventListener('click', () => {
                const mensilitaId = button.dataset.id;
                const importoPaga = button.dataset.paga;

                // Popola il modale con i dati
                mensilitaIdInput.value = mensilitaId;
                importoPagaInput.value = importoPaga;
                notePagaInput.value = '';

                modalePaga.style.display = 'block';
            });
        });

  	//bottone reset
	document.body.addEventListener('click', function(e) {
    if (e.target.classList.contains('reset-btn')) {
        const id = e.target.getAttribute('data-id');
        if (confirm('Vuoi davvero resettare lo stato di pagamento per questa mensilità?')) {
            // Solo se l'utente conferma, parte il reset AJAX
            fetch('scripts/reset_pagamento_tutor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Reset effettuato!');
                    location.reload();
                } else {
                    alert('Errore: ' + (data.message || 'Impossibile effettuare il reset.'));
                }
            })
            .catch(() => alert('Errore di rete.'));
        }
        // Se l'utente clicca "Annulla", non succede nulla
    }
});
    };

    // Chiudi il modale paga
    window.chiudiModalePaga = function () {
        modalePaga.style.display = 'none';
    };

    // Salva i dati del pagamento
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
                    alert(data.message);
                    location.reload(); // Ricarica la pagina per aggiornare i dati
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un problema. Riprova più tardi.');
            });

        chiudiModalePaga();
    });

});
</script>
</html>