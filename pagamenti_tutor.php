<?php
require_once 'config.php'; // Collegamento al database
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamenti Tutor</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header>
        <img src="img/logo.png" alt="Logo" height="75px" style="padding-left: 20px;">
        <h1>Gestione Tutor e Pagamenti</h1>
        <a href="scripts/logout.php">Logout</a>
    </header>

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
                        <th>Azioni</th>
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
                                    <td class="${row.stato === 1 ? 'pagato' : 'non-pagato'}">€${row.paga}</td>
                                    <td>${row.ore_singole}</td>
                                    <td>${row.ore_gruppo}</td>
                                    <td>${row.data_pagamento || '-'}</td>
									<td>${row.note || '-'}</td>
                                    <td>
                                        ${row.stato === 1 ? '<button class="reset-btn" data-id="' + row.id + '">Reset</button>' : '<button class="paga-btn" data-id="' + row.id + '" data-paga="' + row.paga + '">Paga</button>'}
                                    </td>
                                </tr>
                            `)
                            .join('');

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

        document.querySelectorAll('.reset-btn').forEach(button => {
            button.addEventListener('click', () => {
                const mensilitaId = button.dataset.id;

                fetch(`scripts/reset_payment.php?id=${mensilitaId}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Pagamento reimpostato.');
                            location.reload(); // Ricarica la pagina per aggiornare i dati
                        } else {
                            alert('Errore durante il reset del pagamento.');
                        }
                    });
            });
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