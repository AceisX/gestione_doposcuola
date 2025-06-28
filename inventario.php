<?php
require_once 'config.php';
session_start();

// Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: pages/login.php');
    exit;
}

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add_product':
            $codice = $_POST['codice'];
            $nome = $_POST['nome'];
            $categoria = $_POST['categoria'];
            $quantita = (int)$_POST['quantita'];
            $ubicazione = $_POST['ubicazione'];
            $prezzo = $_POST['prezzo'] ? (float)$_POST['prezzo'] : null;
            $note = $_POST['note'];
            $etichette = $_POST['etichette'];
            
            $query = "INSERT INTO inventario (codice, nome, categoria, quantita, ubicazione, prezzo, note, etichette) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssissss", $codice, $nome, $categoria, $quantita, $ubicazione, $prezzo, $note, $etichette);
            $stmt->execute();
            break;
            
        case 'add_category':
            $nome_categoria = $_POST['nome_categoria'];
            $query = "INSERT INTO categorie_inventario (nome) VALUES (?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $nome_categoria);
            $stmt->execute();
            break;
    }
}

// Recupera categorie
$categorie = [];
$query = "SELECT * FROM categorie_inventario ORDER BY nome";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $categorie[] = $row;
}

// Recupera prodotti con filtri
$search = $_GET['search'] ?? '';
$categoria_filter = $_GET['categoria'] ?? '';
$etichetta_filter = $_GET['etichetta'] ?? '';

$query = "SELECT * FROM inventario WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (nome LIKE ? OR codice LIKE ? OR note LIKE ?)";
    $search_param = "%$search%";
    $params[] = &$search_param;
    $params[] = &$search_param;
    $params[] = &$search_param;
    $types .= "sss";
}

if ($categoria_filter) {
    $query .= " AND categoria = ?";
    $params[] = &$categoria_filter;
    $types .= "s";
}

if ($etichetta_filter) {
    $query .= " AND etichette LIKE ?";
    $etichetta_param = "%$etichetta_filter%";
    $params[] = &$etichetta_param;
    $types .= "s";
}

$query .= " ORDER BY nome";

