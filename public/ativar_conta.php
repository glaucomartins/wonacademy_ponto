<?php
// Inclui o inicializador para acesso ao banco e variáveis de ambiente
require_once __DIR__ . '/includes/api_init.php';

// --- 1. VERIFICAÇÃO DE SEGURANÇA ---
// Pega o token da URL e o token secreto do .env
$token_recebido = $_GET['token'] ?? 'DCE53E85D266C8299A1B442CC546E';
$token_secreto = $_ENV['API_TOKEN'] ?? '';

// Se o token estiver vazio ou não bater com o secreto, o acesso é negado.
if (empty($token_secreto) || $token_recebido !== $token_secreto) {
    http_response_code(401); // Unauthorized
    die("Acesso nao autorizado. Token invalido ou ausente.");
}

// --- 2. OBTENÇÃO E VALIDAÇÃO DOS DADOS ---
$telefone_input = $_GET['telefone'] ?? null;

if (empty($telefone_input)) {
    // Se o telefone não for fornecido, redireciona com erro
    header('Location: login?activation_status=missing_phone');
    exit();
}

// Limpa o telefone para garantir que apenas números sejam usados na consulta
$whatsapp_limpo = preg_replace('/\D/', '', $telefone_input);

try {
    // --- 3. ATUALIZAÇÃO NO BANCO DE DADOS ---
    // Prepara o comando SQL para atualizar o status do usuário de 0 (pendente) para 1 (ativo)
    // A condição `status = 0` garante que a operação só afete contas pendentes.
    $sql = "UPDATE tbl_usuarios SET status = 1 WHERE whatsapp = ? AND status = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$whatsapp_limpo]);

    // --- 4. VERIFICAÇÃO E REDIRECIONAMENTO ---
    // Verifica se alguma linha foi de fato alterada no banco
    if ($stmt->rowCount() > 0) {
        // Sucesso! Uma conta foi ativada. Redireciona para o login com status de sucesso.
        header('Location: login?activation_status=success');
        exit();
    } else {
        // Nenhuma linha foi alterada. Ou o usuário não existe, ou a conta já estava ativa.
        header('Location: login?activation_status=notfound_or_active');
        exit();
    }

} catch (PDOException $e) {
    // Em caso de erro no banco de dados, registra o erro e redireciona
    error_log("Erro ao ativar conta: " . $e->getMessage());
    header('Location: login?activation_status=dberror');
    exit();
}
?>