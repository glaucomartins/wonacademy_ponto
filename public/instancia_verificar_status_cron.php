<?php
date_default_timezone_set('America/Sao_Paulo');
$rootDir = __DIR__;

require $rootDir . '/vendor/autoload.php';
use Dotenv\Dotenv;

// --- INICIALIZAÇÃO ---
try {
    $dotenv = Dotenv::createImmutable($rootDir);
    $dotenv->load();
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']);
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $apiUrlBase = rtrim($_ENV['EVOLUTION_API'], '/');
    $apiKey = $_ENV['EVOLUTION_API_KEY'];
} catch (Exception $e) {
    logMessage("ERRO CRÍTICO: Não foi possível inicializar o script. Mensagem: " . $e->getMessage());
    exit(1);
}

// --- FUNÇÕES AUXILIARES ---
function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

function callEvolutionApi($endpoint, $apiKey, $method = 'GET') {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey],
        CURLOPT_TIMEOUT => 20
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
}

// =================================================================
// INÍCIO DO PROCESSO PRINCIPAL
// =================================================================

logMessage("Iniciando verificação e limpeza de instâncias...");

try {
    // 1. BUSCA E ATUALIZAÇÃO DE STATUS
    $stmt = $pdo->query("SELECT instanceName, connectionStatus FROM tbl_instancia");
    $instancias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($instancias)) {
        logMessage("Nenhuma instância encontrada. Encerrando.");
        exit(0);
    }

    logMessage("Verificando status de " . count($instancias) . " instâncias.");
    foreach ($instancias as $instancia) {
        $instanceName = $instancia['instanceName'];
        $currentStatus = $instancia['connectionStatus'];

        $statusUrl = $apiUrlBase . '/instance/connectionState/' . $instanceName;
        $data = callEvolutionApi($statusUrl, $apiKey);
        $newState = $data->instance->state ?? 'closed';

        // Lógica para registrar quando a instância foi desconectada
        if ($newState !== 'open' && $currentStatus === 'open') {
            // A instância ACABOU de cair. Registra a data.
            $updateStmt = $pdo->prepare("UPDATE tbl_instancia SET connectionStatus = ?, last_disconnected_at = NOW() WHERE instanceName = ?");
            $updateStmt->execute([$newState, $instanceName]);
            logMessage("Instância {$instanceName} ficou offline. Status atualizado para '{$newState}'.");
        } elseif ($newState === 'open' && $currentStatus !== 'open') {
            // A instância VOLTOU a ficar online. Limpa a data.
            $updateStmt = $pdo->prepare("UPDATE tbl_instancia SET connectionStatus = ?, last_disconnected_at = NULL WHERE instanceName = ?");
            $updateStmt->execute([$newState, $instanceName]);
            logMessage("Instância {$instanceName} voltou a ficar online. Status atualizado.");
        }
    }

    // 2. PROCESSO DE EXCLUSÃO AUTOMÁTICA
    logMessage("Verificando por instâncias inativas para exclusão...");
    $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt_delete = $pdo->prepare("SELECT instanceName FROM tbl_instancia WHERE last_disconnected_at IS NOT NULL AND last_disconnected_at < ?");
    $stmt_delete->execute([$sevenDaysAgo]);
    $instanciasParaExcluir = $stmt_delete->fetchAll(PDO::FETCH_ASSOC);

    if (empty($instanciasParaExcluir)) {
        logMessage("Nenhuma instância inativa encontrada para excluir.");
    } else {
        logMessage(count($instanciasParaExcluir) . " instância(s) serão excluídas por inatividade.");
        foreach ($instanciasParaExcluir as $instancia) {
            $instanceName = $instancia['instanceName'];
            logMessage("Excluindo instância {$instanceName}...");

            // Desconecta e deleta na API da Evolution
            callEvolutionApi($apiUrlBase . '/instance/logout/' . $instanceName, $apiKey, 'DELETE');
            callEvolutionApi($apiUrlBase . '/instance/delete/' . $instanceName, $apiKey, 'DELETE');

            // Remove do banco de dados local
            $deleteStmt = $pdo->prepare("DELETE FROM tbl_instancia WHERE instanceName = ?");
            $deleteStmt->execute([$instanceName]);

            logMessage("Instância {$instanceName} excluída com sucesso.");
        }
    }

    logMessage("Processo concluído.");
    exit(0);

} catch (Exception $e) {
    logMessage("ERRO INESPERADO: " . $e->getMessage());
    exit(1);
}