<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

require __DIR__ . '/includes/init.php';

header('Content-Type: application/json');

$instanceName = $_GET['instance'] ?? '';
if (empty($instanceName)) {
    echo json_encode(['error' => 'Nome da instância não fornecido.']); exit;
}

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $apiUrlBase = rtrim($_ENV['EVOLUTION_API'], '/');
    $apiKey = $_ENV['EVOLUTION_API_KEY'];

    // 1. VERIFICAR O STATUS DA CONEXÃO
    $statusUrl = $apiUrlBase . '/instance/connectionState/' . $instanceName;
    $chStatus = curl_init($statusUrl);
    curl_setopt_array($chStatus, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey]]);
    $statusResponse = curl_exec($chStatus);
    curl_close($chStatus);
    $statusData = json_decode($statusResponse);
    $state = $statusData->instance->state ?? 'closed';

    // 2. AGIR DE ACORDO COM O STATUS
    if ($state === 'open') {
        // CONECTADO: Buscar dados completos e atualizar o banco
        $stmt = $pdo->prepare("SELECT hash FROM tbl_instancia WHERE instanceName = ? AND id_usuario = ?");
        $stmt->execute([$instanceName, $currentUser['id_user']]);
        $instancia = $stmt->fetch();
        $instanceApiKey = $instancia['hash']; // API Key específica da instância (hash)

        $fetchUrl = $apiUrlBase . '/instance/fetchInstances?instanceName=' . $instanceName;
        $chFetch = curl_init($fetchUrl);
        curl_setopt_array($chFetch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['apikey: ' . $instanceApiKey]]);
        $fetchResponse = curl_exec($chFetch);
        curl_close($chFetch);
        $fetchData = json_decode($fetchResponse)[0] ?? null;

        if ($fetchData) {
            // Atualizar o banco de dados com as novas informações
            $updateSql = "UPDATE tbl_instancia SET connectionStatus = ?, ownerJid = ?, profileName = ?, profilePicUrl = ?, count_messages = ?, count_contacts = ?, count_chats = ? WHERE instanceName = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                $fetchData->connectionStatus, $fetchData->ownerJid, $fetchData->profileName, $fetchData->profilePicUrl,
                $fetchData->_count->Message ?? 0, $fetchData->_count->Contact ?? 0, $fetchData->_count->Chat ?? 0,
                $instanceName
            ]);
        }
        echo json_encode(['status' => 'connected']);
        
    } else {
        // CONECTANDO: Buscar novo QR Code
        $connectUrl = $apiUrlBase . '/instance/connect/' . $instanceName;
        $chConnect = curl_init($connectUrl);
        curl_setopt_array($chConnect, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey]]);
        $connectResponse = curl_exec($chConnect);
        curl_close($chConnect);
        $connectData = json_decode($connectResponse);
        
        echo json_encode(['status' => 'connecting', 'qrCodeBase64' => $connectData->base64 ?? null]);
    }
    exit;

} catch (Exception $e) {
    error_log("Erro no AJAX de status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor.']);
    exit;
}