<?php

$host = "localhost";
$db   = "erwise39_sistemagestao";
$user = "erwise39_rooter";
$pass = "xM>%0&wJshg+1X]4}+*2|9)Q.|:W?SjnfgS<M%G6Nms&!mk3ZSXZ]Gp*GnD>h";
$charset = "utf8mb4";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
