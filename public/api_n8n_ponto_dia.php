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

// 2. OBTENÇÃO E VALIDAÇÃO DOS DADOS DE ENTRADA
$telefone_input = $_GET['telefone'] ?? null;

$response = ['sucesso' => false, 'erro' => ''];

if (empty($telefone_input)) {
    http_response_code(400);
    $response['erro'] = 'O telefone nao foi fornecido.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

$telefone_normalized = preg_replace('/[^0-9]/', '', $telefone_input);
if (strlen($telefone_normalized) > 11) {
    $telefone_normalized = substr($telefone_normalized, -11);
}

date_default_timezone_set('America/Sao_Paulo');
$data_atual = date('Y-m-d');

try {
    // 3. CONSULTA AO BANCO DE DADOS
    $sql = "
        SELECT
            u.id_user, u.nome, u.whatsapp, u.matricula, u.setor, u.cargo,
            p.id_ponto, p.data, p.hora_entrada, p.hora_saida_almoco, p.hora_retorno_almoco, 
            p.hora_saida, p.total_horas, p.ocorrencias, p.created_at AS ponto_registrado_em
        FROM
            tbl_usuarios u
        LEFT JOIN
            tbl_ponto p ON u.id_user = p.id_user AND p.data = :data_atual
        WHERE
            u.whatsapp = :whatsapp
            AND u.status = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':whatsapp', $telefone_normalized, PDO::PARAM_STR);
    $stmt->bindValue(':data_atual', $data_atual, PDO::PARAM_STR);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. TRATAMENTO DO RESULTADO E GERAÇÃO DA RESPOSTA
    if ($resultado) {
        $response['sucesso'] = true;
        $response['ponto_do_dia_existe'] = !is_null($resultado['id_ponto']);
        
        // ========================================================================
        // ## INÍCIO DA FORMATAÇÃO PARA O PADRÃO BRASILEIRO ##
        // ========================================================================

        // Formata o campo 'data' se não for nulo
        if (!is_null($resultado['data'])) {
            $resultado['data'] = (new DateTime($resultado['data']))->format('d/m/Y');
        }

        // Formata o campo 'ponto_registrado_em' (timestamp) se não for nulo
        if (!is_null($resultado['ponto_registrado_em'])) {
            $resultado['ponto_registrado_em'] = (new DateTime($resultado['ponto_registrado_em']))->format('d/m/Y H:i:s');
        }

        // Os campos de hora (TIME) geralmente já vêm no formato H:i:s,
        // mas podemos garantir a formatação para remover os segundos se desejado (ex: H:i).
        // Para este caso, manteremos H:i:s que já é o padrão.

        // ========================================================================
        // ## FIM DA FORMATAÇÃO ##
        // ========================================================================
        
        $response['dados_relatorio'] = $resultado;
        unset($response['erro']);

    } else {
        http_response_code(404);
        $response['erro'] = 'Usuario nao encontrado ou inativo com o telefone fornecido.';
    }

} catch (PDOException $e) {
    error_log("Erro no banco de dados em api_n8n_ponto_dia.php: " . $e->getMessage());
    http_response_code(500);
    $response['erro'] = 'Erro interno no servidor ao consultar o banco de dados.';
}

// 5. ENVIO DA RESPOSTA JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>