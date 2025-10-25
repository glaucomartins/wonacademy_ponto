<?php
require __DIR__ . '/includes/init.php';

// Segurança: Apenas Super Admins e Admins podem criar usuários
if ($currentUser['permissao'] > 2) {
    header('Location: index?error=access_denied');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios');
    exit;
}

// Coleta e validação dos dados (sem alterações)
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$whatsapp = $_POST['whatsapp'] ?? '';
$senha = $_POST['senha'] ?? '';
$permissao = $_POST['permissao'] ?? 3;
$status = $_POST['status'] ?? 0;
$notificacoes = $_POST['notificacoes'] ?? 0;
$cpf = $_POST['cpf'] ?? '';

if (empty($nome) || empty($email) || empty($whatsapp) || empty($senha)) {
    header('Location: usuarios?create_status=error&message=Todos os campos são obrigatórios.');
    exit;
}
if (strlen($senha) < 6) {
    header('Location: usuarios?create_status=error&message=A senha deve ter pelo menos 6 caracteres.');
    exit;
}

try {
    $whatsapp_limpo = preg_replace('/\D/', '', $whatsapp);
    $cpf = preg_replace('/\D/', '', $cpf);

    $stmt_check = $pdo->prepare("SELECT id_user FROM tbl_usuarios WHERE email = ? OR whatsapp = ?");
    $stmt_check->execute([$email, $whatsapp_limpo]);
    if ($stmt_check->fetch()) {
        header('Location: usuarios?create_status=error&message=Este e-mail ou WhatsApp já está em uso.');
        exit;
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = "INSERT INTO tbl_usuarios (nome, email, whatsapp, senha, permissao, status, notificacoes, id_administrador, cpf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql);
    $stmt_insert->execute([$nome, $email, $whatsapp_limpo, $senhaHash, $permissao, $status, $notificacoes, $currentUser['id_user'], $cpf]);

    // Cria o plano padrão para o novo usuário que acabamos de inserir
    $newUserId = $pdo->lastInsertId(); // Pega o ID do usuário recém-criado
    $planSql = "INSERT INTO tbl_instancia_plan (id_usuario, limite_instancias) VALUES (?, ?)";
    $planStmt = $pdo->prepare($planSql);
    $planStmt->execute([$newUserId, 1]); // Define o limite padrão como 1
    
    // Envio da mensagem de boas-vindas (sem alterações)
    $appUrl = rtrim($_ENV['APP_URL'], '/');
    $loginUrl = $appUrl . '/login';
    sendWonNewUserCredentials($pdo, $currentUser['id_user'], $nome, $whatsapp_limpo, $senha, $loginUrl);

    header('Location: usuarios?create_status=success&message=Usuário criado e notificado com sucesso.');
    exit;

} catch (Exception $e) {
    error_log("Erro ao criar usuário: " . $e->getMessage());
    header('Location: usuarios?create_status=error&message=Ocorreu um erro ao criar o usuário.');
    exit;
}