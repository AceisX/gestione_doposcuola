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
    const meseSelect = document.getElementById('mese-pacchetto');
    const oreEffField = document.getElementById('ore-eff');
    const pagamentoForm = document.getElementById('pagamento-form');
    const submitButton = pagamentoForm.querySelector('button[type="submit"]');

    // Funzioni di utilità per la gestione dei mesi
    function getMeseNome(numeroMese) {
        const mesi = {
            1: 'GENNAIO',
            2: 'FEBBRAIO',
            3: 'MARZO',
            4: 'APRILE',
            5: 'MAGGIO',
            6: 'GIUGNO',
            7: 'LUGLIO',
            8: 'AGOSTO',
            9: 'SETTEMBRE',
            10: 'OTTOBRE',
            11: 'NOVEMBRE',
            12: 'DICEMBRE'
        };
        return mesi[parseInt(numeroMese)] || '';
    }

    function getMeseNumero(meseName) {
        const mesi = {
            'GENNAIO': '01',
            'FEBBRAIO': '02',
            'MARZO': '03',
            'APRILE': '04',
            'MAGGIO': '05',
            'GIUGNO': '06',
            'LUGLIO': '07',
            'AGOSTO': '08',
            'SETTEMBRE': '09',
            'OTTOBRE': '10',
            'NOVEMBRE': '11',
            'DICEMBRE': '12'
        };
        return mesi[meseName.toUpperCase()] || '';
    }

    // Funzioni per le chiamate API
    async function getDettagliAlunno(alunnoId) {
        try {
            const response = await fetch(`scripts/get_alunno_info.php?id=${alunnoId}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Errore nel recupero dettagli alunno:', error);
            return { success: false };
        }
    }

    async function getPacchettoAlunno(alunnoId) {
        try {
            const response = await fetch(`scripts/get_pacchetto_alunno.php?alunno_id=${alunnoId}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Errore nel recupero pacchetto alunno:', error);
            return { success: false };
        }
    }

    async function getUltimoPagamento(alunnoId) {
        try {
            const response = await fetch(`scripts/get_ultimo_pagamento.php?alunno_id=${alunnoId}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Errore nel recupero ultimo pagamento:', error);
            return { success: false };
        }
    }

    async function checkMesePagato(alunnoId, meseName) {
        try {
            const response = await fetch(`scripts/check_mese_pagato.php?alunno_id=${alunnoId}&mese=${meseName}`);
            const data = await response.json();
            console.log('Risposta check_mese_pagato:', data);
            if (data.success) {
                return {
                    pagato: data.gia_pagato,
                    hasSaldo: data.is_saldo
                };
            }
            return { pagato: false, hasSaldo: false };
        } catch (error) {
            console.error('Errore nella verifica del mese pagato:', error);
            return { pagato: false, hasSaldo: false };
        }
    }

    async function getOreEffettuate(alunnoId, meseName) {
        try {
            const meseNumero = getMeseNumero(meseName);
            const response = await fetch(`scripts/get_ore_effettuate.php?alunno_id=${alunnoId}&mese=${meseNumero}&anno=${new Date().getFullYear()}`);
            const data = await response.json();
            
            if (data.success) {
                const ore = parseInt(data.ore_effettuate);
                oreEffField.value = ore;

                // Verifica se il mese è già stato pagato
                const statoPagamento = await checkMesePagato(alunnoId, meseName);
                
                if (statoPagamento.hasSaldo) {
                    // Se c'è un saldo, blocca tutto
                    oreEffField.setAttribute('readonly', true);
                    submitButton.disabled = true;
                    
                    // Aggiungi messaggio di avviso
                    const messaggioDiv = document.createElement('div');
                    messaggioDiv.className = 'alert alert-warning';
                    messaggioDiv.textContent = 'Questo mese è già stato saldato. Non sono possibili ulteriori pagamenti.';
                    
                    // Rimuovi eventuali messaggi precedenti
                    const vecchioMessaggio = pagamentoForm.querySelector('.alert');
                    if (vecchioMessaggio) {
                        vecchioMessaggio.remove();
                    }
                    
                    submitButton.parentElement.insertBefore(messaggioDiv, submitButton);
                } else {
                    // Riabilita l'input e il pulsante
                    oreEffField.removeAttribute('readonly');
                    submitButton.disabled = false;
                    
                    // Rimuovi eventuali messaggi
                    const vecchioMessaggio = pagamentoForm.querySelector('.alert');
                    if (vecchioMessaggio) {
                        vecchioMessaggio.remove();
                    }
                }
            }
        } catch (error) {
            console.error('Errore nella richiesta ore:', error);
            oreEffField.value = '0';
        }
    }

    // Event Listeners
    pagamentoButtons.forEach(button => {
        button.addEventListener('click', async () => {
            console.log('Pulsante pagamento cliccato');
            const alunnoId = button.getAttribute('data-id');
            console.log('Alunno ID:', alunnoId);
            
            if (alunnoId) {
                alunnoIdField.value = alunnoId;
                
                // Recupera dettagli alunno e pacchetto
                const [dettagliAlunno, pacchetto] = await Promise.all([
                    getDettagliAlunno(alunnoId),
                    getPacchettoAlunno(alunnoId)
                ]);

                console.log('Dettagli alunno:', dettagliAlunno);
                console.log('Dettagli pacchetto:', pacchetto);

                // Imposta l'id del pacchetto
                const pacchetto_id_field = document.getElementById('pagamento-pacchetto-id');
                if (pacchetto_id_field && pacchetto.success) {
                    pacchetto_id_field.value = pacchetto.id_pacchetto || '';
                    console.log('ID Pacchetto impostato:', pacchetto.id_pacchetto);
                }
                
                if (dettagliAlunno.success) {
                    // Imposta il nome dell'alunno nel modale
                    const studentNameDisplay = document.getElementById('student-name-display');
                    if (studentNameDisplay) {
                        studentNameDisplay.textContent = dettagliAlunno.alunno.nome_completo;
                    }

                    // Imposta il prezzo con gestione degli errori
                    const totale_pagato_field = document.getElementById('totale-pagato');
                    if (totale_pagato_field) {
                        let prezzoDefault = 0;
                        if (dettagliAlunno.alunno.prezzo_finale) {
                            prezzoDefault = parseFloat(dettagliAlunno.alunno.prezzo_finale);
                            if (isNaN(prezzoDefault)) {
                                prezzoDefault = 0;
                            }
                        }
                        console.log('Prezzo default calcolato:', prezzoDefault);
                        totale_pagato_field.value = prezzoDefault.toFixed(2);
                    }

                    // Imposta la data odierna
                    const dataPagamentoField = document.getElementById('data-pagamento');
                    if (dataPagamentoField) {
                        const oggi = new Date();
                        const anno = oggi.getFullYear();
                        const mese = String(oggi.getMonth() + 1).padStart(2, '0');
                        const giorno = String(oggi.getDate()).padStart(2, '0');
                        dataPagamentoField.value = `${anno}-${mese}-${giorno}`;
                    }
                }

                // Determina il mese default
                const pagamentoData = await getUltimoPagamento(alunnoId);
                let meseDefault;
                
                if (pagamentoData.success && pagamentoData.ultimo_mese_pagato) {
                    // Trova il mese successivo all'ultimo pagamento
                    for (let i = 1; i <= 12; i++) {
                        const meseName = getMeseNome(i);
                        if (pagamentoData.ultimo_mese_pagato.includes(meseName)) {
                            meseDefault = getMeseNome(((i % 12) + 1));
                            break;
                        }
                    }
                }
                
                if (!meseDefault) {
                    // Se non è stato trovato un ultimo pagamento, usa il mese corrente
                    meseDefault = getMeseNome(new Date().getMonth() + 1);
                }
                
                console.log('Mese default:', meseDefault);
                meseSelect.value = meseDefault;
                
                // Recupera le ore effettuate
                await getOreEffettuate(alunnoId, meseDefault);
                
                // Mostra il modale
                if (pagamentoModal) {
                    console.log('Mostro il modale');
                    pagamentoModal.style.display = 'block';
                } else {
                    console.error('Elemento modale non trovato!');
                }
            }
        });
    });

    // Gestione cambio mese
    if (meseSelect) {
        meseSelect.addEventListener('change', async () => {
            const alunnoId = alunnoIdField.value;
            const meseSelezionato = meseSelect.value;
            await getOreEffettuate(alunnoId, meseSelezionato);
        });
    }

    // Validazione form
    pagamentoForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const alunnoId = alunnoIdField.value;
        const meseSelezionato = meseSelect.value;
        const oreEffettuate = document.getElementById('ore-eff').value;
        
        // Validazione ore effettuate
        if (!oreEffettuate || oreEffettuate === '0') {
            alert('Inserire il numero di ore effettuate');
            document.getElementById('ore-eff').focus();
            return;
        }

        // Controlla lo stato del pagamento
        const statoPagamento = await checkMesePagato(alunnoId, meseSelezionato);
        
        // Se c'è già un saldo, blocca qualsiasi tipo di pagamento
        if (statoPagamento.hasSaldo) {
            alert('Questo mese è già stato saldato. Non sono possibili ulteriori pagamenti.');
            return;
        }
        
        // Se tutte le validazioni passano, invia il form
        pagamentoForm.submit();
    });

    // Gestione cambio tipologia pagamento
    document.querySelectorAll('input[name="tipologia"]').forEach(radio => {
        radio.addEventListener('change', async () => {
            const alunnoId = alunnoIdField.value;
            const meseSelezionato = meseSelect.value;
            await getOreEffettuate(alunnoId, meseSelezionato);
        });
    });

    // Gestione chiusura modale
    const closePagamentoButton = pagamentoModal.querySelector('.close-btn');
    if (closePagamentoButton) {
        closePagamentoButton.addEventListener('click', () => {
            pagamentoModal.style.display = 'none';
        });
    }

    // Chiusura cliccando fuori
    window.addEventListener('click', (event) => {
        if (event.target === pagamentoModal) {
            pagamentoModal.style.display = 'none';
        }
    });
});


document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterStato = document.getElementById('filterStato');
    const filterPacchetto = document.getElementById('filterPacchetto');
    const tableView = document.getElementById('tableView');
    const paymentsView = document.getElementById('paymentsView');

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const statoValue = filterStato.value.toLowerCase();
        const pacchettoValue = filterPacchetto.value.toLowerCase();

        // Seleziona tutte le righe di entrambe le tabelle
        const rows = document.querySelectorAll('.alunni-table tbody tr, .payments-table tbody tr');

        rows.forEach(row => {
            const nomeCell = row.querySelector('td:first-child').textContent.toLowerCase();
            let visible = true;

            // Applica filtro ricerca
            if (!nomeCell.includes(searchTerm)) {
                visible = false;
            }

            // Nella vista pagamenti, nascondi/mostra solo in base alla ricerca del nome
            if (row.closest('.payments-table')) {
                row.style.display = visible ? '' : 'none';
                return;
            }

            // Per la tabella principale, applica anche gli altri filtri
            if (visible && statoValue) {
                const statoCell = row.querySelector('.status').classList.contains('active') ? 'attivo' : 'disattivato';
                if (statoValue !== statoCell) {
                    visible = false;
                }
            }

            if (visible && pacchettoValue) {
                const pacchettoCell = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                if (!pacchettoCell.includes(pacchettoValue)) {
                    visible = false;
                }
            }

            row.style.display = visible ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', applyFilters);
    filterStato.addEventListener('change', applyFilters);
    filterPacchetto.addEventListener('change', applyFilters);
});



document.addEventListener('DOMContentLoaded', function() {
    // Funzione per formattare la data
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    // Funzione per chiudere il modale
    window.closePaymentModal = function() {
        document.getElementById('payment-details-modal').style.display = 'none';
    }

    // Funzione per eliminare un pagamento
    window.deletePagamento = async function(pagamentoId) {
        if (!confirm('Sei sicuro di voler eliminare questo pagamento?')) {
            return;
        }

        try {
            const response = await fetch(`scripts/elimina_pagamento.php?id=${pagamentoId}`, {
                method: 'POST'
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert(result.message || 'Errore durante l\'eliminazione del pagamento');
            }
        } catch (error) {
            console.error('Errore:', error);
            alert('Errore durante l\'eliminazione del pagamento');
        }
    }

    // Gestione click sulle celle dei pagamenti
    document.querySelectorAll('.payment-cell').forEach(cell => {
        cell.addEventListener('click', async function() {
            const studentId = this.dataset.studentId;
            const studentName = this.dataset.studentName;
            const month = this.dataset.month;
            const year = this.dataset.year;

            try {
                const response = await fetch(`scripts/get_payment_details.php?studentId=${studentId}&month=${month}&year=${year}`);
                const result = await response.json();

                if (result.success) {
                    const modal = document.getElementById('payment-details-modal');
                    const contentDiv = document.getElementById('payment-details-content');
                    document.getElementById('modal-student-name').textContent = studentName;

                    if (!result.data || result.data.length === 0) {
                        contentDiv.innerHTML = '<p>Nessun pagamento trovato per questo periodo.</p>';
                    } else {
                        contentDiv.innerHTML = result.data.map(payment => `
        <div class="payment-detail-card">
            <div class="payment-info">
                <span class="payment-amount">€${parseFloat(payment.totale_pagato).toFixed(2)}</span>
                <span class="payment-date">${formatDate(payment.data_pagamento)}</span>
                <span class="payment-method">${payment.metodo_pagamento || ''}</span>
                <span class="payment-type">${payment.tipologia}</span>
            </div>
            <button class="delete-btn" onclick="deletePagamento(${payment.id})">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `).join('');
                    }

                    modal.style.display = 'block';
                } else {
                    throw new Error(result.message || 'Errore nel caricamento dei dettagli pagamenti');
                }
            } catch (error) {
                console.error('Errore:', error);
                alert('Errore nella richiesta dei dettagli pagamenti');
            }
        });
    });

    // Chiudi il modale quando si clicca fuori
    window.onclick = function(event) {
        const modal = document.getElementById('payment-details-modal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
});

