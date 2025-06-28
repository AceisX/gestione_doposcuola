// FUNZIONI GLOBALI (devono essere accessibili ovunque)

// Funzione per gestire il click su un elemento studente
function toggleStudentSelection(element, event) {
    // Se il click è stato fatto direttamente sulla checkbox, non fare nulla
    // questo permette alla checkbox di funzionare normalmente
    if (event.target.type === 'checkbox') {
        return;
    }
    
    const checkbox = element.querySelector('.student-checkbox');
    checkbox.checked = !checkbox.checked;
}

// Funzione per deselezionare tutti gli studenti
function deselectAllStudents() {
    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Gestione deseleziona tutti gli studenti
document.addEventListener('DOMContentLoaded', () => {
    const deselectAllBtn = document.getElementById('deselect-all-students');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', () => {
            deselectAllStudents();
        });
    }
});

// Filtro studenti
function filterStudents() {
    const search = document.getElementById('student-search').value.toLowerCase();
    document.querySelectorAll('#student-list label').forEach(label => {
        const text = label.textContent.toLowerCase();
        label.parentElement.style.display = text.includes(search) ? '' : 'none';
    });
}

// Funzione per filtrare gli alunni nella lista di ricerca
function filterAlunni() {
    const search = document.getElementById('alunno-search').value.toLowerCase();
    document.querySelectorAll('#alunno-list label').forEach(label => {
        const text = label.textContent.toLowerCase();
        label.parentElement.style.display = text.includes(search) ? '' : 'none';
    });
}

// Funzione per filtrare i tutor nel report - DEVE ESSERE GLOBALE
function filterTutorReport() {

        
     setTimeout(function() {
        const searchInput = document.getElementById('tutor-report-search');
        if (!searchInput) {
            console.error('Input tutor-report-search non trovato');
            return;
        }
        
        const searchTerm = searchInput.value.toLowerCase().trim();
        console.log('Termine di ricerca:', searchTerm);
        
        const tutorList = document.getElementById('tutor-report-list');
        if (!tutorList) {
            console.error('Lista tutor-report-list non trovata');
            return;
        }
        
        // Usa querySelectorAll invece di getElementsByClassName
        const tutorItems = tutorList.querySelectorAll('.tutor-report-item');
        console.log('Numero di tutor trovati:', tutorItems.length);
        
        tutorItems.forEach(function(item) {
            const label = item.querySelector('label');
            if (label) {
                const tutorName = label.textContent.toLowerCase();
                if (searchTerm === '' || tutorName.indexOf(searchTerm) !== -1) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            }
        });
    }, 10);
}

// Funzioni per i modal veloci
function openQuickTutorModal(date) {
    document.getElementById('quick-date').value = date;
    document.getElementById('quick-tutor-modal').style.display = 'block';
}

function closeQuickTutorModal() {
    document.getElementById('quick-tutor-modal').style.display = 'none';
    document.getElementById('quick-tutor-form').reset();
}

function openQuickStudentModal(date, tutorId, slot) {
    document.getElementById('quick-student-date').value = date;
    document.getElementById('quick-student-tutor').value = tutorId;
    document.getElementById('quick-student-slot').value = slot;
    document.getElementById('quick-student-modal').style.display = 'block';
}

function closeQuickStudentModal() {
    document.getElementById('quick-student-modal').style.display = 'none';
    document.getElementById('quick-student-form').reset();
}

function filterQuickStudents() {
    const searchValue = document.getElementById('quick-search').value.toLowerCase();
    const items = document.querySelectorAll('.student-quick-item');
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchValue) ? 'block' : 'none';
    });
}

// Funzioni per modal edit lezione
function closeEditLessonModal() {
    document.getElementById('edit-lesson-modal').style.display = 'none';
    document.getElementById('edit-lesson-form').reset();
}

