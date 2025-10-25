<?php
// --- NOVO TESTE DE DIAGNÓSTICO ---
$autoloaderPath = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloaderPath)) {
    // Se o arquivo for encontrado, carrega-o.
    require_once $autoloaderPath;
} else {
    // Se não for encontrado, para tudo e mostra uma mensagem clara de erro.
    die("ERRO CRÍTICO: O autoloader do Composer não foi encontrado no caminho esperado.<br>Caminho verificado: " . realpath(__DIR__ . '/..') . "/vendor/autoload.php");
}

use Spatie\Image\Image;

// ===== FUNÇÕES DE GERENCIAMENTO DE USUÁRIOS =====

/**
 * Traduz o nível de permissão numérico para um texto.
 */
function getRoleName(int $permissao): string {
    switch ($permissao) {
        case 1:
            return 'Super Admin';
        case 2:
            return 'Admin';
        case 3:
            return 'Usuário';
        default:
            return 'Desconhecido';
    }
}

/**
 * Busca a lista completa de usuários do painel.
 */
function getListaUsuarios(PDO $pdo): array {
    return $pdo->query("SELECT id_user, nome, email, whatsapp, status, permissao FROM tbl_usuarios ORDER BY id_user ASC")->fetchAll();
}

/**
 * Adiciona um novo usuário ao banco de dados.
 */
function adicionarUsuario(PDO $pdo, string $whatsapp, string $senha, int $permissao): bool {
    // Criptografa a senha antes de salvar
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = "INSERT INTO tbl_usuarios (whatsapp, senha, permissao, status) VALUES (?, ?, ?, 1)";
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$whatsapp, $senha_hash, $permissao]);
    } catch (PDOException $e) {
        return false; // Falha (provavelmente whatsapp duplicado)
    }
}

/**
 * Exclui um usuário do banco de dados.
 */
