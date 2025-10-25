<?php
session_start();

require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

function redirectToLogin(string $error) {
    // Aponta para login.php para exibir os erros corretamente
    header('Location: login?error=' . $error);
    exit;
}

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']);
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- MUDANÇAS PRINCIPAIS AQUI ---

    // 1. Pega o campo 'login', que pode ser email ou whatsapp, e a senha.
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($login) || empty($senha)) {
        redirectToLogin('invalid_credentials');
    }

    // 2. Prepara a busca para email OU whatsapp.
    // O campo 'whatsapp' no banco deve armazenar apenas os números.
    $cleaned_whatsapp = preg_replace('/\D/', '', $login);

    $stmt = $pdo->prepare("SELECT * FROM tbl_usuarios WHERE email = ? OR whatsapp = ?");
    
    // 3. Executa a busca com os dois valores.
    $stmt->execute([$login, $cleaned_whatsapp]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- FIM DAS MUDANÇAS ---

    // Verifica se o usuário existe, a senha está correta e o status está ativo
    if (!$user || !password_verify($senha, $user['senha']) || $user['status'] != 1) {
        redirectToLogin('invalid_credentials');
    }
    
    // O resto do processo permanece o mesmo...
    $new_token = hash('sha256', random_bytes(32));

    $updateStmt = $pdo->prepare("UPDATE tbl_usuarios SET token_login = ?, sessao = NOW() WHERE id_user = ?");
    $updateStmt->execute([$new_token, $user['id_user']]);
    
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['login_token'] = $new_token;

    // Redireciona para o painel principal
    header('Location: index');
    exit;

} catch (Exception $e) {
    // Em caso de erro de banco de dados ou outro erro crítico
    // Opcional: logar o erro real para depuração
    // error_log($e->getMessage());
    redirectToLogin('system_error');
}