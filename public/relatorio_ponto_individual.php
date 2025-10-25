<?php
// Inclui o init.php para segurança e conexão com o banco ($pdo)
require_once __DIR__ . '/includes/init.php';

// --- 1. VALIDAÇÃO DOS PARÂMETROS DE ENTRADA ---
// CORREÇÃO 1: Substituído FILTER_SANITIZE_STRING.
// Acessamos o valor diretamente e confiamos na validação com preg_match abaixo.
$mes_ano_param = $_GET['mes'] ?? null; 
$colaborador_user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// O formato esperado é "YYYY-MM", ex: "2025-08"
if (!$colaborador_user_id || !preg_match('/^\d{4}-\d{2}$/', $mes_ano_param)) {
    // Se os parâmetros estiverem ausentes ou em formato incorreto, exibe um erro.
    die("Parâmetros inválidos. É necessário fornecer o ID do colaborador e o mês/ano no formato AAAA-MM.");
}

try {
    // --- 2. PREPARAÇÃO DAS DATAS ---
    $periodo = new DateTime($mes_ano_param . '-01');
    $mes_ano_titulo = $periodo->format('m/Y');
    $mes_numerico = (int)$periodo->format('m');
    $ano_numerico = (int)$periodo->format('Y');

    // --- 3. BUSCA DOS DADOS NO BANCO ---
    
    // Busca os dados cadastrais do colaborador na tbl_usuarios
    $stmt_user = $pdo->prepare(
        "SELECT nome, cpf, matricula, setor, cargo FROM tbl_usuarios WHERE id_user = ?"
    );
    $stmt_user->execute([$colaborador_user_id]);
    $dados_colaborador = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$dados_colaborador) {
        die("Colaborador com ID {$colaborador_user_id} não encontrado.");
    }

    // Busca todos os registros de ponto do mês na tbl_ponto
    $stmt_ponto = $pdo->prepare(
        "SELECT * FROM tbl_ponto WHERE id_user = ? AND YEAR(data) = ? AND MONTH(data) = ? ORDER BY data ASC"
    );
    $stmt_ponto->execute([$colaborador_user_id, $ano_numerico, $mes_numerico]);
    $registros_ponto = $stmt_ponto->fetchAll(PDO::FETCH_ASSOC);

    // Busca o resumo do mês na tbl_ponto_resumo
    $stmt_resumo = $pdo->prepare(
        "SELECT * FROM tbl_ponto_resumo WHERE id_user = ? AND ano = ? AND mes = ?"
    );
    $stmt_resumo->execute([$colaborador_user_id, $ano_numerico, $mes_numerico]);
    $dados_resumo = $stmt_resumo->fetch(PDO::FETCH_ASSOC);

    // --- 4. PROCESSAMENTO E PREPARAÇÃO PARA O TEMPLATE ---

    // Organiza os registros por dia para acesso rápido no loop
    $pontos_por_dia = [];
    foreach ($registros_ponto as $registro) {
        $dia = (new DateTime($registro['data']))->format('d');
        $pontos_por_dia[$dia] = $registro;
    }
    
    // CORREÇÃO 2: Usado __DIR__ para criar um caminho absoluto para o arquivo de template.
    $template_path = __DIR__ . '/folha_de_ponto.html';
    if (!file_exists($template_path)) {
        die("ERRO CRÍTICO: O arquivo de template 'folha_de_ponto.html' não foi encontrado.");
    }
    $template = file_get_contents($template_path);

    // --- 5. PREENCHIMENTO DO CABEÇALHO ---
    $template = str_replace('{{NOME_DA_EMPRESA}}', htmlspecialchars($_ENV['APP_COMPANY_NAME'] ?? 'Empresa não configurada'), $template);
    $template = str_replace('{{CNPJ_DA_EMPRESA}}', htmlspecialchars($_ENV['APP_COMPANY_CNPJ'] ?? 'CNPJ não configurado'), $template);
    $template = str_replace('{{NOME_COMPLETO}}', htmlspecialchars($dados_colaborador['nome']), $template);
    $template = str_replace('{{CPF}}', htmlspecialchars($dados_colaborador['cpf'] ?? 'N/A'), $template);
    $template = str_replace('{{MATRICULA}}', htmlspecialchars($dados_colaborador['matricula'] ?? 'N/A'), $template);
    $template = str_replace('{{SETOR}}', htmlspecialchars($dados_colaborador['setor'] ?? 'N/A'), $template);
    $template = str_replace('{{CARGO}}', htmlspecialchars($dados_colaborador['cargo'] ?? 'N/A'), $template);
    $template = str_replace('{{MES_ANO}}', $mes_ano_titulo, $template);
    $template = str_replace('{{MES}}', $periodo->format('m'), $template);

    // --- 6. GERAÇÃO DINÂMICA DAS LINHAS DA TABELA ---
    $linhas_tabela = '';
    $total_dias_mes = (int)$periodo->format('t');
    
    for ($dia_loop = 1; $dia_loop <= $total_dias_mes; $dia_loop++) {
        $dia_formatado = str_pad($dia_loop, 2, '0', STR_PAD_LEFT);
        
        // Verifica se existe registro para este dia
        $registro_do_dia = $pontos_por_dia[$dia_formatado] ?? null;

        $linhas_tabela .= "<tr>";
        $linhas_tabela .= "<td>{$dia_formatado}/{$periodo->format('m')}</td>";
        $linhas_tabela .= "<td>" . ($registro_do_dia['hora_entrada'] ?? '') . "</td>";
        $linhas_tabela .= "<td>" . ($registro_do_dia['hora_saida_almoco'] ?? '') . "</td>";
        $linhas_tabela .= "<td>" . ($registro_do_dia['hora_retorno_almoco'] ?? '') . "</td>";
        $linhas_tabela .= "<td>" . ($registro_do_dia['hora_saida'] ?? '') . "</td>";
        // Formata o total de horas para HH:MM
        $total_horas_dia = $registro_do_dia ? gmdate('H:i', (int)($registro_do_dia['total_horas'] * 3600)) : '';
        $linhas_tabela .= "<td>" . $total_horas_dia . "</td>";
        $linhas_tabela .= "<td>" . htmlspecialchars($registro_do_dia['ocorrencias'] ?? '') . "</td>";
        $linhas_tabela .= "</tr>";
    }

    $template = preg_replace('/<tbody id="linhas">.*<\/tbody>/s', '<tbody id="linhas">' . $linhas_tabela . '</tbody>', $template);

    // --- 7. PREENCHIMENTO DOS TOTAIS USANDO DADOS DA tbl_ponto_resumo ---
    // A função gmdate converte segundos para o formato H:i
    $template = str_replace('{{H_CONTRATUAIS}}', $dados_resumo ? gmdate('H:i', (int)($dados_resumo['horas_contratuais'] * 3600)) : '00:00', $template);
    $template = str_replace('{{H_TRABALHADAS}}', $dados_resumo ? gmdate('H:i', (int)($dados_resumo['horas_trabalhadas'] * 3600)) : '00:00', $template);
    $template = str_replace('{{H_EXTRAS}}', $dados_resumo ? gmdate('H:i', (int)($dados_resumo['horas_extras'] * 3600)) : '00:00', $template);
    $template = str_replace('{{BANCO_HORAS}}', $dados_resumo ? gmdate('H:i', (int)($dados_resumo['banco_horas'] * 3600)) : '00:00', $template);
    $template = str_replace('{{FALTAS}}', $dados_resumo['faltas'] ?? '0', $template);
    $template = str_replace('{{ATRASOS}}', $dados_resumo ? gmdate('H:i', $dados_resumo['atrasos'] * 60) : '00:00', $template); // Atrasos em minutos

    // --- 8. EXIBIÇÃO DO RELATÓRIO FINAL ---
    echo $template;

} catch (PDOException $e) {
    die("Erro de banco de dados ao gerar relatório: " . $e->getMessage());
} catch (Exception $e) {
    die("Erro geral ao processar o relatório: " . $e->getMessage());
}