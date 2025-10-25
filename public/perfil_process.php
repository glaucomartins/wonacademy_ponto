<?php
require __DIR__ . '/includes/init.php'; // Carrega o usuário atual em $currentUser

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

// Coleta de dados da senha
$userId = $currentUser['id_user'];
$senha_atual = $_POST['senha_atual'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';
$confirma_senha = $_POST['confirma_senha'] ?? '';

// Se nenhum campo de senha foi preenchido, não faz nada e retorna com sucesso.
if (empty($senha_atual) && empty($nova_senha) && empty($confirma_senha)) {
    header('Location: perfil.php?status=success&message=Nenhuma alteração de senha foi solicitada.');
    exit;
}

// Lógica para alteração de senha
try {
    // Validações de senha
    if (empty($senha_atual)) {
        header('Location: perfil.php?status=error&message=Por favor, informe sua senha atual para definir uma nova.');
        exit;
    }
    if ($nova_senha !== $confirma_senha) {
        header('Location: perfil.php?status=error&message=A nova senha e a confirmação não correspondem.');
        exit;
    }
    if (strlen($nova_senha) < 6) {
        header('Location: perfil.php?status=error&message=A nova senha deve ter pelo menos 6 caracteres.');
        exit;
    }

    // Verifica se a senha atual está correta
    if (!password_verify($senha_atual, $currentUser['senha'])) {
        header('Location: perfil.php?status=error&message=A senha atual está incorreta.');
        exit;
    }

    // Prepara e executa a atualização da senha
    $senhaHash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $sql = "UPDATE tbl_usuarios SET senha = ? WHERE id_user = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$senhaHash, $userId]);

    header('Location: perfil.php?status=success&message=Senha alterada com sucesso!');
    exit;

} catch (Exception $e) {
    error_log("Erro ao atualizar senha: " . $e->getMessage());
    header('Location: perfil.php?status=error&message=Ocorreu um erro interno ao atualizar sua senha.');
    exit;
}

?>