function openEditLessonModal(lessonId, date, tutorId, slot, tutorName) {
    // Imposta i valori nascosti
    document.getElementById('edit-lesson-id').value = lessonId;
    document.getElementById('edit-date').value = date;
    document.getElementById('edit-tutor').value = tutorId;
    document.getElementById('edit-slot').value = slot;
    
    // Formatta la data in italiano
    const dateObj = new Date(date);
    const dateFormatted = dateObj.toLocaleDateString('it-IT', { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric' 
    });
    
    // Mappa degli slot
    const slotTimes = {
        1: '15:30-16:30',
        2: '16:30-17:30',
        3: '17:30-18:30'
    };
    
    // Imposta le info visualizzate
    document.getElementById('edit-info-date').textContent = dateFormatted;
    document.getElementById('edit-info-slot').textContent = slotTimes[slot];
    document.getElementById('edit-info-tutor').textContent = tutorName;
    
    // Carica i dettagli della lezione
    fetch(`../scripts/get_lesson_details.php?lesson_id=${lessonId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Imposta la checkbox mezza lezione
                document.getElementById('edit-half-lesson').checked = data.lesson.durata == 1;
                
                // Mostra gli alunni attuali
                const container = document.getElementById('current-students-list');
                if (!data.lesson.students || data.lesson.students.length === 0) {
                    container.innerHTML = '<p style="color: #a0aec0; font-style: italic;">Nessun alunno iscritto</p>';
                } else {
                    container.innerHTML = data.lesson.students.map(student => `
                        <div class="student-item" style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-user-graduate" style="margin-right: 10px; color: #667eea;"></i>
                                <span>${student.nome}</span>
                            </div>
                            <button type="button" 
                                    onclick="removeStudentFromLesson(${student.id})" 
                                    class="btn-remove"
                                    style="background: #f56565; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `).join('');
                }
                
                // Carica alunni disponibili
                loadAvailableStudents(data.lesson.students || []);
                
                // Mostra il modal
                document.getElementById('edit-lesson-modal').style.display = 'block';
            } else {
                alert('Errore nel caricamento dei dettagli');
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Errore nel caricamento dei dettagli');
        });
}

function loadAvailableStudents(currentStudents) {
    fetch('../scripts/get_all_students.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentIds = currentStudents.map(s => s.id);
                const availableStudents = data.students.filter(s => !currentIds.includes(s.id));
                
                const container = document.getElementById('edit-available-students');
                if (availableStudents.length === 0) {
                    container.innerHTML = '<p style="color: #a0aec0; font-style: italic; padding: 10px;">Tutti gli alunni sono già iscritti</p>';
                } else {
                    container.innerHTML = availableStudents.map(student => `
                        <div class="student-item edit-available-item" style="padding: 10px; border-bottom: 1px solid #edf2f7;">
                            <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; margin: 0;">
                                <span style="flex: 1;">
                                    <i class="fas fa-user-plus" style="margin-right: 10px; color: #48bb78;"></i>
                                    ${student.nome}
                                </span>
                                <button type="button" 
                                        onclick="addStudentToLesson(${student.id})" 
                                        class="btn-small"
                                        style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); padding: 6px 15px;">
                                    Aggiungi
                                </button>
                            </label>
                        </div>
                    `).join('');
                }
            }
        });
}

function removeStudentFromLesson(studentId) {
    if (!confirm('Vuoi rimuovere questo alunno dalla lezione?')) return;
    
    const lessonId = document.getElementById('edit-lesson-id').value;
    
    fetch('../scripts/remove_student_from_lesson.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ lesson_id: lessonId, student_id: studentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.message.includes('eliminata')) {
                closeEditLessonModal();
                location.reload();
            } else {
                // Ricarica i dati del modal
                const date = document.getElementById('edit-date').value;
                const tutorId = document.getElementById('edit-tutor').value;
                const slot = document.getElementById('edit-slot').value;
                const tutorName = document.getElementById('edit-info-tutor').textContent;
                openEditLessonModal(lessonId, date, tutorId, slot, tutorName);
            }
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Si è verificato un errore');
    });
}

