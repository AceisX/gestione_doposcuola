document.addEventListener("DOMContentLoaded", function () {
    // Caricamento dati degli alunni
    fetch("php/alunni.php")
        .then((response) => response.json())
        .then((data) => {
            const tbody = document.querySelector("#alunni-table tbody");
            data.forEach((alunno) => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${alunno.nome}</td>
                    <td>${alunno.scuola}</td>
                    <td>${alunno.piano}</td>
                    <td>${alunno.quota}</td>
                    <td>${alunno.ore}</td>
                    <td>${alunno.stato}</td>
                    <td><button>Modifica</button></td>
                `;
                tbody.appendChild(row);
            });
        });
});

// Selezione degli elementi del modal
const modal = document.getElementById('alunnoModal');
const openModalBtn = document.getElementById('openModal');
const closeModalBtn = document.querySelector('.close');

// Apri il modal
openModalBtn.addEventListener('click', () => {
    modal.style.display = 'block';
});

// Chiudi il modal
closeModalBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

// Chiudi il modal cliccando fuori dal contenuto
window.addEventListener('click', (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});