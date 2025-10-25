<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Inclui o arquivo com a nova função que criamos
require __DIR__ . '/includes/functions.php';

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']);
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);

    // Limpa o número de WhatsApp recebido do formulário
    $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');

    if (!empty($whatsapp)) {
        // MUDANÇA: Agora também buscamos o 'nome' para personalizar a mensagem
        $stmt = $pdo->prepare("SELECT id_user, nome FROM tbl_usuarios WHERE whatsapp = ? AND status = 1");
        $stmt->execute([$whatsapp]);
        $user = $stmt->fetch();

        if ($user) {
            // Gera o token e a data de expiração
            $token = random_int(100000, 999999);
            $token_hash = password_hash((string)$token, PASSWORD_DEFAULT);
            $expira_em = date('Y-m-d H:i:s', time() + (15 * 60)); // Expira em 15 minutos

            // Salva o token hash no banco de dados
            $updateStmt = $pdo->prepare("UPDATE tbl_usuarios SET dois_fa = ?, dois_fa_expira = ? WHERE id_user = ?");
            $updateStmt->execute([$token_hash, $expira_em, $user['id_user']]);

            // MUDANÇA: Chama a nova função da API Won para enviar o token
            sendWonPasswordResetToken($user['nome'], $whatsapp, (string)$token);
            
            // Guarda na sessão qual WhatsApp está em processo de recuperação
            $_SESSION['reset_whatsapp'] = $whatsapp;
        }
    }
    
    // Redireciona para a página de verificação de qualquer forma para não revelar se um usuário existe ou não.
    header('Location: verificar_token');
    exit;

} catch (Exception $e) {
    error_log("Erro no reset de senha: " . $e->getMessage());
    header('Location: login?error=system_error');
    exit;
}