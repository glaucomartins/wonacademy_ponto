<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Segurança: Se o usuário não acessou a página "esqueci a senha" primeiro, redireciona.
if (!isset($_SESSION['reset_whatsapp'])) {
    header('Location: login');
    exit;
}

$error = '';
$whatsapp_formatado = $_SESSION['reset_whatsapp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_digitado = $_POST['token'] ?? '';

    if (empty($token_digitado) || !is_numeric($token_digitado)) {
        $error = "Por favor, insira um código válido.";
    } else {
        try {
            // Carrega as variáveis de ambiente e conecta ao banco
            $dotenv = Dotenv::createImmutable(__DIR__);
            $dotenv->load();
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']);
            $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);

            // Busca o usuário pelo WhatsApp para verificar o token
            $stmt = $pdo->prepare("SELECT id_user, dois_fa, dois_fa_expira FROM tbl_usuarios WHERE whatsapp = ?");
            $stmt->execute([$_SESSION['reset_whatsapp']]);
            $user = $stmt->fetch();

            // Valida o token e sua data de expiração
            if ($user && password_verify($token_digitado, $user['dois_fa']) && new DateTime() < new DateTime($user['dois_fa_expira'])) {
                // Token válido! Autoriza a troca de senha na próxima etapa.
                $_SESSION['can_reset_password_for_user'] = $user['id_user'];
                unset($_SESSION['reset_whatsapp']); // Limpa a sessão antiga para segurança
                header('Location: nova_senha');
                exit;
            } else {
                // Token inválido ou expirado
                $error = "Código inválido ou expirado. Tente novamente.";
            }

        } catch (Exception $e) {
            error_log("Erro ao verificar token: " . $e->getMessage());
            $error = "Ocorreu um erro no sistema. Tente mais tarde.";
        }
    }
}

// --- Cores Customizáveis ---
$bgColorStart = '#00793b';
$bgColorEnd = '#00c67f';
$containerColor = '#212529';
$primaryBtnColor = '#00ff8c';
$textColorPrimary = '#ffffff';
$inputBgColor = '#ffffff';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Verificar Código - Painel</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <style>
        body {
            background-image: linear-gradient(to right, <?php echo $bgColorStart; ?>, <?php echo $bgColorEnd; ?>);
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0; font-family: 'Nunito', sans-serif;
        }
        .form-container {
            background-color: <?php echo $containerColor; ?>;
            border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 50px 40px; width: 100%; max-width: 450px;
        }
        .form-header { margin-bottom: 30px; color: <?php echo $textColorPrimary; ?>; text-align: center; }
        .form-header p { font-size: 0.9rem; color: #ccc; }
        .form-header strong { color: <?php echo $primaryBtnColor; ?>; }
        .form-group { position: relative; margin-bottom: 20px; }
        .form-control {
            width: 100%; padding: 12px 20px; border: none; border-radius: 50px;
            box-sizing: border-box; font-size: 1rem; background-color: <?php echo $inputBgColor; ?>;
        }
        .token-input {
            text-align: center;
            font-size: 1.2rem;
            letter-spacing: 0.5em; /* Espaçamento entre os caracteres */
            padding-right: 20px; /* Ajuste para centralizar com o letter-spacing */
        }
        .btn-primary {
            background-color: <?php echo $primaryBtnColor; ?>; color: #212529; font-weight: bold;
            border: none; padding: 12px 20px; border-radius: 50px; cursor: pointer;
            font-size: 1rem; width: 100%; display: block; transition: background-color 0.3s ease;
        }
        .alert-danger { background-color: #ffdddd; color: #d8000c; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px; }
        .login-link { text-align: center; margin-top: 25px; }
        .small { color: <?php echo $textColorPrimary; ?>; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1 class="h4">Verifique seu WhatsApp</h1>
            <p>
                Enviamos um código de 6 dígitos para o número <br><strong><?php echo htmlspecialchars($whatsapp_formatado); ?></strong>.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="verificar_token" method="POST">
            <div class="form-group">
                <input type="text" name="token" id="token" required maxlength="6"
                       class="form-control token-input"
                       placeholder="______">
            </div>
            <button type="submit" class="btn-primary">
                Verificar e Continuar
            </button>
        </form>
        <div class="login-link">
            <a href="login" class="small">Voltar para o Login</a>
        </div>
    </div>
    <script src="vendor/jquery/jquery.min.js"></script>
</body>
</html>