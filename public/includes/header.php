<?php 
// INCLUI O SCRIPT DE SEGURANÇA. SÓ DEIXA ACESSAR A PÁGINA QUEM ESTIVER LOGADO.
require_once __DIR__ . '/init.php'; 

// --- Cores Customizáveis para o Dashboard ---
$primaryColor = '#00c67f'; // Verde principal para botões e links
$gradientStart = '#00793b';
$gradientEnd = '#00c67f';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Won CRM</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">

    <style>
        .bg-gradient-primary {
            background-color: <?php echo $gradientStart; ?>;
            background-image: linear-gradient(180deg, <?php echo $gradientStart; ?> 10%, <?php echo $gradientEnd; ?> 100%);
            background-size: cover;
        }
        .btn-primary {
            background-color: <?php echo $primaryColor; ?>;
            border-color: <?php echo $primaryColor; ?>;
        }
        .btn-primary:hover {
            background-color: #009c65; /* Um tom um pouco mais escuro para o hover */
            border-color: #009c65;
        }
        .text-primary {
            color: <?php echo $primaryColor; ?> !important;
        }
        a {
            color: <?php echo $primaryColor; ?>;
        }
        .sidebar-dark .nav-item.active .nav-link {
            background-color: rgba(0,0,0,0.2);
        }
        .sidebar-dark .nav-item .nav-link:active, .sidebar-dark .nav-item .nav-link:focus, .sidebar-dark .nav-item .nav-link:hover {
            background-color: rgba(0,0,0,0.15);
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">