<?php
header('Content-Type: application/json');

require_once __DIR__ . '/includes/api_init.php';

// 2. DEFINE E VERIFICA O TOKEN DE SEGURANÇA
define('API_TOKEN', $_ENV['API_TOKEN']); 

$token = $_GET['token'] ?? '';
if ($token !== API_TOKEN) {
    http_response_code(401); // Unauthorized
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso não autorizado.']);
    exit();
} 

$telefone_input = $_GET['telefone'] ?? '';

// Validação básica da entrada
if (empty($telefone_input)) {
    http_response_code(400); // Bad Request
    echo json_encode(['sucesso' => false, 'erro' => 'O telefone não foi fornecido.']);
    exit();
}

// Lógica para normalizar o telefone
$sem_sufixo = str_replace('@s.whatsapp.net', '', $telefone_input);
$telefone_normalized = preg_replace('/^55/', '', $sem_sufixo);

try {
    // --- SQL MODIFICADA COM LEFT JOIN ---
    // A consulta agora junta a tabela de usuários (apelidada de 'u')
    // com a tabela de instâncias (apelidada de 'i').
    $sql = "
        SELECT 
            u.id_user, 
            u.id_administrador, 
            u.nome, 
            u.whatsapp,
            i.instanceName,
            i.hash,
            i.connectionStatus
        FROM 
            tbl_usuarios AS u
        LEFT JOIN 
            tbl_instancia AS i ON u.id_administrador = i.id_usuario
        WHERE 
            u.whatsapp = :telefone_input OR u.whatsapp = :telefone_normalized
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':telefone_input', $telefone_input, PDO::PARAM_STR);
    $stmt->bindValue(':telefone_normalized', $telefone_normalized, PDO::PARAM_STR);
    $stmt->execute();
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($colaborador) {
        // Remove o sufixo do whatsapp para o retorno do JSON
        $colaborador['telefone'] = str_replace('@s.whatsapp.net', '', $colaborador['whatsapp']);
        
        // Adiciona a flag de sucesso e retorna os dados combinados
        echo json_encode(array_merge($colaborador, ['sucesso' => true]), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Colaborador não encontrado.'], JSON_UNESCAPED_UNICODE);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    // Para depuração, você pode querer logar o erro: error_log($e->getMessage());
    echo json_encode(['sucesso' => false, 'erro' => 'Erro de consulta ao banco de dados.'], JSON_UNESCAPED_UNICODE);
}
?>