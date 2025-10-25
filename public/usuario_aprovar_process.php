<?php
require __DIR__ . '/includes/init.php';

// Segurança: Apenas Super Admins podem aprovar/rejeitar
if ($currentUser['permissao'] != 1) {
    header('Location: index.php?error=access_denied');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios_aprovacao.php');
    exit;
}

$userId = $_POST['user_id'] ?? null;
$action = $_POST['action'] ?? '';

if (empty($userId) || empty($action)) {
    header('Location: usuarios_aprovacao.php?status=error&message=Ação inválida.');
    exit;
}

try {
    if ($action === 'aprovar') {
        // Altera o status do utilizador para 1 (Ativo)
        $stmt = $pdo->prepare("UPDATE tbl_usuarios SET status = 1 WHERE id_user = ?");
        $stmt->execute([(int)$userId]);
        // Futuramente, podemos adicionar aqui o envio de um e-mail/WhatsApp a notificar a aprovação.
        header('Location: usuarios_aprovacao.php?status=success&message=Utilizador aprovado com sucesso.');

    } elseif ($action === 'rejeitar') {
        // Apaga permanentemente o registo do utilizador
        $stmt = $pdo->prepare("DELETE FROM tbl_usuarios WHERE id_user = ?");
        $stmt->execute([(int)$userId]);
        header('Location: usuarios_aprovacao.php?status=success&message=Registo do utilizador rejeitado e excluído.');
    } else {
        header('Location: usuarios_aprovacao.php?status=error&message=Ação desconhecida.');
    }
    exit;

} catch (Exception $e) {
    error_log("Erro no processo de aprovação: " . $e->getMessage());
    header('Location: usuarios_aprovacao.php?status=error&message=Ocorreu um erro no sistema.');
    exit;
}