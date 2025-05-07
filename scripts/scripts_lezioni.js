document.addEventListener('DOMContentLoaded', () => {
    // Elenco dei giorni festivi (aggiungi i tuoi giorni festivi qui)
   const publicHolidays = [
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

    // Funzione per verificare se una data è un fine settimana o un giorno festivo
    function isInvalidDate(date) {
        if (!date) return true; // Se la data non è valida, restituisci errore
        const selectedDate = new Date(date);
        const dayOfWeek = selectedDate.getDay(); // 0 = Domenica, 6 = Sabato

        // Controlla se è sabato, domenica o un giorno festivo
        return dayOfWeek === 0 || dayOfWeek === 6 || publicHolidays.includes(date);
    }

    // Recupera il form e il campo data
    const lessonForm = document.getElementById('lesson-form');
    const lessonDateInput = document.getElementById('lesson-date');

    if (lessonForm && lessonDateInput) {
        // Aggiungi l'evento submit al modulo
        lessonForm.addEventListener('submit', (event) => {
            const selectedDate = lessonDateInput.value;

            if (isInvalidDate(selectedDate)) {
                // Mostra un alert e blocca l'invio del modulo
                alert('Errore: Le lezioni non possono essere programmate nei fine settimana o nei giorni festivi.');
                event.preventDefault();
            }
        });
    } else {
        console.error('Errore: Elemento "lesson-form" o "lesson-date" non trovato.');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    let currentSlot = null;

    // Apre il menu per selezionare gli alunni
    document.querySelectorAll('.add-student-btn').forEach(button => {
        button.addEventListener('click', () => {
            currentSlot = button.closest('.slot-group');

            if (!currentSlot) {
                console.error("Errore: Slot non trovato per il pulsante cliccato.");
                return;
            }

            // Resetta solo le selezioni relative a questo slot
            const selectedStudentIds = currentSlot.querySelector('.student-ids').value.split(',');
            document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                checkbox.checked = selectedStudentIds.includes(checkbox.value);
            });

            document.getElementById('student-modal').style.display = 'block';
        });
    });

    // Chiude il menu selezione studenti
    document.getElementById('close-student-modal').addEventListener('click', () => {
        document.getElementById('student-modal').style.display = 'none';
    });

    // Salva gli studenti selezionati
    document.getElementById('save-students').addEventListener('click', () => {
        if (!currentSlot) {
            console.error('Errore: Nessuno slot attivo selezionato.');
            return;
        }

        // Ottieni gli studenti selezionati
        const selectedStudents = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(
            checkbox => ({
                id: checkbox.value,
                name: checkbox.closest('div').querySelector('label').textContent.trim()
            })
        );

        if (selectedStudents.length === 0) {
            console.warn("Nessun studente selezionato.");
        }

        // Debug: Mostra gli studenti selezionati
        console.log("Studenti selezionati:", selectedStudents);

        // Aggiorna la lista degli studenti selezionati
        const studentContainer = currentSlot.querySelector('.selected-students');
        if (!studentContainer) {
            console.error("Errore: Contenitore studenti non trovato nello slot corrente.");
            return;
        }

        studentContainer.innerHTML = ''; // Resetta la lista visibile
        selectedStudents.forEach(student => {
            const studentItem = document.createElement('div');
            studentItem.className = 'student-item'; // Aggiungiamo una classe per styling
            studentItem.textContent = student.name;
            studentContainer.appendChild(studentItem);
        });

        // Aggiorna il campo nascosto con gli ID degli studenti
        const hiddenInput = currentSlot.querySelector('.student-ids');
        if (!hiddenInput) {
            console.error("Errore: Campo nascosto non trovato nello slot corrente.");
            return;
        }

        hiddenInput.value = selectedStudents.map(student => student.id).join(',');

        // Debug: Campo nascosto aggiornato
        console.log("Campo nascosto aggiornato:", hiddenInput.value);

        // Chiudi il modale
        const modal = document.getElementById('student-modal');
        if (modal) {
            modal.style.display = 'none';
            console.log("Modale chiuso correttamente.");
        } else {
            console.error("Errore: Modale non trovato.");
        }
    });
});



