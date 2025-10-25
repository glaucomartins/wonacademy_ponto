<?php
session_start();

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
    <title>Cadastrar Empresa</title>

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
            padding: 50px 40px; width: 100%; max-width: 480px;
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
        .alert-box { padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px; }
        .alert-danger { background-color: #ffdddd; color: #d8000c; }
        .login-link { text-align: center; margin-top: 25px; }
        .small { color: <?php echo $textColorPrimary; ?>; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1 class="h4">Crie sua Conta Empresarial</h1>
            <p>Seu acesso será liberado após aprovação.</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert-box alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="login_novo_process" method="POST">
            <div class="form-group">
                <input type="text" class="form-control" name="nome" required placeholder="Razão Social ou Nome Completo">
            </div>
            <div class="form-group">
                <input type="text" class="form-control" id="cpf_cnpj" name="cpf" required placeholder="CPF ou CNPJ">
            </div>
            <div class="form-group">
                <input type="email" class="form-control" name="email" required placeholder="Seu melhor e-mail">
            </div>
            <div class="form-group">
                <input type="text" class="form-control" id="whatsapp" name="whatsapp" required placeholder="WhatsApp (DDD) 99999-9999">
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" class="form-control" id="senha" name="senha" required placeholder="Crie uma senha">
                    <span class="toggle-password" data-target="senha"><i class="fa fa-eye"></i></span>
                </div>
            </div>
             <div class="form-group">
                <div class="password-container">
                    <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" required placeholder="Confirme sua senha">
                    <span class="toggle-password" data-target="confirma_senha"><i class="fa fa-eye"></i></span>
                </div>
            </div>
            <button type="submit" class="btn-primary">Criar Conta</button>
        </form>
        <div class="login-link">
            <a class="small" href="login">Já tem uma conta? Faça Login</a>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // MUDANÇA 3: Ativação da máscara no campo de WhatsApp
            // Este código aplica a máscara de forma inteligente, adaptando-se para números
            // de 8 ou 9 dígitos (celulares antigos e novos).
            var options =  {
                onKeyPress: function(cep, e, field, options) {
                    var masks = ['(00) 0000-00009', '(00) 00000-0000'];
                    var mask = (cep.length > 14) ? masks[1] : masks[0];
                    $('#whatsapp').mask(mask, options);
                }
            };
            $('#whatsapp').mask('(00) 0000-00009', options);

            // Aplica a máscara de CPF ou CNPJ dependendo do número de dígitos
            var cpfCnpjMaskBehavior = function (val) {
                return val.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';
            },
            cpfCnpjOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(cpfCnpjMaskBehavior.apply({}, arguments), options);
                }
            };
            $('#cpf_cnpj').mask(cpfCnpjMaskBehavior, cpfCnpjOptions);


            // Script de visualizar/ocultar senha (sem alterações)
            $('.toggle-password').on('click', function() {
                var targetId = $(this).data('target');
                var field = $('#' + targetId);
                var fieldType = field.attr('type');
                if (fieldType === 'password') {
                    field.attr('type', 'text');
                    $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    field.attr('type', 'password');
                    $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });
    </script>
</body>
</html>