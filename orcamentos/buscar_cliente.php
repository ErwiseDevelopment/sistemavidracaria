<?php
require_once "../config/database.php";

$whatsapp = $_GET['whatsapp'] ?? '';
if(!$whatsapp) exit(json_encode(['erro'=>'Número não informado']));

$sql = $pdo->prepare("SELECT * FROM clientes WHERE clientewhatsapp = ? AND clientesituacao=1 LIMIT 1");
$sql->execute([$whatsapp]);
$cliente = $sql->fetch(PDO::FETCH_ASSOC);

if($cliente){
    echo json_encode($cliente);
}else{
    echo json_encode(['erro'=>'Cliente não encontrado']);
}