// Filtro studenti
function filterStudents() {
    const search = document.getElementById('student-search').value.toLowerCase();
    document.querySelectorAll('#student-list label').forEach(label => {
        const text = label.textContent.toLowerCase();
        label.parentElement.style.display = text.includes(search) ? '' : 'none';
    });
}


// Gestione apertura e chiusura modale per aggiungere lezioni
document.getElementById('add-lesson-fixed-btn').addEventListener('click', () => {
    document.getElementById('add-lesson-modal').style.display = 'block';
});

document.getElementById('close-add-lesson').addEventListener('click', () => {
    document.getElementById('add-lesson-modal').style.display = 'none';
});

window.onclick = function (event) {
    const studentModal = document.getElementById('student-modal');
    const lessonModal = document.getElementById('add-lesson-modal');
    if (event.target === studentModal) studentModal.style.display = 'none';
    if (event.target === lessonModal) lessonModal.style.display = 'none';
};

document.addEventListener('DOMContentLoaded', () => {
    const lessonForm = document.getElementById('lesson-form');
    const lessonDateInput = document.getElementById('lesson-date');
    const tutorSelect = document.getElementById('tutor-select');

    lessonForm.addEventListener('submit', (event) => {
        const selectedDate = lessonDateInput.value;
        const selectedTutor = tutorSelect.value;

        if (!selectedDate || !selectedTutor) {
            alert('Seleziona una data e un tutor.');
            event.preventDefault();
            return;
        }

        // Effettua una richiesta AJAX per verificare se il tutor ha già una lezione registrata
        fetch(`../scripts/check_tutor_availability.php?tutor_id=${selectedTutor}&date=${selectedDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.hasLesson) {
                    // Mostra il messaggio di conferma
                    const confirmMessage = `${data.tutorName} ha già una lezione registrata il ${data.date}. Sei sicuro di voler aggiungere un'altra lezione?`;
                    const confirmed = confirm(confirmMessage);

                    if (!confirmed) {
                        // Blocca l'invio del modulo
                        event.preventDefault();
                        return;
                    }
                }

                // Se confermato o il tutor non ha lezioni, invia il modulo
                lessonForm.submit();
            })
            .catch(error => {
                console.error('Errore durante il controllo della disponibilità del tutor:', error);
                alert('Si è verificato un errore durante il controllo della disponibilità del tutor.');
                event.preventDefault();
            });

        // Impedisce l'invio immediato del modulo finché non viene completata la verifica
        event.preventDefault();
    });
});

    // Script per il modale "Aggiungi Tutor"
    const addTutorBtn = document.getElementById('add-tutor-btn');
    const addTutorModal = document.getElementById('add-tutor-modal');
    const closeAddTutor = document.getElementById('close-add-tutor');

    // Apertura del modale "Aggiungi Tutor"
    addTutorBtn.addEventListener('click', () => {
        addTutorModal.style.display = 'block';
    });

    // Chiusura del modale "Aggiungi Tutor"
    closeAddTutor.addEventListener('click', () => {
        addTutorModal.style.display = 'none';
    });

    // Chiudi il modale cliccando fuori dal contenuto
    window.onclick = function(event) {
        if (event.target === addTutorModal) {
            addTutorModal.style.display = 'none';
        }
    };

