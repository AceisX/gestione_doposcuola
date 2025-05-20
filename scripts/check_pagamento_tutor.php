<?php
require_once '../config.php';
$tutor_id = intval($_GET['tutor_id']);
$mese = $_GET['mese']; // formato YYYY-MM

$query = "SELECT stato FROM pagamenti_tutor WHERE tutor_id = ? AND mensilita = ?";
$stmt = $conn->prepare($query);
$mensilita = $mese . '-01';
$stmt->bind_param('is', $tutor_id, $mensilita);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
echo json_encode(['pagato' => ($row['stato'] ?? 0) == 1]);
?>