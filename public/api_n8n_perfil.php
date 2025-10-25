<?php

header('Content-Type: application/json');

require_once __DIR__ . '/includes/api_init.php';

// 2. DEFINE E VERIFICA O TOKEN DE SEGURANÇA
define('API_TOKEN', $_ENV['API_TOKEN']); 

$token = $_GET['token'] ?? '';
if ($token !== API_TOKEN) {
    http_response_code(401); // Unauthorized
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso nao autorizado.']);
    exit();
}

// Obtém o número de telefone da requisição GET (ex: ?telefone=5527998670627)
$telefone_input = $_GET['telefone'] ?? null;

// Prepara o array de resposta
$response = [
    'sucesso' => false,
    'erro' => ''
];

if (empty($telefone_input)) {
    $response['erro'] = 'O telefone não foi fornecido.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Normaliza o número de telefone para consulta, removendo caracteres não numéricos.
// Isso garante que formatos como "+55 (27) 99867-0627" ou "27998670627" funcionem.
$telefone_normalized = preg_replace('/[^0-9]/', '', $telefone_input);
// Pega apenas os últimos 11 dígitos, caso o DDI 55 tenha sido incluído
if (strlen($telefone_normalized) > 11) {
    $telefone_normalized = substr($telefone_normalized, 2);
}

try {
    // A consulta agora faz um auto-join na tbl_usuarios para pegar os dados do administrador
    $sql = "
        SELECT
            tu.id_user,
            tu.id_administrador,
            tu.nome,
            tu.whatsapp,
            tu.matricula,
            tu.setor,
            tu.cargo,
            ta.nome AS admin_nome,
            ta.whatsapp AS admin_whatsapp
        FROM
            tbl_usuarios tu
        LEFT JOIN
            tbl_usuarios ta ON tu.id_administrador = ta.id_user
        WHERE
            tu.whatsapp = :whatsapp AND tu.status = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':whatsapp', $telefone_normalized, PDO::PARAM_STR);
    $stmt->execute();
    $usuario = $stmt->fetch();

    if ($usuario) {
        $usuario['whatsapp'] = str_replace('@s.whatsapp.net', '', $usuario['whatsapp']);
        $response['sucesso'] = true;
        $response = array_merge($response, $usuario);
        unset($response['erro']);
    } else {
        $response['erro'] = 'Usuário não encontrado ou inativo com o telefone fornecido.'. $telefone_normalized;
    }

} catch (\PDOException $e) {
    error_log("Erro no banco de dados durante a consulta do usuário: " . $e->getMessage());
    $response['erro'] = 'Erro no banco de dados: ' . $e->getMessage();
} catch (\Exception $e) {
    error_log("Erro inesperado durante a consulta do usuário: " . $e->getMessage());
    $response['erro'] = 'Erro inesperado: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>