function addStudentToLesson(studentId) {
    const lessonId = document.getElementById('edit-lesson-id').value;
    
    fetch('../scripts/add_student_to_lesson.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ lesson_id: lessonId, student_id: studentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ricarica i dati del modal
            const date = document.getElementById('edit-date').value;
            const tutorId = document.getElementById('edit-tutor').value;
            const slot = document.getElementById('edit-slot').value;
            const tutorName = document.getElementById('edit-info-tutor').textContent;
            openEditLessonModal(lessonId, date, tutorId, slot, tutorName);
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Si è verificato un errore');
    });
}

function deleteLessonSlot() {
    if (!confirm('Sei sicuro di voler eliminare questa lezione?')) return;
    
    const lessonId = document.getElementById('edit-lesson-id').value;
    
    fetch('../scripts/delete_single_lesson.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ lesson_id: lessonId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeEditLessonModal();
            location.reload();
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Si è verificato un errore');
    });
}

function filterEditStudents() {
    const searchValue = document.getElementById('edit-search').value.toLowerCase();
    const items = document.querySelectorAll('.edit-available-item');
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchValue)) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease';
        } else {
            item.style.display = 'none';
        }
    });
}

// Funzione per chiudere il modale INFO
window.closeInfoModal = function () {
    const infoModal = document.getElementById('info-modal');
    if (infoModal) {
        infoModal.style.display = 'none';
    }
};

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
                location.reload();
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

// EVENTI DOMCONTENTLOADED
document.addEventListener('DOMContentLoaded', () => {


    const savedMonth = sessionStorage.getItem('lastSelectedMonth');
    if (savedMonth) {
        // Cerca se c'è un parametro success nell'URL (indica che veniamo da un salvataggio)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            // Rimuovi il parametro success e ricarica con il mese salvato
            sessionStorage.removeItem('lastSelectedMonth');
            window.location.href = window.location.pathname + '?month=' + savedMonth;
            return;
        }
    }
    // Elenco dei giorni festivi
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
        if (!date) return true;
        const selectedDate = new Date(date);
        const dayOfWeek = selectedDate.getDay();
        return dayOfWeek === 0 || dayOfWeek === 6 || publicHolidays.includes(date);
    }

    // Recupera il form e il campo data
    const lessonForm = document.getElementById('lesson-form');
    const lessonDateInput = document.getElementById('lesson-date');

    // Sostituisci la parte del submit del lesson-form con questa versione:
