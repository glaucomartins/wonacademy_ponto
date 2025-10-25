<?php
// Inclui o arquivo de inicialização para ter acesso ao $pdo e functions.php
require_once __DIR__ . '/includes/api_init.php';

// Redireciona se a requisição não for POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_novo');
    exit;
}

// 1. OBTÉM E VALIDA OS DADOS DO FORMULÁRIO PÚBLICO
$nome = trim($_POST['nome'] ?? '');
$cpf = trim($_POST['cpf'] ?? ''); // ADICIONADO: Recebe o CPF/CNPJ
$email = trim($_POST['email'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirma_senha = $_POST['confirma_senha'] ?? '';

// Limpa os campos para salvar apenas números
$cpf_limpo = preg_replace('/\D/', '', $cpf); // ADICIONADO: Limpa o CPF/CNPJ
$whatsapp_limpo = preg_replace('/\D/', '', $whatsapp);

// 2. VALIDAÇÃO DOS CAMPOS
// ALTERADO: Adicionado $cpf_limpo à validação
if (empty($nome) || empty($cpf_limpo) || empty($email) || empty($whatsapp_limpo) || empty($senha)) {
    header('Location: login_novo?error=Por favor, preencha todos os campos.');
    exit;
}

if ($senha !== $confirma_senha) {
    header('Location: login_novo?error=As senhas não correspondem.');
    exit;
}

if (strlen($senha) < 6) {
    header('Location: login_novo?error=A senha deve ter pelo menos 6 caracteres.');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: login_novo?error=O formato do e-mail é inválido.');
    exit;
}

try {
    // 3. VERIFICAÇÃO DE DUPLICIDADE (CPF, WHATSAPP OU EMAIL)
    // ALTERADO: Adicionado a verificação de CPF duplicado
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE email = ? OR whatsapp = ? OR cpf = ?");
    $stmt->execute([$email, $whatsapp_limpo, $cpf_limpo]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: login_novo?error=O e-mail, WhatsApp ou CPF/CNPJ informado já está cadastrado.');
        exit;
    }

    // Criptografa a senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // 4. INSERÇÃO DO NOVO USUÁRIO COM STATUS PENDENTE
    // ALTERADO: Adicionada a coluna `cpf` e ajustada a permissão para 2 (Admin)
    $sql = "INSERT INTO tbl_usuarios (nome, cpf, email, whatsapp, senha, permissao, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // Por padrão para empresas: permissao=2 (Admin) e status=0 (Pendente de Aprovação)
    $success = $stmt->execute([$nome, $cpf_limpo, $email, $whatsapp_limpo, $senha_hash, 2, 0]);

    if ($success) {
        // Opcional: Enviar notificação para o admin ou mensagem de boas-vindas para o usuário
        sendWonWelcomeMessage($nome, $whatsapp_limpo); // Exemplo de função que poderia ser usada

        // Redireciona para a página de sucesso com a mensagem de aguardar liberação
        header('Location: login_sucesso');
        exit;
    } else {
        header('Location: login_novo?error=Ocorreu um erro ao criar sua conta. Tente novamente.');
        exit;
    }

} catch (PDOException $e) {
    // Adiciona log de erro mais detalhado
    error_log("Erro PDO no registro público (login_novo_process): " . $e->getMessage());
    // Verifica se o erro é de duplicidade para dar uma mensagem mais amigável
    if ($e->errorInfo[1] == 1062) { // 1062 é o código de erro para "Duplicate entry"
         header('Location: login_novo?error=O e-mail, WhatsApp ou CPF/CNPJ informado já está cadastrado.');
    } else {
         header('Location: login_novo?error=Ocorreu um erro interno no servidor. Tente mais tarde.');
    }
    exit;
}
?>