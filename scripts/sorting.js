document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('.alunni-table');

    if (!table) {
        console.error("Errore: Tabella con classe 'alunni-table' non trovata!");
        return;
    }

    const headers = table.querySelectorAll('th');
    const tableBody = table.querySelector('tbody');
    const rows = Array.from(tableBody.querySelectorAll('tr'));

    headers.forEach((header, index) => {
        header.addEventListener('click', () => {
            const isAscending = header.classList.contains('asc');
            const type = header.dataset.type; // Tipo di dato (es. 'string', 'number', 'date')

            headers.forEach(h => h.classList.remove('asc', 'desc'));
            header.classList.toggle('asc', !isAscending);
            header.classList.toggle('desc', isAscending);

            const sortedRows = rows.sort((a, b) => {
                const aText = a.children[index];
                const bText = b.children[index];

                return compareValues(aText, bText, type, isAscending);
            });

            tableBody.innerHTML = '';
            tableBody.append(...sortedRows);
        });
    });

    function compareValues(a, b, type, isAscending) {
        if (type === 'number') {
            a = parseFloat(a.textContent.replace('€', '').trim()) || 0;
            b = parseFloat(b.textContent.replace('€', '').trim()) || 0;
        } else if (type === 'date') {
            a = new Date(a.textContent);
            b = new Date(b.textContent);
        } else if (type === 'status') {
            // Controlla la classe dello stato
            const aClass = a.querySelector('.status')?.classList.contains('active') ? 'attivo' : 'inattivo';
            const bClass = b.querySelector('.status')?.classList.contains('active') ? 'attivo' : 'inattivo';
            a = aClass.toLowerCase();
            b = bClass.toLowerCase();
        } else {
            a = a.textContent.toLowerCase();
            b = b.textContent.toLowerCase();
        }

        if (a > b) return isAscending ? 1 : -1;
        if (a < b) return isAscending ? -1 : 1;
        return 0;
    }
});