document.addEventListener('DOMContentLoaded', () => {
    const reportModal = document.getElementById('report-alunno-modal');
    const resultModal = document.getElementById('report-result-modal');
    const reportBtn = document.getElementById('report-alunno-btn');
    const closeReportModal = document.getElementById('close-report-modal');
    const closeResultModal = document.getElementById('close-result-modal');
    const generateReportBtn = document.getElementById('generate-report_a');
    const alunnoSearch = document.getElementById('alunno-search');
    const alunnoList = document.getElementById('alunno-list');
    const meseInput = document.getElementById('mese-report');
    const reportContent = document.getElementById('report-content');
    const downloadReport = document.getElementById('download-report');

    // Mostra il Modale per Generare il Report
    reportBtn.addEventListener('click', () => {
        reportModal.style.display = 'block';
    });

    // Chiudi il Modale
    closeReportModal.addEventListener('click', () => {
        reportModal.style.display = 'none';
    });

    closeResultModal.addEventListener('click', () => {
        resultModal.style.display = 'none';
    });

    // Genera il Report
    generateReportBtn.addEventListener('click', () => {
        const selectedAlunni = Array.from(document.querySelectorAll('.alunno-checkbox:checked')).map(cb => cb.value);
        const mese = meseInput.value;

        if (selectedAlunni.length === 0 || !mese) {
            alert('Seleziona almeno un alunno e un mese per generare il report.');
            return;
        }

        // Effettua una chiamata AJAX per ottenere i dati del report
        fetch(`../scripts/generate_report_a.php?mese=${mese}&alunni=${selectedAlunni.join(',')}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Formatta il mese (es. "Maggio 2025")
                const meseFormattato = data.mese;

                // Mostra i risultati nel secondo modale
                reportContent.innerHTML = data.data.map(alunno => `
				<p>
					<strong>${alunno.nome}</strong>: ${alunno.ore} ore nel mese di ${meseFormattato}
					<button class="info-btn" data-id="${alunno.id}">
					<i class="fa-solid fa-circle-info"></i>
					</button>
				</p>
				`).join('');

                // Imposta il link per scaricare il report completo
                downloadReport.href = `../scripts/generate_report_a.php?mese=${mese}&alunni=${selectedAlunni.join(',')}&export=1`;

                reportModal.style.display = 'none';
                resultModal.style.display = 'block';
            })
            .catch(error => {
                alert('Errore durante la generazione del report.');
                console.error(error);
            });
    });
});

// Funzione per filtrare gli alunni nella lista di ricerca
function filterAlunni() {
    const search = document.getElementById('alunno-search').value.toLowerCase();
    document.querySelectorAll('#alunno-list label').forEach(label => {
        const text = label.textContent.toLowerCase();
        label.parentElement.style.display = text.includes(search) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const infoModal = document.getElementById('info-modal');
    const infoContent = document.getElementById('info-content');

    // Event delegation per gestire i bottoni "Info"
    document.body.addEventListener('click', (event) => {
        const button = event.target.closest('.info-btn');
        if (button) {
            const alunnoId = button.getAttribute('data-id');

            // Richiedi i dettagli dell'alunno tramite AJAX
            fetch(`../scripts/get_alunno_info.php?id=${alunnoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostra i dettagli nel modale
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
                                                    <button class="delete-btn" onclick="eliminaPagamento(${pagamento.id})">
                                                        <i class="fa-solid fa-trash-can" style="color: #ffffff;"></i>
                                                    </button>
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
        }
    });

  // Funzione per chiudere il modale INFO
    window.closeInfoModal = function () {
        infoModal.style.display = 'none';
    };

// Chiudi il modale cliccando fuori dal contenuto
window.addEventListener('click', (event) => {
    const infoModal = document.getElementById('info-modal');
    if (infoModal && event.target === infoModal) {
        infoModal.style.display = 'none';
    }
});

// Associa il listener al bottone di chiusura
document.addEventListener('DOMContentLoaded', () => {
    const closeBtn = document.querySelector('#info-modal .close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeInfoModal);
    }
});

    // Funzione per eliminare un pagamento
    window.eliminaPagamento = function (pagamentoId) {
        if (confirm("Sei sicuro di voler eliminare questo pagamento?")) {
            fetch(`../scripts/elimina_pagamento.php?id=${pagamentoId}`, {
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