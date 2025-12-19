<?php
session_start();

$tipo = 0;

if ($tipo == 1) {
    define("BASE_URL", "https://visavidros.erwisedev-hml.com.br");
} else {
    define("BASE_URL", "http://sistema.local:8180");
}
