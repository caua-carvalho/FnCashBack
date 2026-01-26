<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP OK<br>";

$root = dirname(__DIR__);
echo "ROOT = $root<br>";

if (!file_exists($root . '/Router.php')) {
    echo "Router.php NÃO EXISTE<br>";
    exit;
}

require_once $root . '/Router.php';

echo "Router carregado";
