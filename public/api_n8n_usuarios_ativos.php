<?php

header('Content-Type: application/json');

// 1. INICIALIZAÇÃO E SEGURANÇA
require_once __DIR__ . '/includes/api_init.php';

define('API_TOKEN', $_ENV['API_TOKEN']); 
$token = $_GET['token'] ?? '';
if ($token !== API_TOKEN) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso nao autorizado.']);
    exit(); 
}

$response = ['sucesso' => false, 'erro' => ''];

try {
    // 3. CONSULTA AO BANCO DE DADOS
    $sql = "
        SELECT
            u.id_user, u.nome, u.whatsapp, u.matricula, u.setor, u.cargo
        FROM
            tbl_usuarios u
        WHERE
            u.status = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. TRATAMENTO DO RESULTADO E GERAÇÃO DA RESPOSTA
    if ($resultado) {
        $response['sucesso'] = true;
        $response['dados_relatorio'] = $resultado;
        unset($response['erro']);

    } else {
        http_response_code(404);
        $response['erro'] = 'Nenhum usuário ativo encontrado.';
    }

} catch (PDOException $e) {
    error_log("Erro no banco de dados em api_n8n_ponto_dia.php: " . $e->getMessage());
    http_response_code(500);
    $response['erro'] = 'Erro interno no servidor ao consultar o banco de dados.';
}

// 5. ENVIO DA RESPOSTA JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>