<?php
require_once "../config/database.php";

$id = $_GET['id'] ?? 0;

if ($id) {
    $sql = $pdo->prepare("DELETE FROM usuarios WHERE usuariocodigo=?");
    $sql->execute([$id]);
}

header("Location: listar.php");
exit;
