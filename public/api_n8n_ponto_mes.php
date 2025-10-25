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

$response = [
    'sucesso' => false,
    'erro' => '',
    'dados_relatorio' => []
];

if (empty($telefone_input)) {
    http_response_code(400);
    $response['erro'] = 'O telefone nao foi fornecido.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Normaliza o número de telefone
$telefone_normalized = preg_replace('/[^0-9]/', '', $telefone_input);
if (strlen($telefone_normalized) > 11) {
    $telefone_normalized = substr($telefone_normalized, -11);
}

// 3. DEFINIÇÃO DO INTERVALO DE DATAS (MÊS ATUAL)
date_default_timezone_set('America/Sao_Paulo');
$data_inicio_mes = date('Y-m-01'); // Pega o primeiro dia do mês corrente
$data_fim_mes = date('Y-m-t');  // Pega o último dia do mês corrente

try {
    // 4. CONSULTA AO BANCO DE DADOS
    $stmt_user = $pdo->prepare("SELECT id_user, nome, whatsapp FROM tbl_usuarios WHERE whatsapp = :whatsapp AND status = 1");
    $stmt_user->bindValue(':whatsapp', $telefone_normalized, PDO::PARAM_STR);
    $stmt_user->execute();
    $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $sql_ponto = "
            SELECT
                data, hora_entrada, hora_saida_almoco, hora_retorno_almoco, 
                hora_saida, total_horas, ocorrencias
            FROM
                tbl_ponto
            WHERE
                id_user = :id_user
                AND data BETWEEN :data_inicio AND :data_fim
            ORDER BY
                data ASC
        ";

        $stmt_ponto = $pdo->prepare($sql_ponto);
        $stmt_ponto->bindValue(':id_user', $usuario['id_user'], PDO::PARAM_INT);
        $stmt_ponto->bindValue(':data_inicio', $data_inicio_mes, PDO::PARAM_STR);
        $stmt_ponto->bindValue(':data_fim', $data_fim_mes, PDO::PARAM_STR);
        $stmt_ponto->execute();
        $registros_do_mes = $stmt_ponto->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. FORMATAÇÃO E CÁLCULOS
        $total_geral_horas = array_sum(array_column($registros_do_mes, 'total_horas'));

        $pontos_formatados = [];
        foreach ($registros_do_mes as $ponto) {
            $ponto['data'] = (new DateTime($ponto['data']))->format('d/m/Y');
            $pontos_formatados[] = $ponto;
        }
        
        $response['sucesso'] = true;
        $response['dados_usuario'] = $usuario;
        
        $response['periodo_consulta'] = [
            'mes_referencia' => date('m/Y'),
            'inicio' => date('d/m/Y', strtotime($data_inicio_mes)),
            'fim' => date('d/m/Y', strtotime($data_fim_mes)),
            'total_geral_horas' => number_format($total_geral_horas, 2, ',', '.')
        ];
        
        $response['registros_do_mes'] = $pontos_formatados;
        unset($response['erro']);

    } else {
        http_response_code(404);
        $response['erro'] = 'Usuario nao encontrado ou inativo com o telefone fornecido.';
    }

} catch (PDOException $e) {
    error_log("Erro no banco de dados em api_n8n_ponto_mes.php: " . $e->getMessage());
    http_response_code(500);
    $response['erro'] = 'Erro interno no servidor ao consultar o banco de dados.';
}

// 6. ENVIO DA RESPOSTA JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>