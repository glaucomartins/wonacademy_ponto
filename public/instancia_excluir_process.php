<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

require __DIR__ . '/includes/init.php'; // Garante que o usuário está logado

$instanceName = $_POST['instanceName'] ?? '';
if (empty($instanceName)) {
    header('Location: instancias?delete_status=error&message=Nome da instância não fornecido.');
    exit;
}

// Função para fazer a chamada cURL à API
function callEvolutionApi($endpoint, $apiKey, $method = 'DELETE') {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey],
        CURLOPT_TIMEOUT => 20
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    $apiUrlBase = rtrim($_ENV['EVOLUTION_API'], '/');
    $apiKey = $_ENV['EVOLUTION_API_KEY'];

    // 1. DESCONECTAR (LOGOUT) a instância - Boa prática antes de deletar
    $logoutUrl = $apiUrlBase . '/instance/logout/' . $instanceName;
    callEvolutionApi($logoutUrl, $apiKey); // Não precisamos verificar o resultado, tentamos desconectar

    // 2. DELETAR a instância permanentemente na API
    $deleteUrl = $apiUrlBase . '/instance/delete/' . $instanceName;
    $deleteHttpCode = callEvolutionApi($deleteUrl, $apiKey);

    // A API retorna 200 para sucesso na exclusão. Se falhar, informamos o erro mas prosseguimos para apagar do nosso banco.
    if ($deleteHttpCode !== 200) {
        // Opcional: registrar o erro da API em um log
        error_log("API da Evolution falhou ao deletar a instância {$instanceName}. Código HTTP: {$deleteHttpCode}");
    }

    // 3. REMOVER a instância do nosso banco de dados
    $stmt = $pdo->prepare("DELETE FROM tbl_instancia WHERE instanceName = ? AND id_usuario = ?");
    $stmt->execute([$instanceName, $currentUser['id_user']]);

    // Redireciona de volta com mensagem de sucesso
    header('Location: instancias?delete_status=success&message=Instância ' . urlencode($instanceName) . ' excluída com sucesso.');
    exit;

} catch (Exception $e) {
    error_log("Erro ao excluir instância: " . $e->getMessage());
    header('Location: instancias?delete_status=error&message=Ocorreu um erro interno ao excluir a instância.');
    exit;
}