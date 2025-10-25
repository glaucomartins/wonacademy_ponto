<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Requer o init.php para segurança e acesso às variáveis
require __DIR__ . '/includes/init.php';

header('Content-Type: application/json');

$instanceName = $_GET['instance'] ?? '';
if (empty($instanceName)) {
    echo json_encode(['error' => 'Nome da instância não fornecido.']);
    exit;
}

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    // Endpoint para buscar o status da conexão e um novo QR Code
    $apiUrl = rtrim($_ENV['EVOLUTION_API'], '/') . '/instance/connect/' . $instanceName;
    $apiKey = $_ENV['EVOLUTION_API_KEY'];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey],
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API retornou o código de status {$httpCode}");
    }

    $data = json_decode($response);
    
    // Retorna o novo QR Code em base64
    echo json_encode(['qrCodeBase64' => $data->base64 ?? null]);
    exit;

} catch (Exception $e) {
    error_log("Erro ao atualizar QR Code: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Não foi possível obter um novo QR Code.']);
    exit;
}