<?php


$tipo = 0; // 0 = local | 1 = produção

$charset = "utf8mb4";

if ($tipo == 1) {
    // PRODUÇÃO
    $host = "localhost";
    $db   = "erwise39_sistemagestao";
    $user = "erwise39_rooter";
    $pass = "xM>%0&wJshg+1X]4}+*2|9)Q.|:W?SjnfgS<M%G6Nms&!mk3ZSXZ]Gp*GnD>h";
} else {
    // LOCAL
    $host = "localhost";
    $db   = "vidracaria";
    $user = "root";
    $pass = "";
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
