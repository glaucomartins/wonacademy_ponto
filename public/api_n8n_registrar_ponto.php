<?php

header('Content-Type: application/json');

// ADAPTADO: Usa o inicializador padrão da API para conexão com DB
require_once __DIR__ . '/includes/api_init.php';

// ADAPTADO: Adicionada autenticação por token para segurança
define('API_TOKEN', $_ENV['API_TOKEN'] ?? ''); 
$token = $_GET['token'] ?? ''; // O token é passado via URL
if (empty(API_TOKEN) || $token !== API_TOKEN) {
    http_response_code(401); // Não autorizado
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso não autorizado.']);
    exit();
}

try {
    // Receber os dados da requisição POST (JSON)
    $input = json_decode(file_get_contents('php://input'), true);

    // ADAPTADO: Validação e tratamento para 'id_user'
    $id_user = filter_var($input['id_user'] ?? null, FILTER_VALIDATE_INT);
    $n8n_timestamp_registro = $input['timestamp_n8n'] ?? null;
    $ocorrencias_input = $input['ocorrencias'] ?? null;

    // --- TRATAMENTO DOS DADOS DE DATA/HORA DO N8N ---
    if (empty($n8n_timestamp_registro)) {
        echo json_encode(['sucesso' => false, 'erro' => 'O campo timestamp_n8n é obrigatório.']);
        exit();
    }

    try {
        $datetime_obj_registro = new DateTime($n8n_timestamp_registro);
        $data_do_registro = $datetime_obj_registro->format('Y-m-d');
        $hora_da_batida_atual = $datetime_obj_registro->format('H:i:s');
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'erro' => 'Formato de timestamp_n8n inválido: ' . $e->getMessage()]);
        exit();
    }

    // ADAPTADO: Validação para id_user
    if (empty($id_user)) {
        echo json_encode(['sucesso' => false, 'erro' => 'O campo id_user é obrigatório.']);
        exit();
    }

    // ADAPTADO: Procura registro de ponto usando id_user
    $sql_select = "SELECT id_ponto, hora_entrada, hora_saida_almoco, hora_retorno_almoco, hora_saida FROM tbl_ponto WHERE id_user = :id_user AND data = :data_do_registro";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->bindValue(':id_user', $id_user, PDO::PARAM_INT);
    $stmt_select->bindValue(':data_do_registro', $data_do_registro);
    $stmt_select->execute();
    $registro_existente = $stmt_select->fetch();

    $mensagem = '';
    $total_horas_calculado = null;

    if ($registro_existente) {
        // --- REGISTRO EXISTENTE: FAZER UPDATE ---
        $update_fields = [];
        $update_values = [':id_ponto' => $registro_existente['id_ponto']];

        if (empty($registro_existente['hora_entrada'])) {
            $update_fields[] = 'hora_entrada = :hora';
            $mensagem = 'Hora de entrada registrada.';
        } elseif (empty($registro_existente['hora_saida_almoco'])) {
            $update_fields[] = 'hora_saida_almoco = :hora';
            $mensagem = 'Hora de saída do almoço registrada.';
        } elseif (empty($registro_existente['hora_retorno_almoco'])) {
            $update_fields[] = 'hora_retorno_almoco = :hora';
            $mensagem = 'Hora de retorno do almoço registrada.';
        } elseif (empty($registro_existente['hora_saida'])) {
            $update_fields[] = 'hora_saida = :hora';
            $mensagem = 'Hora de saída registrada.';

            // Calcula o total de horas ao fechar o dia
            $total_horas_calculado = calcularTotalHoras(
                $registro_existente['hora_entrada'],
                $registro_existente['hora_saida_almoco'],
                $registro_existente['hora_retorno_almoco'],
                $hora_da_batida_atual
            );
            if (!is_null($total_horas_calculado)) {
                $update_fields[] = 'total_horas = :total_horas';
                $update_values[':total_horas'] = $total_horas_calculado;
                $mensagem .= ' Total de horas calculado.';
            }
        } else {
            // Todos os pontos preenchidos, considera como uma re-batida da saída
            $update_fields[] = 'hora_saida = :hora';
            $mensagem = 'Hora de saída atualizada (re-batida).';

            // Recalcula o total de horas
            $total_horas_calculado = calcularTotalHoras(
                $registro_existente['hora_entrada'],
                $registro_existente['hora_saida_almoco'],
                $registro_existente['hora_retorno_almoco'],
                $hora_da_batida_atual
            );
            if (!is_null($total_horas_calculado)) {
                $update_fields[] = 'total_horas = :total_horas';
                $update_values[':total_horas'] = $total_horas_calculado;
                $mensagem .= ' Total de horas recalculado.';
            }
        }
        $update_values[':hora'] = $hora_da_batida_atual;
        
        if (!is_null($ocorrencias_input)) {
            $update_fields[] = 'ocorrencias = :ocorrencias';
            $update_values[':ocorrencias'] = $ocorrencias_input;
        }

        if (!empty($update_fields)) {
            $sql_update = "UPDATE tbl_ponto SET " . implode(', ', $update_fields) . " WHERE id_ponto = :id_ponto";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute($update_values);
            echo json_encode(['sucesso' => true, 'mensagem' => $mensagem, 'total_horas_calculado' => $total_horas_calculado]);
        } else {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Nenhum campo para atualizar.']);
        }

    } else {
        // --- NENHUM REGISTRO EXISTENTE: FAZER INSERT ---
        // ADAPTADO: Insere usando id_user
        $sql_insert = "INSERT INTO tbl_ponto (id_user, data, hora_entrada, ocorrencias)
                       VALUES (:id_user, :data, :hora_entrada, :ocorrencias)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            ':id_user' => $id_user,
            ':data' => $data_do_registro,
            ':hora_entrada' => $hora_da_batida_atual,
            ':ocorrencias' => $ocorrencias_input
        ]);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Ponto de entrada registrado com sucesso!']);
    }

} catch (\PDOException $e) {
    error_log("Erro PDO ao processar ponto: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'erro' => 'Erro no banco de dados.']);
} catch (\Exception $e) {
    error_log("Erro inesperado ao processar ponto: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'erro' => 'Erro inesperado.']);
}

/**
 * Calcula o total de horas trabalhadas. (Função mantida como no original)
 */
function calcularTotalHoras($entrada, $saida_almoco, $retorno_almoco, $saida) {
    if (empty($entrada) || empty($saida)) {
        return null;
    }
    try {
        $e = new DateTime($entrada);
        $s = new DateTime($saida);
        $total_segundos = $s->getTimestamp() - $e->getTimestamp();

        if (!empty($saida_almoco) && !empty($retorno_almoco)) {
            $sa = new DateTime($saida_almoco);
            $ra = new DateTime($retorno_almoco);
            if ($ra > $sa) {
                $segundos_almoco = $ra->getTimestamp() - $sa->getTimestamp();
                $total_segundos -= $segundos_almoco;
            }
        }
        return round($total_segundos / 3600, 2);
    } catch (Exception $e) {
        error_log("Erro ao calcular total_horas: " . $e->getMessage());
        return null;
    }
}
?>