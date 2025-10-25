<?php
require __DIR__ . '/includes/init.php';

if ($currentUser['permissao'] >= 3) {
    header('Location: index?error=access_denied');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios');
    exit;
}

// Coleta dos dados do formulário
$userId = $_POST['userId'] ?? null;
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$whatsapp = $_POST['whatsapp'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$permissao = $_POST['permissao'] ?? 3;
$status = $_POST['status'] ?? 0;
$notificacoes = $_POST['notificacoes'] ?? 0;
$novaSenha = $_POST['nova_senha'] ?? '';

if (empty($userId) || empty($nome) || empty($email) || empty($whatsapp)) {
    header('Location: usuarios?edit_status=error&message=Campos obrigatórios não preenchidos.');
    exit;
}

try {
    $whatsapp_limpo = preg_replace('/\D/', '', $whatsapp);
    $cpf = preg_replace('/\D/', '', $cpf);
    
    // Monta a query base
    $sql = "UPDATE tbl_usuarios SET nome = ?, email = ?, whatsapp = ?, cpf = ?, permissao = ?, status = ?, notificacoes = ?";
    $params = [$nome, $email, $whatsapp_limpo, $cpf, $permissao, $status, $notificacoes];

    if (!empty($novaSenha)) {
        if (strlen($novaSenha) < 6) {
            header('Location: usuarios?edit_status=error&message=A nova senha deve ter pelo menos 6 caracteres.');
            exit;
        }
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $sql .= ", senha = ?";
        $params[] = $senhaHash;
    }

    $sql .= " WHERE id_user = ?";
    $params[] = $userId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Location: usuarios?edit_status=success&message=Usuário atualizado com sucesso.');
    exit;

} catch (Exception $e) {
    error_log("Erro ao editar usuário: " . $e);
    header('Location: usuarios?edit_status=error&message=Ocorreu um erro ao atualizar o usuário.');
    exit;
}