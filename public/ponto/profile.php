<?php

header('Content-Type: application/json'); // Define o cabeçalho para indicar conteúdo JSON

// Supondo que você tenha um arquivo de inicialização que estabelece a conexão PDO.
// Se o seu arquivo 'functions.php' já faz isso, mantenha o seu include.
// Para este exemplo, vou simular a conexão que vi no seu 'init.php'.
require_once __DIR__ . '/../includes/api_init.php'; // Use seu arquivo de inicialização que já cria a variável $pdo

// Obtém o número de telefone da requisição GET (ex: ?telefone=123456789)
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
// Isso garante que formatos como "+55 (27) 123456789" ou "27123456789" funcionem.
$telefone_normalized = preg_replace('/[^0-9]/', '', $telefone_input);
// Pega apenas os últimos 11 dígitos, caso o DDI 55 tenha sido incluído
if (strlen($telefone_normalized) > 11) {
    $telefone_normalized = substr($telefone_normalized, -11);
}


try {
    // $pdo já deve estar disponível a partir do 'init.php'
    
    // A consulta foi ajustada para buscar dados apenas da tbl_usuarios,
    // que é a tabela correta de acordo com seu script SQL.
    $sql = "
        SELECT
            id_user,
            id_administrador,
            nome,
            cpf,
            matricula,
            setor,
            cargo,
            email,
            whatsapp,
            foto,
            status,
            permissao
        FROM
            tbl_usuarios
        WHERE
            whatsapp = :whatsapp
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':whatsapp', $telefone_normalized, PDO::PARAM_STR);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $response['sucesso'] = true;
        // Mescla todos os dados do usuário encontrados na resposta
        $response['dados'] = $usuario; 
        unset($response['erro']); // Remove a chave de erro em caso de sucesso
    } else {
        $response['erro'] = 'Usuário não encontrado com o telefone fornecido.';
    }

} catch (\PDOException $e) {
    error_log("Erro no banco de dados durante a consulta do perfil: " . $e->getMessage());
    // Em produção, evite expor detalhes do erro.
    $response['erro'] = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.'; 
} catch (\Exception $e) {
    error_log("Erro inesperado durante a consulta do perfil: " . $e->getMessage());
    $response['erro'] = 'Ocorreu um erro inesperado.';
}

// Retorna a resposta JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>