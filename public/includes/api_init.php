<?php

// Carrega o autoload do Composer e o .env
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load(); 

// Inclui o arquivo de funções
require_once __DIR__ . '/functions.php';

// Conexão com o Banco de Dados
try {
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']);
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}