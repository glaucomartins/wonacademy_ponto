<?php
// Define que este script é para chamadas AJAX e previne redirecionamentos de login
define('IS_AJAX', true);
require __DIR__ . '/includes/init.php';

// Define o cabeçalho como JSON
header('Content-Type: application/json');
 
// Segurança: Apenas Super Admins podem buscar dados de usuários
if ($currentUser['permissao'] >= 3) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$userId = $_GET['id'] ?? null;
if (!$userId) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID do usuário não fornecido.']);
    exit;
}

// Usa a função existente para buscar os dados do usuário
$usuario = getUsuarioPorId($pdo, (int)$userId);

if (!$usuario) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Usuário não encontrado.']);
    exit;
}

// Retorna os dados do usuário em formato JSON
echo json_encode($usuario);
exit;