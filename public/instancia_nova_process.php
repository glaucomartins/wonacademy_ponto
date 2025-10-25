<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// O init.php já carrega o .env, conecta ao banco ($pdo) e identifica o usuário ($currentUser).
require __DIR__ . '/includes/init.php';

if ($currentUser['permissao'] > 2) {
    $_SESSION['api_error'] = "Você não tem permissão para criar novas instâncias.";
    header('Location: instancia_nova');
    exit;
}

$instanceName = $_POST['instanceName'] ?? '';
if (empty($instanceName)) {
    $_SESSION['api_error'] = "O nome da instância é obrigatório.";
    header('Location: instancia_nova');
    exit;
}

try {
    // --- VERIFICAÇÃO DE LIMITE DO PLANO ---
    $stmt_current = $pdo->prepare("SELECT COUNT(*) FROM tbl_instancia WHERE id_usuario = ?");
    $stmt_current->execute([$currentUser['id_user']]);
    $instanciasAtuais = $stmt_current->fetchColumn();

    $stmt_plan = $pdo->prepare("SELECT limite_instancias FROM tbl_instancia_plan WHERE id_usuario = ?");
    $stmt_plan->execute([$currentUser['id_user']]);
    $limiteInstancias = $stmt_plan->fetchColumn();
    
    if ($limiteInstancias === false) { $limiteInstancias = 1; }

    if ($instanciasAtuais >= $limiteInstancias) {
        $_SESSION['api_error'] = "Você atingiu o limite de {$limiteInstancias} instância(s) do seu plano.";
        header('Location: instancia_nova');
        exit;
    }

    // Garante que o nome da instância seja único e limpo
    $uniqueInstanceName = preg_replace('/[^a-zA-Z0-9]/', '', $instanceName) . '-' . random_int(100000, 999999);
    
    $apiUrl = rtrim($_ENV['EVOLUTION_API'], '/') . '/instance/create';
    $apiKey = $_ENV['EVOLUTION_API_KEY'];
    $endponintponto = $_ENV['ENDPOINT_PONTO'];

    $payload = json_encode([
        'instanceName' => $uniqueInstanceName,
        'qrcode' => true,
        'integration' => 'WHATSAPP-BAILEYS',
        'webhook' => [
            'url' => $endponintponto,
            'byEvents' => false,
            'base64' => true,
            'events' => [
                'MESSAGES_UPSERT'
            ]
        ]
    ]);
    

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response);

    if ($httpCode !== 201 && $httpCode !== 200) {
        $errorMessage = "Erro ao criar instância. ";
        if ($httpCode === 401) {
            $errorMessage .= "A API Key Global está incorreta.";
        } else {
            $errorMessage .= $data->response->message ?? 'Verifique se o nome já existe ou tente novamente.';
        }
        $_SESSION['api_error'] = $errorMessage;
        header('Location: instancia_nova');
        exit;
    }

    $instanceData = $data->instance;
    $hash = $data->hash;
    
    $sql = "INSERT INTO tbl_instancia (id_usuario, instanceName, instanceId, integration, status, hash) 
            VALUES (:id_usuario, :instanceName, :instanceId, :integration, :status, :hash)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $currentUser['id_user'],
        ':instanceName' => $instanceData->instanceName,
        ':instanceId' => $instanceData->instanceId,
        ':integration' => $instanceData->integration,
        ':status' => $instanceData->status,
        ':hash' => $hash
    ]);

    $_SESSION['qr_code_base64'] = $data->qrcode->base64;
    $_SESSION['instance_name'] = $instanceData->instanceName;

    header('Location: instancia_nova');
    exit;

} catch (Exception $e) {
    error_log("Erro ao criar instância: " . $e->getMessage());
    $_SESSION['api_error'] = "Ocorreu um erro crítico no sistema. Tente novamente.";
    header('Location: instancia_nova');
    exit;
}