// Trova la sezione del submit del form e sostituiscila con questa:
if (lessonForm && lessonDateInput) {
    lessonForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const selectedDate = lessonDateInput.value;
        const selectedTutor = document.getElementById('selected-tutor-id').value;

        if (!selectedDate || !selectedTutor) {
            alert('Seleziona una data e un tutor.');
            return;
        }

        if (isInvalidDate(selectedDate)) {
            alert('Errore: Le lezioni non possono essere programmate nei fine settimana o nei giorni festivi.');
            return;
        }

        // SALVA IL MESE CORRENTE PRIMA DI PROCEDERE
        // Prova a catturare il mese dal calendario se esiste
        let currentMonth = null;
        
        // Metodo 1: Cerca nell'URL
        const urlParams = new URLSearchParams(window.location.search);
        currentMonth = urlParams.get('month') || urlParams.get('mese');
        
        // Metodo 2: Se non c'è nell'URL, prova a prenderlo dal calendario
        if (!currentMonth) {
            const monthSelect = document.querySelector('select[name="month"]');
            if (monthSelect) {
                currentMonth = monthSelect.value;
            }
        }
        
        // Metodo 3: Se ancora non c'è, usa la data della lezione
        if (!currentMonth) {
            currentMonth = selectedDate.substring(0, 7); // YYYY-MM
        }
        
        // Salva nel sessionStorage
        if (currentMonth) {
            sessionStorage.setItem('lastSelectedMonth', currentMonth);
        }

        const lessonMonth = selectedDate.slice(0, 7);
        fetch(`../scripts/check_pagamento_tutor.php?tutor_id=${selectedTutor}&mese=${lessonMonth}`)
            .then(response => response.json())
            .then(data => {
                if (data.pagato) {
                    alert('ATTENZIONE: Il tutor risulta già PAGATO per questo mese. Non puoi inserire altre lezioni.');
                    return;
                } else {
                    fetch(`../scripts/check_tutor_availability.php?tutor_id=${selectedTutor}&date=${selectedDate}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.hasLesson) {
                                const confirmMessage = `${data.tutorName} ha già una lezione registrata il ${data.date}. Sei sicuro di voler aggiungere un'altra lezione?`;
                                const confirmed = confirm(confirmMessage);

                                if (!confirmed) {
                                    return;
                                }
                            }
                            
                            // Prima di fare il submit, aggiungi un campo nascosto con il mese
                            const monthInput = document.createElement('input');
                            monthInput.type = 'hidden';
                            monthInput.name = 'return_month';
                            monthInput.value = currentMonth;
                            lessonForm.appendChild(monthInput);
                            
                            lessonForm.submit();
                        })
                        .catch(error => {
                            console.error('Errore durante il controllo della disponibilità del tutor:', error);
                            alert('Si è verificato un errore durante il controllo della disponibilità del tutor.');
                        });
                }
            })
            .catch(error => {
                console.error('Errore durante il controllo del pagamento:', error);
                alert('Si è verificato un errore durante il controllo del pagamento.');
            });
    });
}
    // Gestione selezione studenti per slot
    let currentSlot = null;

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

        // Aggiorna la lista degli studenti selezionati
        const studentContainer = currentSlot.querySelector('.selected-students');
        if (!studentContainer) {
            console.error("Errore: Contenitore studenti non trovato nello slot corrente.");
            return;
        }

        studentContainer.innerHTML = '';
        selectedStudents.forEach(student => {
            const studentItem = document.createElement('div');
            studentItem.className = 'student-item';
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

        // Chiudi il modale
        const modal = document.getElementById('student-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    });

    // Gestione apertura e chiusura modale per aggiungere lezioni
    document.getElementById('add-lesson-fixed-btn').addEventListener('click', () => {
        document.getElementById('add-lesson-modal').style.display = 'block';
    });

    document.getElementById('close-add-lesson').addEventListener('click', () => {
        document.getElementById('add-lesson-modal').style.display = 'none';
    });

    // Script per il modale "Aggiungi Tutor"
    const addTutorBtn = document.getElementById('add-tutor-btn');
    const addTutorModal = document.getElementById('add-tutor-modal');
    const closeAddTutor = document.getElementById('close-add-tutor');

    if (addTutorBtn) {
        addTutorBtn.addEventListener('click', () => {
            addTutorModal.style.display = 'block';
        });
    }

    if (closeAddTutor) {
        closeAddTutor.addEventListener('click', () => {
            addTutorModal.style.display = 'none';
        });
    }

    // Gestione Report Alunno
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

    if (reportBtn) {
        reportBtn.addEventListener('click', () => {
            reportModal.style.display = 'block';
        });
    }

    if (closeReportModal) {
        closeReportModal.addEventListener('click', () => {
            reportModal.style.display = 'none';
        });
    }

    if (closeResultModal) {
        closeResultModal.addEventListener('click', () => {
            resultModal.style.display = 'none';
        });
    }

    // Gestione tipo periodo per report alunno
    const periodoMeseAlunno = document.getElementById('periodo-mese-alunno');
    const periodoAnnoAlunno = document.getElementById('periodo-anno-alunno');
    const meseAlunnoContainer = document.getElementById('mese-alunno-container');

    if (periodoMeseAlunno) {
        periodoMeseAlunno.addEventListener('change', function() {
            meseAlunnoContainer.style.display = 'block';
        });
    }

    if (periodoAnnoAlunno) {
        periodoAnnoAlunno.addEventListener('change', function() {
            meseAlunnoContainer.style.display = 'none';
        });
    }

    if (generateReportBtn) {
        generateReportBtn.addEventListener('click', () => {
            const selectedAlunni = Array.from(document.querySelectorAll('.alunno-checkbox:checked')).map(cb => cb.value);
            const tipoPeriodo = document.querySelector('input[name="tipo-periodo-alunno"]:checked').value;
            let periodo;

            if (tipoPeriodo === 'mese') {
                periodo = meseInput.value;
                if (!periodo) {
                    alert('Seleziona un mese per generare il report.');
                    return;
                }
            } else {
                periodo = 'anno-' + new Date().getFullYear();
            }

            if (selectedAlunni.length === 0) {
                alert('Seleziona almeno un alunno per generare il report.');
                return;
            }

            fetch(`../scripts/generate_report_a.php?periodo=${periodo}&alunni=${selectedAlunni.join(',')}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Response was not JSON:', text);
                            throw new Error('La risposta dal server non è in formato JSON valido');
                        }
                    });
                })
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    const meseFormattato = data.mese;
                    const isAnnualReport = periodo.startsWith('anno-');
                    const periodoText = isAnnualReport ? `nell'${meseFormattato.toLowerCase()}` : `nel mese di ${meseFormattato}`;

                    reportContent.innerHTML = data.data.map(alunno => `
                        <div class="student-report">
                            <h3 class="student-name">
                                ${alunno.nome}
                                <button class="info-btn" data-id="${alunno.id}">
                                    <i class="fa-solid fa-circle-info"></i>
                                </button>
                            </h3>
                            <div class="total-hours">Totale ore: ${alunno.ore}</div>
                        </div>
                    `).join('');

                    downloadReport.href = `../scripts/generate_report_a.php?periodo=${periodo}&alunni=${selectedAlunni.join(',')}&export=1`;

                    reportModal.style.display = 'none';
                    resultModal.style.display = 'block';
                })
                .catch(error => {
                    alert('Errore durante la generazione del report.');
                    console.error(error);
                });
        });
    }

    // Event delegation per gestire i bottoni "Info"
    document.body.addEventListener('click', (event) => {
        const button = event.target.closest('.info-btn');
        if (button) {
            const alunnoId = button.getAttribute('data-id');
            const infoModal = document.getElementById('info-modal');
            const infoContent = document.getElementById('info-content');

            fetch(`../scripts/get_alunno_info.php?id=${alunnoId}`)
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

            infoModal.style.display = 'block';
        }
    });

    // Associa il listener al bottone di chiusura del modal info
    const closeInfoBtn = document.querySelector('#info-modal .close-btn');
    if (closeInfoBtn) {
        closeInfoBtn.addEventListener('click', closeInfoModal);
    }

    // Event listener per duplicate slot button
    document.body.addEventListener('click', (event) => {
        const btn = event.target.closest('.duplicate-slot-btn');
        if (!btn) return;

        const currentSlot = parseInt(btn.getAttribute('data-slot'));
        const nextSlot = currentSlot + 1;

        const currentSlotGroup = document.querySelector(`.slot-group[data-slot="${currentSlot}"]`);
        const nextSlotGroup = document.querySelector(`.slot-group[data-slot="${nextSlot}"]`);
        
        if (!nextSlotGroup) {
            return;
        }

        const currentStudentIdsInput = currentSlotGroup.querySelector('input.student-ids');
        const nextStudentIdsInput = nextSlotGroup.querySelector('input.student-ids');
        const currentHalfLessonCheckbox = currentSlotGroup.querySelector('input[type="checkbox"]');
        const nextHalfLessonCheckbox = nextSlotGroup.querySelector('input[type="checkbox"]');

        if (!currentStudentIdsInput || !nextStudentIdsInput) {
            return;
        }

        const currentStudentIds = currentStudentIdsInput.value;
        if (!currentStudentIds || currentStudentIds.trim() === '') {
            return;
        }

        nextStudentIdsInput.value = currentStudentIds;

        if (currentHalfLessonCheckbox && nextHalfLessonCheckbox) {
            nextHalfLessonCheckbox.checked = currentHalfLessonCheckbox.checked;
        }

        const currentSelectedStudentsDiv = currentSlotGroup.querySelector('.selected-students');
        const nextSelectedStudentsDiv = nextSlotGroup.querySelector('.selected-students');
        
                if (currentSelectedStudentsDiv && nextSelectedStudentsDiv) {
            nextSelectedStudentsDiv.innerHTML = currentSelectedStudentsDiv.innerHTML;
        }

        console.log(`Duplicated slot ${currentSlot} to slot ${nextSlot}`);
    });

    // Gestione form tutor veloce
    const quickTutorForm = document.getElementById('quick-tutor-form');
    if (quickTutorForm) {
        quickTutorForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const date = document.getElementById('quick-date').value;
            const tutorId = document.getElementById('quick-tutor-select').value;
            
            if (!tutorId) {
                alert('Seleziona un tutor');
                return;
            }
            
            closeQuickTutorModal();
            
            setTimeout(() => {
                openQuickStudentModal(date, tutorId, 1);
            }, 300);
        });
    }
    
    // Gestione form alunni veloce
    const quickStudentForm = document.getElementById('quick-student-form');
    if (quickStudentForm) {
        quickStudentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            const selectedStudents = formData.getAll('students[]');
            if (selectedStudents.length === 0) {
                alert('Seleziona almeno un alunno');
                return;
            }
            
            fetch('../scripts/aggiungi_lezione_veloce.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeQuickStudentModal();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore');
            });
        });
    }

    // Gestione form di modifica lezione
    const editForm = document.getElementById('edit-lesson-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../scripts/update_lesson.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeEditLessonModal();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore nell\'aggiornamento');
            });
        });
    }

    // Gestione Report Tutor
    const reportTutorBtn = document.getElementById('report-tutor-btn');
    if (reportTutorBtn) {
        reportTutorBtn.addEventListener('click', function() {
            document.getElementById('report-tutor-modal').style.display = 'block';
        });
    }

    const closeReportTutorModal = document.getElementById('close-report-tutor-modal');
    if (closeReportTutorModal) {
        closeReportTutorModal.addEventListener('click', function() {
            document.getElementById('report-tutor-modal').style.display = 'none';
        });
    }

    // Gestione tipo periodo
    const periodoMese = document.getElementById('periodo-mese');
    if (periodoMese) {
        periodoMese.addEventListener('change', function() {
            document.getElementById('mese-tutor-container').style.display = 'block';
        });
    }

    const periodoAnno = document.getElementById('periodo-anno');
    if (periodoAnno) {
        periodoAnno.addEventListener('change', function() {
            document.getElementById('mese-tutor-container').style.display = 'none';
        });
    }

    // Collega la funzione di filtro all'input di ricerca tutor per il report
    const tutorReportSearchInput = document.getElementById('tutor-report-search');
    if (tutorReportSearchInput) {
        tutorReportSearchInput.removeAttribute('onkeyup');
        tutorReportSearchInput.addEventListener('keyup', filterTutorReport);
        tutorReportSearchInput.addEventListener('input', filterTutorReport);
        tutorReportSearchInput.addEventListener('paste', function() {
            setTimeout(filterTutorReport, 100);
        });
    }

    // Genera report tutor
    const generateReportTutorBtn = document.getElementById('generate-report-tutor');
    if (generateReportTutorBtn) {
        generateReportTutorBtn.addEventListener('click', function() {
            const selectedTutors = [];
            document.querySelectorAll('.tutor-report-checkbox:checked').forEach(checkbox => {
                selectedTutors.push(checkbox.value);
            });
            
            if (selectedTutors.length === 0) {
                alert('Seleziona almeno un tutor!');
                return;
            }
            
            let periodo;
            const tipoPeriodo = document.querySelector('input[name="tipo-periodo"]:checked').value;
            
            if (tipoPeriodo === 'mese') {
                periodo = document.getElementById('mese-tutor-report').value;
                if (!periodo) {
                    alert('Seleziona un mese!');
                    return;
                }
            } else {
                periodo = 'anno-' + new Date().getFullYear();
            }
            
            // Crea form per POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../scripts/generate_report_tutor.php';
            form.target = '_blank';
            
            // Aggiungi tutor selezionati
            selectedTutors.forEach(tutorId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tutor_ids[]';
                input.value = tutorId;
                form.appendChild(input);
            });
            
            // Aggiungi periodo
            const periodoInput = document.createElement('input');
            periodoInput.type = 'hidden';
            periodoInput.name = 'periodo';
            periodoInput.value = periodo;
            form.appendChild(periodoInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            // Chiudi il modale
            document.getElementById('report-tutor-modal').style.display = 'none';
        });
    }
});