function excluirUsuario(PDO $pdo, int $id): bool {
    // Impede que o usuário se auto-exclua
    if (isset($_SESSION['user_id']) && $id === $_SESSION['user_id']) {
        return false;
    }
    $sql = "DELETE FROM tbl_usuarios WHERE id_user = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

// ===== FUNÇÕES DE PERFIL DE USUÁRIO =====

/**
 * Busca todos os dados de um usuário pelo seu ID.
 */
function getUsuarioPorId(PDO $pdo, int $id): ?array
{
    // MUDANÇA: Adicionado o campo 'notificacoes' à consulta
    $stmt = $pdo->prepare("SELECT id_user, nome, email, whatsapp, cpf, permissao, status, notificacoes FROM tbl_usuarios WHERE id_user = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Atualiza os dados do perfil de um usuário.
 */
function atualizarPerfilUsuario(PDO $pdo, int $id, string $whatsapp, ?string $nova_senha): bool
{
    // A senha só é atualizada se uma nova for fornecida.
    if (!empty($nova_senha)) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql = "UPDATE tbl_usuarios SET whatsapp = ?, senha = ? WHERE id_user = ?";
        $params = [$whatsapp, $senha_hash, $id];
    } else {
        $sql = "UPDATE tbl_usuarios SET whatsapp = ? WHERE id_user = ?";
        $params = [$whatsapp, $id];
    }

    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        // Provavelmente erro de whatsapp duplicado
        error_log("Erro ao atualizar perfil: " . $e->getMessage());
        return false;
    }
}

/**
 * Busca os dados do usuário atualmente logado na sessão.
 * Retorna os dados do usuário ou null se não for encontrado.
 */
function getAuthenticatedUser(PDO $pdo, int $user_id, string $login_token): ?array {
    $stmt = $pdo->prepare("SELECT * FROM tbl_usuarios WHERE id_user = ? AND token_login = ? AND status = 1");
    $stmt->execute([$user_id, $login_token]);
    $user = $stmt->fetch();

    if ($user) {
        // Adiciona o nome do "cargo" (role) para facilitar a exibição
        $user['role_name'] = getRoleName($user['permissao']);
        return $user;
    }

    return null;
}


/**
 * Envia uma mensagem de boas-vindas via API da Won.
 * @param string $nome O nome do novo usuário.
 * @param string $whatsapp O número de WhatsApp (apenas dígitos).
 */
function sendWonWelcomeMessage(string $nome, string $whatsapp): void {
    $endpoint = $_ENV['WON_API_ENDPOINT'] ?? ''; 
    $apiKey = $_ENV['WON_API_KEY'] ?? '';

    if (empty($endpoint) || empty($apiKey)) {
        error_log("API da Won não configurada no .env. Mensagem não enviada.");
        return;
    }
    
    $ativar_url = "https://ponto.seusite.com.br/ativar_conta?telefone=".$whatsapp;

    // Personalize a mensagem de boas-vindas aqui
$mensagem = "Olá, {$nome}! Seu cadastro em nossa plataforma foi realizado com sucesso.

Ative no link abaixo.

{$ativar_url}

*Obrigado!*";

    $payload = json_encode([
        'number' => '55'.$whatsapp,
        'text' => $mensagem
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Erro no cURL ao enviar para a API Won: ' . curl_error($ch));
    }
    curl_close($ch);
}

/**
 * Envia um código de recuperação de senha via API da Won.
 * @param string $nome O nome do usuário.
 * @param string $whatsapp O número de WhatsApp (apenas dígitos).
 * @param string $token O código de 6 dígitos para a recuperação.
 */
function sendWonPasswordResetToken(string $nome, string $whatsapp, string $token): void {
    $endpoint = $_ENV['WON_API_ENDPOINT'] ?? '';
    $apiKey = $_ENV['WON_API_KEY'] ?? '';

    if (empty($endpoint) || empty($apiKey)) {
        error_log("API da Won não configurada no .env. Mensagem de recuperação não enviada.");
        return;
    }
    
    // Personalize a mensagem de recuperação aqui
    $mensagem = "Olá, {$nome}! Seu código para recuperação de senha é: *{$token}*\n\nEste código é válido por 15 minutos.";

    $payload = json_encode([
        'number' => '55'.$whatsapp,
        'text' => $mensagem
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Erro no cURL ao enviar token para a API Won: ' . curl_error($ch));
    }
    curl_close($ch);
}

/**
 * Envia as credenciais de acesso para um novo usuário via API da Won,
 * utilizando uma instância específica do administrador que está realizando a ação.
 *
 * @param PDO $pdo O objeto de conexão com o banco de dados.
 * @param int $adminId O ID do administrador que está criando o novo usuário.
 * @param string $nome O nome do novo usuário.
 * @param string $whatsapp O número de WhatsApp do novo usuário (apenas dígitos).
 * @param string $senha A senha em texto plano criada para o usuário.
 * @param string $loginUrl A URL completa para a página de login.
 */
function sendWonNewUserCredentials(PDO $pdo, int $adminId, string $nome, string $whatsapp, string $senha, string $loginUrl): void {
    $apiUrlBase = rtrim($_ENV['EVOLUTION_API'], '/') ?? '';
    if (empty($apiUrlBase)) {
        error_log("URL base da API (EVOLUTION_API) não configurada no .env.");
        return;
    }

    try {
        // 1. Busca uma instância ATIVA do administrador para usar como remetente
        $stmt = $pdo->prepare("SELECT instanceName, hash FROM tbl_instancia WHERE id_usuario = ? AND connectionStatus = 'open' LIMIT 1");
        $stmt->execute([$adminId]);
        $instanciaRemetente = $stmt->fetch();

        if (!$instanciaRemetente) {
            error_log("Nenhuma instância conectada encontrada para o administrador ID {$adminId}. Mensagem de credenciais não enviada.");
            return;
        }
        
        // 2. Monta o endpoint e a chave de API específicos da instância
        $instanceName = $instanciaRemetente['instanceName'];
        $apiKey = $instanciaRemetente['hash']; // A API Key agora é o hash da instância
        $endpoint = $apiUrlBase . '/message/sendText/' . $instanceName;

        // 3. Prepara e envia a mensagem
        $mensagem = "Olá, {$nome}!\n\nSua conta em nossa plataforma foi criada com sucesso.\n\n";
        $mensagem .= "Acesse agora mesmo em:\n{$loginUrl}\n\n";
        $mensagem .= "Use seu e-mail ou WhatsApp para logar.\n";
        $mensagem .= "Sua senha de acesso é: *{$senha}*";

        $payload = json_encode(['number' => '55'.$whatsapp, 'text' => $mensagem]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $apiKey // Usa a chave da instância
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("Erro no cURL ao enviar credenciais pela instância {$instanceName}: " . curl_error($ch));
        }
        curl_close($ch);

    } catch (Exception $e) {
        error_log("Erro de banco de dados ao buscar instância do remetente: " . $e->getMessage());
    }
}


