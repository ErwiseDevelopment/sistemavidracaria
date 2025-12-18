<?php
require_once __DIR__ . "/../config/config.php";


if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
