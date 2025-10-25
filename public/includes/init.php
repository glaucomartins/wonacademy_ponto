<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega o autoload do Composer e o .env
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load(); 

// Conexão com o Banco de Dados
try {
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']);
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Inclui o arquivo de funções
require_once __DIR__ . '/functions.php';

// =======================================================
// ## INÍCIO DA VERIFICAÇÃO DE SEGURANÇA ##
// =======================================================

$user_id_session = $_SESSION['user_id'] ?? null;
$login_token_session = $_SESSION['login_token'] ?? null;
$currentUser = null;

if ($user_id_session && $login_token_session) {
    $currentUser = getAuthenticatedUser($pdo, $user_id_session, $login_token_session);
}

if ($currentUser === null) {
    session_destroy();
    header('Location: login');
    exit;
}
// =======================================================
// ## FIM DA VERIFICAÇÃO DE SEGURANÇA ##
// =======================================================


// =================================================================
// ## INÍCIO DA VERIFICAÇÃO DE STATUS DAS INSTÂNCIAS ##
// =================================================================

    // A verificação de instância só se aplica a admins e super admins
    if ($currentUser['permissao'] <= 2) {
        $current_page = basename($_SERVER['PHP_SELF']);

        $exempt_pages = [
            'instancias.php',
            'instancia_nova.php',
            'instancia_nova_process.php',
            'instancia_conectar.php',
            'instancia_excluir_process.php',
            'logout.php',
            'instancia_status_ajax.php'
        ];

        if (!in_array($current_page, $exempt_pages)) {
            // 1. Verifica se o usuário possui alguma instância
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM tbl_instancia WHERE id_usuario = ?");
            $stmt_count->execute([$currentUser['id_user']]);
            $totalInstancias = $stmt_count->fetchColumn();

            if ($totalInstancias == 0) {
                // Se não tiver NENHUMA instância, redireciona para a página de criação
                header('Location: instancia_nova');
                exit;
            } else {
                // 2. Se possui instâncias, verifica se ALGUMA está desconectada
                $stmt_disconnected = $pdo->prepare("SELECT COUNT(*) FROM tbl_instancia WHERE id_usuario = ? AND (connectionStatus != 'open' OR connectionStatus IS NULL)");
                $stmt_disconnected->execute([$currentUser['id_user']]);
                $totalDesconectadas = $stmt_disconnected->fetchColumn();

                if ($totalDesconectadas > 0) {
                    // Se tiver PELO MENOS UMA desconectada, redireciona para a lista
                    header('Location: instancias');
                    exit;
                }
            }
        }
    }

// =================================================================
// ## FIM DA VERIFICAÇÃO DE STATUS DAS INSTÂNCIAS ##
// =================================================================

// --- LÓGICA ADICIONADA PARA BUSCAR O LIMITE DO PLANO ---

// Busca instâncias com base na permissão do usuário
if ($currentUser['permissao'] == 1) {
    // Super admin (1) vê todas as instâncias
    $stmt = $pdo->query("SELECT * FROM tbl_instancia ORDER BY data_criacao DESC");
    $instancias = $stmt->fetchAll();
} elseif ($currentUser['permissao'] == 2) {
    // Admin (2) vê apenas as suas instâncias
    $stmt = $pdo->prepare("SELECT * FROM tbl_instancia WHERE id_usuario = ? ORDER BY data_criacao DESC");
    $stmt->execute([$currentUser['id_user']]);
    $instancias = $stmt->fetchAll();
} else {
    // Usuários (3) não têm instâncias associadas diretamente nesta visualização
    $instancias = [];
}

// 2. A nova variável `$limiteInstancias` é definida aqui.
// Ela busca o limite específico do usuário na tabela de planos.
$stmt_plan = $pdo->prepare("SELECT limite_instancias FROM tbl_instancia_plan WHERE id_usuario = ?");
$stmt_plan->execute([$currentUser['id_user']]);
$limiteInstancias = $stmt_plan->fetchColumn();

// Caso o usuário (por algum motivo) não tenha um plano, definimos um padrão para evitar erros.
if ($limiteInstancias === false) {
    $limiteInstancias = 1;
}