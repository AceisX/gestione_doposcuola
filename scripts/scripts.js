// Funzione per aprire il modale
function openReportModal() {
    document.getElementById('report-modal').style.display = 'block';
}

// Funzione per chiudere il modale
function closeReportModal() {
    document.getElementById('report-modal').style.display = 'none';
}

// Chiude il modale se l'utente clicca fuori
window.onclick = function (event) {
    const modal = document.getElementById('report-modal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const infoButtons = document.querySelectorAll('.info-btn');
    const infoModal = document.getElementById('info-modal');
    const infoContent = document.getElementById('info-content');

    // Funzione per aprire il modale INFO
    infoButtons.forEach(button => {
        button.addEventListener('click', () => {
            const alunnoId = button.getAttribute('data-id');

            // Richiedi i dettagli dell'alunno tramite AJAX
            fetch(`scripts/get_alunno_info.php?id=${alunnoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        infoContent.innerHTML = `
                            <div class="info-grid">
                                <div class="info-column">
                                    <h3>Info Alunno</h3>
                                    <p><strong>Nome Completo:</strong> ${data.alunno.nome_completo}</p>
                                    <p><strong>Scuola:</strong> ${data.alunno.scuola}</p>
                                    <p><strong>Pacchetto:</strong> ${data.alunno.pacchetto}</p>
                                    <p><strong>Prezzo Pagato:</strong> €${data.alunno.prezzo_finale}</p>
                                    <p><strong>Stato:</strong> ${data.alunno.stato}</p>
                                    <p><strong>Data Iscrizione:</strong> ${data.alunno.data_iscrizione}</p>
                                </div>
                                <div class="info-column">
                                    <h3>Info Genitore</h3>
                                    <p><strong>Nome:</strong> ${data.genitore.nome_completo}</p>
                                    <p><strong>Residenza:</strong> ${data.genitore.residenza}</p>
                                    <p><strong>Codice Fiscale:</strong> ${data.genitore.codice_fiscale}</p>
                                    <p><strong>Telefono:</strong> ${data.genitore.telefono}</p>
                                </div>
                            </div>
                            <div class="info-section">
                                <h3>Pagamenti</h3>
                                <table class="info-table">
                                    <thead>
                                        <tr>
                                            <th>Data Pagamento</th>
                                            <th>Mese Pagato</th>
                                            <th>Tipologia</th>
                                            <th>Totale Pagato</th>
                                            <th>Ore Effettuate</th>
                                            <th>Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.pagamenti.map(pagamento => `
                                            <tr>
                                                <td>${pagamento.data_pagamento}</td>
                                                <td>${pagamento.mese_pagato}</td>
                                                <td>${pagamento.tipologia}</td>
                                                <td>€${pagamento.totale_pagato}</td>
                                                <td>${pagamento.ore_effettuate}</td>
                                                <td>
                                                    <button onclick="eliminaPagamento(${pagamento.id})"><i class="fa-solid fa-trash-can" style="color: #ffffff;"></i></button>
                                                </td>
                                            </tr>`).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        infoContent.innerHTML = `<p>Errore nel caricamento dei dati.</p>`;
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    infoContent.innerHTML = `<p>Errore nel caricamento dei dati.</p>`;
                });

            // Mostra il modale
            infoModal.style.display = 'block';
        });
    });

    // Funzione per chiudere il modale INFO
    window.closeInfoModal = function () {
        infoModal.style.display = 'none';
    };

    // Chiudere il modale cliccando fuori
    window.addEventListener('click', (event) => {
        if (event.target === infoModal) {
            infoModal.style.display = 'none';
        }
    });


    // Funzione per eliminare un pagamento
    window.eliminaPagamento = function (pagamentoId) {
        if (confirm("Sei sicuro di voler eliminare questo pagamento?")) {
            fetch(`scripts/elimina_pagamento.php?id=${pagamentoId}`, {
                method: 'POST'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Pagamento eliminato con successo.");
                        location.reload(); // Ricarica la pagina per aggiornare i dati
                    } else {
                        alert("Errore durante l'eliminazione del pagamento.");
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    alert("Si è verificato un errore durante l'eliminazione del pagamento.");
                });
        }
    };
});

  document.addEventListener('DOMContentLoaded', () => {
        const editButtons = document.querySelectorAll('.edit-btn');
        const editModal = document.getElementById('edit-modal');
        const editForm = document.getElementById('edit-form');
        
        // Funzione per aprire il modale EDIT
        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const alunnoId = button.getAttribute('data-id');
                
                // Caricare i dati dell'alunno tramite AJAX
                fetch(`scripts/get_alunno_info.php?id=${alunnoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('edit-alunno-id').value = alunnoId;
                            document.getElementById('edit-nome').value = data.alunno.nome;
                            document.getElementById('edit-cognome').value = data.alunno.cognome;
                            document.getElementById('edit-scuola').value = data.alunno.scuola;
                            document.getElementById('edit-id-pacchetto').value = data.alunno.id_pacchetto;
                            document.getElementById('edit-stato').value = data.alunno.stato;
                            document.getElementById('edit-nome-genitore').value = data.genitore.nome_completo;
                            document.getElementById('edit-residenza').value = data.genitore.residenza;
                            document.getElementById('edit-codice-fiscale').value = data.genitore.codice_fiscale;
                            document.getElementById('edit-telefono').value = data.genitore.telefono;
                            
                            editModal.style.display = 'block';
                        }
                    });
            });
        });

        // Chiusura del modale EDIT
        window.closeEditModal = function () {
            editModal.style.display = 'none';
        };

        // Gestione del submit del form EDIT
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(editForm);

            fetch('scripts/edit_alunno.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Modifiche salvate con successo!');
                    location.reload(); // Ricarica la pagina
                } else {
                    alert('Errore durante il salvataggio delle modifiche.');
                }
            });
        });
    });
	
    document.addEventListener('DOMContentLoaded', () => {
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', () => {
                const alunnoId = button.getAttribute('data-id');

                // Mostrare una finestra di conferma
                if (confirm("Sei sicuro di voler eliminare questo alunno?")) {
                    // Effettua la richiesta di eliminazione
                    fetch(`scripts/delete_alunno.php?id=${alunnoId}`, {
                        method: 'GET',
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Alunno eliminato con successo!");
                            location.reload(); // Ricarica la pagina
                        } else {
                            alert(data.message || "Errore durante l'eliminazione dell'alunno.");
                        }
                    });
                }
            });
        });
    });
	
document.addEventListener('DOMContentLoaded', () => {
    const pagamentoButtons = document.querySelectorAll('.pagamento-btn');
    const pagamentoModal = document.getElementById('pagamentoModale');
    const alunnoIdField = document.getElementById('pagamento-alunno-id');
	
console.log("Pulsanti PAGAMENTO trovati:", pagamentoButtons);
console.log("Modale trovata:", pagamentoModal);
console.log("Campo nascosto alunno_id trovato:", alunnoIdField);

    if (!pagamentoModal) {
        console.error("Errore: Modale con ID 'pagamentoModal' non trovato!");
        return;
    }

    pagamentoButtons.forEach(button => {
        button.addEventListener('click', () => {
            const alunnoId = button.getAttribute('data-id');
            console.log('ID Alunno selezionato:', alunnoId);

            if (alunnoId) {
                if (alunnoIdField) {
                    alunnoIdField.value = alunnoId;
                } else {
                    console.error("Errore: Campo nascosto 'alunno_id' non trovato!");
                }
                pagamentoModal.style.display = 'block'; // Mostra la modale
            } else {
                console.error('Errore: il pulsante PAGAMENTO non ha un data-id valido.');
            }
        });
    });

    // Logica per chiudere la modale
    const closePagamentoButton = pagamentoModal.querySelector('.close-btn');
    if (closePagamentoButton) {
        closePagamentoButton.addEventListener('click', () => {
            pagamentoModal.style.display = 'none';
        });
    } else {
        console.error("Errore: Pulsante di chiusura non trovato all'interno del modale.");
    }

    // Chiudi la modale cliccando fuori dal contenuto
    window.addEventListener('click', (event) => {
        if (event.target === pagamentoModal) {
            pagamentoModal.style.display = 'none';
        }
    });
});