if ($types) {
    $stmt = $conn->prepare($query);
    array_unshift($params, $types);
    call_user_func_array([$stmt, 'bind_param'], $params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$prodotti = [];
while ($row = $result->fetch_assoc()) {
    $prodotti[] = $row;
}

// Recupera tutti gli alunni per i movimenti
$alunni = [];
$query = "SELECT id, CONCAT(nome, ' ', cognome) as nome_completo FROM alunni WHERE stato = 'attivo' ORDER BY nome, cognome";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $alunni[] = $row;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Gestione Doposcuola</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link href="assets/fontawesome/css/all.min.css" rel="stylesheet">
    <style>
        /* Stili specifici per l'inventario */
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .inventory-filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .inventory-table {
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        
        .inventory-table th {
            background: linear-gradient(135deg, rgba(102,126,234,0.08), rgba(118,75,162,0.08));
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9em;
            color: #2d3748;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .inventory-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .product-code {
            font-family: monospace;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .category-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-block;
        }
        
        .category-badge.libri {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .category-badge.cancelleria {
            background: #fef3c7;
            color: #92400e;
        }
        
        .category-badge.multimediale {
            background: #e9d5ff;
            color: #6b21a8;
        }
        
        .quantity-display {
            font-size: 1.1em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-low {
            color: #ef4444;
        }
        
        .quantity-medium {
            color: #f59e0b;
        }
        
        .quantity-good {
            color: #22c55e;
        }
        
        .location-info {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #64748b;
            font-size: 0.9em;
        }
        
        .tag {
            background: #e5e7eb;
            color: #374151;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-right: 4px;
            display: inline-block;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-movement {
            background: #3b82f6;
            color: white;
        }
        
        .btn-movement:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-edit {
            background: #f59e0b;
            color: white;
        }
        
        .btn-edit:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .add-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border: 2px solid #667eea;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Modal specifici per inventario */
        .modal-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .modal-form-grid .full-width {
            grid-column: 1 / -1;
        }
        
        .movement-type-selector {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .movement-type-selector label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .movement-type-selector input[type="radio"]:checked + label {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .inventory-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .modal-form-grid {
                grid-template-columns: 1fr;
            }
            
            .inventory-table {
                font-size: 0.9em;
            }
            
            .inventory-table th, .inventory-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/header.php'; ?>
    
    <main class="container">
        <div class="inventory-header">
            <h2><i class="fas fa-boxes"></i> Gestione Inventario</h2>
            
            <div class="add-buttons">
                <button class="btn-primary" onclick="openAddProductModal()">
                    <i class="fas fa-plus"></i> Aggiungi Prodotto
                </button>
                <button class="btn-secondary" onclick="openAddCategoryModal()">
                    <i class="fas fa-tag"></i> Nuova Categoria
                </button>
            </div>
        </div>
        
        <div class="table-controls">
            <div class="search-bar">
                <input type="text" id="searchInventory" placeholder="Cerca prodotto, codice o note..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <i class="fa-solid fa-search"></i>
            </div>
            
            <div class="inventory-filters">
                <select id="filterCategoria" onchange="filterInventory()">
                    <option value="">Tutte le categorie</option>
                    <?php foreach($categorie as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['nome']); ?>" 
                                <?php echo $categoria_filter == $cat['nome'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" id="filterEtichetta" placeholder="Filtra per etichetta..." 
                       value="<?php echo htmlspecialchars($etichetta_filter); ?>" 
                       onkeyup="filterInventory()">
            </div>
        </div>
        
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Codice</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Quantità</th>
                    <th>Ubicazione</th>
                    <th>Prezzo</th>
                    <th>Etichette</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($prodotti as $prodotto): ?>
                <tr>
                    <td><span class="product-code"><?php echo htmlspecialchars($prodotto['codice']); ?></span></td>
                    <td>                        <strong><?php echo htmlspecialchars($prodotto['nome']); ?></strong>
                        <?php if($prodotto['note']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($prodotto['note']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="category-badge <?php echo htmlspecialchars($prodotto['categoria']); ?>">
                            <?php echo htmlspecialchars($prodotto['categoria']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="quantity-display <?php 
                            echo $prodotto['quantita'] <= 5 ? 'quantity-low' : 
                                ($prodotto['quantita'] <= 20 ? 'quantity-medium' : 'quantity-good'); 
                        ?>">
                            <?php echo $prodotto['quantita']; ?>
                            <?php if($prodotto['quantita'] <= 5): ?>
                                <i class="fas fa-exclamation-triangle"></i>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <div class="location-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($prodotto['ubicazione'] ?: 'Non specificata'); ?>
                        </div>
                    </td>
                    <td>
                        <?php echo $prodotto['prezzo'] ? '€' . number_format($prodotto['prezzo'], 2) : '-'; ?>
                    </td>
                    <td>
                        <?php 
                        if($prodotto['etichette']):
                            $etichette = explode(',', $prodotto['etichette']);
                            foreach($etichette as $etichetta):
                                if(trim($etichetta)):
                        ?>
                            <span class="tag"><?php echo htmlspecialchars(trim($etichetta)); ?></span>
                        <?php 
                                endif;
                            endforeach;
                        endif; 
                        ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-small btn-movement" onclick="openMovementModal(<?php echo $prodotto['id']; ?>, '<?php echo htmlspecialchars($prodotto['nome']); ?>')">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <button class="btn-small btn-edit" onclick="editProduct(<?php echo $prodotto['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-small btn-delete" onclick="deleteProduct(<?php echo $prodotto['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    
    <!-- Modale Aggiungi Prodotto -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addProductModal')">&times;</span>
            <h2>Aggiungi Nuovo Prodotto</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_product">
                
                <div class="modal-form-grid">
                    <div class="form-group">
                        <label for="codice">Codice Prodotto*</label>
                        <input type="text" name="codice" id="codice" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nome">Nome Prodotto*</label>
                        <input type="text" name="nome" id="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria">Categoria*</label>
                        <select name="categoria" id="categoria" required>
                            <option value="">Seleziona categoria...</option>
                            <?php foreach($categorie as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['nome']); ?>">
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantita">Quantità Iniziale*</label>
                        <input type="number" name="quantita" id="quantita" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ubicazione">Ubicazione</label>
                        <input type="text" name="ubicazione" id="ubicazione" placeholder="es. Scaffale A, Ripiano 2">
                    </div>
                    
                    <div class="form-group">
                        <label for="prezzo">Prezzo (€)</label>
                        <input type="number" name="prezzo" id="prezzo" step="0.01" min="0">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="etichette">Etichette (separate da virgola)</label>
                        <input type="text" name="etichette" id="etichette" placeholder="es. nuovo, importante, fragile">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="note">Note</label>
                        <textarea name="note" id="note" rows="3" placeholder="Note aggiuntive sul prodotto..."></textarea>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Salva Prodotto
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modale Aggiungi Categoria -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close-btn" onclick="closeModal('addCategoryModal')">&times;</span>
            <h2>Aggiungi Nuova Categoria</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_category">
                
                <div class="form-group">
                    <label for="nome_categoria">Nome Categoria*</label>
                    <input type="text" name="nome_categoria" id="nome_categoria" required>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus"></i> Aggiungi Categoria
                    </button>
                </div>
            </form>
            
            <div style="margin-top: 30px;">
                <h4>Categorie esistenti:</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                    <?php foreach($categorie as $cat): ?>
                        <span class="category-badge" style="background: #e5e7eb; color: #374151;">
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modale Movimento Prodotto -->
    <div id="movementModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('movementModal')">&times;</span>
            <h2>Registra Movimento - <span id="movementProductName"></span></h2>
            
            <form id="movementForm">
                <input type="hidden" id="movement_product_id" name="product_id">
                
                <div class="movement-type-selector">
                    <div>
                        <input type="radio" name="tipo_movimento" id="entrata" value="entrata" checked>
                        <label for="entrata">
                            <i class="fas fa-arrow-down" style="color: #22c55e;"></i> Entrata
                        </label>
                    </div>
                    <div>
                        <input type="radio" name="tipo_movimento" id="uscita" value="uscita">
                        <label for="uscita">
                            <i class="fas fa-arrow-up" style="color: #ef4444;"></i> Uscita
                        </label>
                    </div>
                </div>
                
                <div class="modal-form-grid">
                    <div class="form-group">
                        <label for="movement_quantita">Quantità*</label>
                        <input type="number" name="quantita" id="movement_quantita" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="movement_alunno">Alunno (se applicabile)</label>
                        <select name="id_alunno" id="movement_alunno">
                            <option value="">Nessuno</option>
                            <?php foreach($alunni as $alunno): ?>
                                <option value="<?php echo $alunno['id']; ?>">
                                    <?php echo htmlspecialchars($alunno['nome_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="data_rientro_group" style="display: none;">
                        <label for="data_rientro">Data Rientro Prevista</label>
                        <input type="date" name="data_rientro" id="data_rientro">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="movement_note">Note</label>
                        <textarea name="note" id="movement_note" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-check"></i> Registra Movimento
                    </button>
                </div>
            </form>
            
            <!-- Storico movimenti recenti -->
            <div style="margin-top: 30px;">
                <h4>Movimenti Recenti</h4>
                <div id="recentMovements">
                    <!-- Popolato via JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Funzione per filtrare l'inventario
        function filterInventory() {
            const search = document.getElementById('searchInventory').value;
            const categoria = document.getElementById('filterCategoria').value;
            const etichetta = document.getElementById('filterEtichetta').value;
            
            let url = 'inventario.php?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (categoria) url += 'categoria=' + encodeURIComponent(categoria) + '&';
            if (etichetta) url += 'etichetta=' + encodeURIComponent(etichetta);
            
            window.location.href = url;
        }
        
        // Ricerca con debounce
        let searchTimeout;
        document.getElementById('searchInventory').addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterInventory, 500);
        });
        
        // Apertura modali
        function openAddProductModal() {
            document.getElementById('addProductModal').style.display = 'block';
        }
        
        function openAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'block';
        }
        
        function openMovementModal(productId, productName) {
            document.getElementById('movement_product_id').value = productId;
            document.getElementById('movementProductName').textContent = productName;
            document.getElementById('movementModal').style.display = 'block';
            
            // Carica movimenti recenti
            loadRecentMovements(productId);
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Gestione tipo movimento
        document.querySelectorAll('input[name="tipo_movimento"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const rientroGroup = document.getElementById('data_rientro_group');
                if (this.value === 'uscita') {
                    rientroGroup.style.display = 'block';
                } else {
                    rientroGroup.style.display = 'none';
                }
            });
        });
        
        // Gestione form movimento
        document.getElementById('movementForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('scripts/inventory_movement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Movimento registrato con successo!');
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                }
            });
        });
        
        // Carica movimenti recenti
        function loadRecentMovements(productId) {
            fetch('scripts/get_product_movements.php?product_id=' + productId)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('recentMovements');
                    if (data.movements.length > 0) {
                        let html = '<table style="width: 100%; font-size: 0.9em;">';
                        html += '<tr><th>Data</th><th>Tipo</th><th>Quantità</th><th>Alunno</th><th>Note</th></tr>';
                        
                        data.movements.forEach(mov => {
                            html += `<tr>
                                <td>${mov.data}</td>
                                <td><span class="${mov.tipo === 'entrata' ? 'positive' : 'negative'}">${mov.tipo}</span></td>
                                <td>${mov.quantita}</td>
                                <td>${mov.alunno || '-'}</td>
                                <td>${mov.note || '-'}</td>
                            </tr>`;
                        });
                        
                        html += '</table>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<p>Nessun movimento registrato</p>';
                    }
                });
        }
        
        // Modifica prodotto
        function editProduct(id) {
            // Implementare la logica di modifica
            alert('Funzione di modifica in             sviluppo');
        }
        
        // Elimina prodotto
        function deleteProduct(id) {
            if (confirm('Sei sicuro di voler eliminare questo prodotto?')) {
                fetch('scripts/delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Prodotto eliminato con successo!');
                        location.reload();
                    } else {
                        alert('Errore: ' + data.message);
                    }
                });
            }
        }
        
        // Chiudi modali cliccando fuori
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>