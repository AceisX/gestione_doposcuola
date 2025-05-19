<?php
require_once '../config.php';

if (isset($_GET['tutor_id'])) {
    $tutorId = intval($_GET['tutor_id']);
    $sql = "
        SELECT mensilita, paga, data_pagamento, ore_singole, ore_gruppo
        FROM pagamenti_tutor
        WHERE tutor_id = ?
        ORDER BY mensilita DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $tutorId);
    $stmt->execute();
    $result = $stmt->get_result();

    $html = '<table class="dettagli-pagamenti-table">
                <thead>
                    <tr>
                        <th>Mensilità</th>
                        <th>Paga</th>
                        <th>Data Pagamento</th>
                        <th>Ore Singole</th>
                        <th>Ore di Gruppo</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>';
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
                    <td>' . date('M Y', strtotime($row['mensilita'])) . '</td>
                    <td class="' . ($row['data_pagamento'] ? 'pagato' : 'non-pagato') . '">' . '€' . number_format($row['paga'], 2) . '</td>
                    <td>' . ($row['data_pagamento'] ? $row['data_pagamento'] : '-') . '</td>
                    <td>' . $row['ore_singole'] . '</td>
                    <td>' . $row['ore_gruppo'] . '</td>
                    <td>
                        ' . ($row['data_pagamento'] ? '' : '<button class="paga-btn" data-id="' . $row['mensilita'] . '">Paga</button>') . '
                        <button class="elimina-btn" data-id="' . $row['mensilita'] . '">Elimina</button>
                    </td>
                  </tr>';
    }
    $html .= '</tbody></table>';

    echo json_encode(['success' => true, 'html' => $html]);
} else {
    echo json_encode(['success' => false, 'message' => 'ID Tutor non valido.']);
}
?>