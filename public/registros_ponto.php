<?php 
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 

// Segurança: Apenas usuários com permissão 3 podem acessar esta página.
if ($currentUser['permissao'] != 3) {
    header('Location: index.php?error=access_denied');
    exit;
}

// Aqui virá a lógica para buscar os registros de ponto do usuário logado.
// $meus_pontos = ...;

?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Meus Registros de Ponto</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Histórico de Pontos</h6>
        </div>
        <div class="card-body">
            <p>Em breve, você poderá ver seus registros de ponto aqui.</p>
            
            <?php
            /*
            // Exemplo de como a tabela pode ser:
            <div class="table-responsive">
                <table class="table table-bordered" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Entrada</th>
                            <th>Saída Almoço</th>
                            <th>Retorno Almoço</th>
                            <th>Saída</th>
                            <th>Total Horas</th>
                        </tr>
                    </thead>
                    <tbody>
                        // Loop através dos $meus_pontos
                    </tbody>
                </table>
            </div>
            */
            ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>