// Gestione chiusura modali cliccando fuori
window.onclick = function (event) {
    const studentModal = document.getElementById('student-modal');
    const lessonModal = document.getElementById('add-lesson-modal');
    const addTutorModal = document.getElementById('add-tutor-modal');
    const infoModal = document.getElementById('info-modal');
    const reportTutorModal = document.getElementById('report-tutor-modal');
    
    if (event.target === studentModal) studentModal.style.display = 'none';
    if (event.target === lessonModal) lessonModal.style.display = 'none';
    if (event.target === addTutorModal) addTutorModal.style.display = 'none';
    if (event.target === infoModal) infoModal.style.display = 'none';
    if (event.target === reportTutorModal) reportTutorModal.style.display = 'none';
};

// Funzione per filtrare i tutor nella selezione "Aggiungi Lezione"
function filterTutors() {
    const searchText = document.getElementById('tutor-search').value.toLowerCase();
    const tutorList = document.getElementById('tutor-list');
    const tutorItems = tutorList.getElementsByClassName('tutor-item');

    for (let item of tutorItems) {
        const tutorName = item.getElementsByTagName('span')[0].textContent.toLowerCase();
        if (tutorName.includes(searchText)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    }
}

// Funzione per selezionare un tutor nella selezione "Aggiungi Lezione"
function selectTutor(tutorId, tutorName) {
    document.getElementById('selected-tutor-id').value = tutorId;
    document.getElementById('selected-tutor-name').textContent = tutorName;
    document.getElementById('selected-tutor').style.display = 'block';
    document.getElementById('tutor-list').style.display = 'none';
    document.getElementById('tutor-search').value = '';
}

// Funzione per cancellare la selezione del tutor nella selezione "Aggiungi Lezione"
function clearTutorSelection() {
    document.getElementById('selected-tutor-id').value = '';
    document.getElementById('selected-tutor').style.display = 'none';
    document.getElementById('tutor-list').style.display = 'block';
    document.getElementById('tutor-search').value = '';
    filterTutors();
}

// Gestione ricerca e selezione tutor
document.addEventListener('DOMContentLoaded', () => {
    const tutorSearch = document.getElementById('tutor-search');
    const tutorList = document.getElementById('tutor-list');
    
    if (tutorSearch && tutorList) {
        // Nascondi la lista all'inizio
        tutorList.style.display = 'none';
        
        // Mostra la lista solo quando c'è del testo nella ricerca
        tutorSearch.addEventListener('input', function() {
            const hasSearchText = this.value.trim().length > 0;
            tutorList.style.display = hasSearchText ? 'block' : 'none';
            if (hasSearchText) {
                filterTutors();
            }
        });

        // Gestisci anche l'evento keyup per catturare cancellazioni
        tutorSearch.addEventListener('keyup', function() {
            const hasSearchText = this.value.trim().length > 0;
            tutorList.style.display = hasSearchText ? 'block' : 'none';
            if (hasSearchText) {
                filterTutors();
            }
        });
    }
});

// Fine del file
