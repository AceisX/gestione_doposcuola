<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Doposcuola</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Gestione Doposcuola</h1>

    <!-- Bottone per aprire il modal -->
    <button id="openModal">Aggiungi Alunno</button>

    <!-- Tabella per visualizzare i dati -->
    <div id="table-container">
        <table id="alunni-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Scuola</th>
                    <th>Piano</th>
                    <th>Quota</th>
                    <th>Ore</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <!-- Dati caricati dinamicamente -->
            </tbody>
        </table>
    </div>

    <!-- Inclusione dei modal -->
    <?php include 'modals.html'; ?>

    <script src="js/script.js"></script>
</body>
</html>