<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Segurança: Se o usuário não passou pela verificação do token, redireciona.
if (!isset($_SESSION['can_reset_password_for_user'])) {
    header('Location: login');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (empty($nova_senha) || $nova_senha !== $confirma_senha) {
        $error = "As senhas não correspondem ou estão vazias.";
    } elseif (strlen($nova_senha) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        try {
            $dotenv = Dotenv::createImmutable(__DIR__);
            $dotenv->load();
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']);
            $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);

            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $id_user = $_SESSION['can_reset_password_for_user'];
            
            // Atualiza a senha e invalida o token de recuperação
            $stmt = $pdo->prepare("UPDATE tbl_usuarios SET senha = ?, dois_fa = NULL, dois_fa_expira = NULL WHERE id_user = ?");
            $stmt->execute([$nova_senha_hash, $id_user]);
            
            session_destroy(); // Destrói a sessão para forçar um novo login
            $success = "Senha redefinida com sucesso! Você será redirecionado para o login em 5 segundos.";
            header("refresh:5;url=login");

        } catch (Exception $e) {
            error_log("Erro ao redefinir senha: " . $e->getMessage());
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
    <title>Criar Nova Senha - Painel</title>

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
        .form-group, .password-container { position: relative; margin-bottom: 20px; }
        .form-control {
            width: 100%; padding: 12px 20px; border: none; border-radius: 50px;
            box-sizing: border-box; font-size: 1rem; background-color: <?php echo $inputBgColor; ?>;
        }
        .toggle-password {
            position: absolute; right: 20px; top: 50%;
            transform: translateY(-50%); cursor: pointer; color: #6c757d;
        }
        .btn-primary {
            background-color: <?php echo $primaryBtnColor; ?>; color: #212529; font-weight: bold;
            border: none; padding: 12px 20px; border-radius: 50px; cursor: pointer;
            font-size: 1rem; width: 100%; display: block; transition: background-color 0.3s ease;
        }
        .alert-danger { background-color: #ffdddd; color: #d8000c; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px; }
        .alert-success { background-color: #ddffdd; color: #004d00; padding: 15px; border-radius: 5px; text-align: center; }
        .login-link { text-align: center; margin-top: 25px; }
        .small { color: <?php echo $textColorPrimary; ?>; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1 class="h4">Crie sua Nova Senha</h1>
            <p>Escolha uma senha forte e segura.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php else: ?>
            <form action="nova_senha.php" method="POST">
                <div class="form-group">
                    <div class="password-container">
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required placeholder="Nova Senha">
                        <span class="toggle-password" data-target="nova_senha">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <div class="password-container">
                        <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" required placeholder="Confirme a Nova Senha">
                        <span class="toggle-password" data-target="confirma_senha">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn-primary">Salvar Nova Senha</button>
            </form>
            <div class="login-link">
                <a class="small" href="login">Voltar para o Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.toggle-password').on('click', function() {
                var targetId = $(this).data('target');
                var passwordField = $('#' + targetId);
                var passwordFieldType = passwordField.attr('type');
                
                if (passwordFieldType === 'password') {
                    passwordField.attr('type', 'text');
                    $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });
    </script>
</body>
</html>