<?php

header('Content-Type: application/json');

// Inclui o inicializador da API (que carrega .env, conexão PDO, etc.)
require_once __DIR__ . '/includes/api_init.php';

// Define e verifica o token de segurança a partir das variáveis de ambiente
define('API_TOKEN', $_ENV['API_TOKEN']); 

$token = $_GET['token'] ?? '';
if ($token !== API_TOKEN) {
    http_response_code(401); // Unauthorized
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso nao autorizado.']);
    exit();
}

// Obtém o ID do usuário da requisição GET (ex: ?id_user=1)
$id_usuario_input = $_GET['id'] ?? null;

// Prepara o array de resposta padrão
$response = [
    'sucesso' => false,
    'erro' => ''
];

if (empty($id_usuario_input)) {
    http_response_code(400); // Bad Request
    $response['erro'] = 'O id_user nao foi fornecido.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

try {
    // Consulta SQL que une tbl_usuarios com tbl_instancia
    // para buscar os dados do admin e da sua instância ativa e conectando.
    $sql = "
        SELECT
            u.id_user,
            u.nome,
            u.whatsapp,
            i.instanceName,
            i.instanceId,
            i.hash,
            i.status,
            i.connectionStatus
        FROM
            tbl_usuarios u
        JOIN
            tbl_instancia i ON u.id_user = i.id_usuario
        WHERE
            u.id_user = :id_user
            AND i.status = 'connecting'
            AND i.connectionStatus = 'open'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_user', $id_usuario_input, PDO::PARAM_INT);
    $stmt->execute();
    $administrador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($administrador) {
        // Se encontrou, formata a resposta de sucesso
        $response['sucesso'] = true;
        
        // ADICIONADO: Inclui as variáveis de ambiente solicitadas
        // Usamos o operador '??' para evitar erros caso a variável não exista no .env
        $administrador['app_url'] = $_ENV['APP_URL'] ?? '';
        $administrador['app_api_token'] = $_ENV['API_TOKEN'] ?? '';

        $response['dados'] = $administrador;
        unset($response['erro']);

    } else {
        // Se não encontrou, verifica a causa
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tbl_instancia WHERE id_usuario = :id_user AND (connectionStatus != 'open' OR status != 'connecting')");
        $stmt_check->bindValue(':id_user', $id_usuario_input, PDO::PARAM_INT);
        $stmt_check->execute();
        $instanciaInativa = $stmt_check->fetchColumn();

        if ($instanciaInativa > 0) {
            $response['erro'] = 'A instancia para este administrador esta desconectada ou nao esta pronta para conexao.';
        } else {
            $response['erro'] = 'Administrador nao encontrado ou nenhuma instancia ativa e conectando associada.';
        }
        http_response_code(404); // Not Found
    }

} catch (PDOException $e) {
    // Captura erros de banco de dados
    error_log("Erro no banco de dados em api_n8n_administrador.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    $response['erro'] = 'Erro interno no servidor ao consultar o banco de dados.';
}

// Retorna a resposta final em formato JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>