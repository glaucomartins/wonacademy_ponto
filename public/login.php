<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Lógica para mensagens de ERRO
$error = $_GET['error'] ?? '';
$errorMessages = [
    'invalid_credentials' => 'Login ou senha inválidos.',
    'system_error' => 'Ocorreu um erro no sistema. Tente novamente.',
];
$errorMessage = $errorMessages[$error] ?? null;

// --- NOVA LÓGICA PARA MENSAGENS DE SUCESSO ---
$success = $_GET['success'] ?? '';
$successMessages = [
    'registered' => 'Cadastro realizado com sucesso! Aguarde a aprovação de um administrador para fazer o login.',
    // Você pode adicionar outras mensagens de sucesso aqui no futuro
];
$successMessage = $successMessages[$success] ?? null;


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
    <title>Login - Painel Administrativo</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <style>
        body {
            background-image: linear-gradient(to right, <?php echo $bgColorStart; ?>, <?php echo $bgColorEnd; ?>);
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0; font-family: 'Nunito', sans-serif;
        }
        .login-container {
            background-color: <?php echo $containerColor; ?>; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); width: 100%; max-width: 800px;
            display: flex; overflow: hidden;
        }
        .login-logo-column { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; }
        .login-logo { max-width: 150px; }
        .login-form-column { flex: 1; padding: 50px 40px; background-color: <?php echo $containerColor; ?>; }
        .login-header { margin-bottom: 30px; color: <?php echo $textColorPrimary; ?>; }
        .form-group, .password-container { position: relative; margin-bottom: 20px; }
        .form-control {
            width: 100%; padding: 12px 20px; border: none; border-radius: 50px;
            box-sizing: border-box; font-size: 1rem; background-color: <?php echo $inputBgColor; ?>;
        }
        .toggle-password { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6c757d; }
        .btn-primary {
            background-color: <?php echo $primaryBtnColor; ?>; color: #212529; font-weight: bold;
            border: none; padding: 12px 20px; border-radius: 50px; cursor: pointer;
            font-size: 1rem; width: 100%; display: block; transition: background-color 0.3s ease;
        }
        .login-links { text-align: center; margin-top: 25px; }
        .small { color: <?php echo $textColorPrimary; ?>; text-decoration: none; font-size: 0.9rem; display: block; margin-top: 10px; cursor: pointer; }
        .small:hover { text-decoration: underline; }
        
        /* Estilos para os Alertas */
        .alert { padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px; }
        .alert-danger { background-color: #ffdddd; color: #d8000c; }
        .alert-success { background-color: #ddffdd; color: #004d00; } /* NOVO ESTILO */
        
        .modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; }
        .modal.hidden { display: none; }
        .modal-backdrop { position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.6); }
        .modal-content {
            background-color: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,.5);
            padding: 30px; z-index: 10; width: 100%; max-width: 400px; color: #333;
        }
        .modal-header { text-align: center; margin-bottom: 20px; }
        .modal-body .form-control { border: 1px solid #ccc; }
        .modal-footer { display: flex; gap: 10px; margin-top: 25px; }
        .btn { width: 100%; border-radius: 50px; padding: 10px; font-size: 1rem; border: none; cursor: pointer; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-send { background-color: #007bff; color: white; }

        @media (max-width: 768px) {
            .login-container { flex-direction: column; max-width: 400px; }
            .login-logo-column { padding-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo-column">
            <img src="https://wonecosystem.com.br/wp-content/uploads/2025/03/5.png" alt="Logo Won" class="login-logo">
        </div>
        <div class="login-form-column">
            <div class="login-header">
                <h1 class="h4">Bem-vindo de Volta!</h1>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <form action="login_process" method="POST">
                <div class="form-group">
                    <input type="text" class="form-control" id="login" name="login" required placeholder="Digite seu Email ou WhatsApp...">
                </div>
                <div class="form-group">
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="senha" required placeholder="Senha">
                        <span class="toggle-password"><i class="fa fa-eye" aria-hidden="true"></i></span>
                    </div>
                </div>
                <button type="submit" class="btn-primary">Entrar</button>
            </form>
            <div class="login-links">
                <a class="small" id="forgot-password-link">Esqueceu a senha?</a>
                <a class="small" href="login_novo">Criar uma conta!</a>
            </div>
        </div>
    </div>

    <div id="reset-modal" class="modal hidden">
        <div id="modal-backdrop" class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="h5 font-weight-bold">Recuperar Senha</h2>
                <p class="small text-muted mt-2">Insira seu WhatsApp para receber o código de verificação.</p>
            </div>
            <div class="modal-body">
                <form action="esqueci_senha_process" method="POST">
                    <div class="form-group">
                        <input type="text" name="whatsapp" id="reset-whatsapp" required class="form-control" placeholder="(99) 99999-9999">
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="cancel-reset" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">Enviar Código</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Script de visualizar/ocultar senha (sem alterações)
            $('.toggle-password').on('click', function() {
                var passwordField = $('#password');
                var passwordFieldType = passwordField.attr('type');
                if (passwordFieldType == 'password') {
                    passwordField.attr('type', 'text');
                    $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // --- NOVO SCRIPT PARA O MODAL ---
            const resetModal = $('#reset-modal');
            
            // Abrir o modal
            $('#forgot-password-link').on('click', function(e) {
                e.preventDefault();
                resetModal.removeClass('hidden');
            });

            // Fechar o modal
            $('#cancel-reset, #modal-backdrop').on('click', function() {
                resetModal.addClass('hidden');
            });
        });
    </script>