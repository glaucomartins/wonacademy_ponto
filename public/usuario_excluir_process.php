<?php
require __DIR__ . '/includes/init.php';

// Segurança: Apenas Super Admins podem excluir usuários
if ($currentUser['permissao'] >= 3) {
    header('Location: index?error=access_denied');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios');
    exit;
}

$userId = $_POST['userId'] ?? null;

if (empty($userId)) {
    header('Location: usuarios?delete_status=error&message=ID do usuário não fornecido.');
    exit;
}

// Chama a função de exclusão que já existe em functions.php
$success = excluirUsuario($pdo, (int)$userId);

if ($success) {
    header('Location: usuarios?delete_status=success&message=Usuário excluído com sucesso.');
} else {
    // A função retorna false se, por exemplo, o admin tentar se auto-excluir.
    header('Location: usuarios?delete_status=error&message=Não foi possível excluir o usuário. Você não pode excluir a si mesmo.');
}
exit;