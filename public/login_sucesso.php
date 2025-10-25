<?php
// --- Cores Customizáveis (mesmas da tela de cadastro para consistência) ---
$bgColorStart = '#00793b';
$bgColorEnd = '#00c67f';
$containerColor = '#212529';
$primaryBtnColor = '#00ff8c';
$textColorPrimary = '#ffffff';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cadastro Realizado!</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <style>
        body {
            background-image: linear-gradient(to right, <?php echo $bgColorStart; ?>, <?php echo $bgColorEnd; ?>);
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0; font-family: 'Nunito', sans-serif;
        }
        .success-container {
            background-color: <?php echo $containerColor; ?>;
            border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 50px 40px; width: 100%; max-width: 480px;
            text-align: center;
            color: <?php echo $textColorPrimary; ?>;
        }
        .success-icon {
            font-size: 4rem;
            color: <?php echo $primaryBtnColor; ?>;
            margin-bottom: 20px;
        }
        .success-container h1 {
            margin-bottom: 15px;
        }
        .success-container p {
            color: #ccc;
            font-size: 1rem;
            margin-bottom: 30px;
        }
        .btn-login {
            background-color: <?php echo $primaryBtnColor; ?>; color: #212529; font-weight: bold;
            border: none; padding: 12px 20px; border-radius: 50px; cursor: pointer;
            font-size: 1rem; text-decoration: none; display: inline-block;
            transition: background-color 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="h4">Cadastro Realizado com Sucesso!</h1>
        <p>Sua conta foi criada e agora aguarda a aprovação de um administrador.<br>Você será notificado assim que seu acesso for liberado.</p>
        <a href="login" class="btn-login">Voltar para o Login</a>
    </div>
</body>
</html>