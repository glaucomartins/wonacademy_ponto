<?php 
require_once __DIR__ . '/includes/header.php'; 

// Segurança: Apenas Super Admins (nível 1) podem acessar esta página.
if ($currentUser['permissao'] != 1) {
    header('Location: index.php?error=access_denied');
    exit;
}

require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 

// Busca a lista de usuários usando a função já existente em functions.php
$usuarios = getListaUsuarios($pdo);

// Prepara os dados para o gráfico
$permissoesContador = [
    'Super Admin' => 0,
    'Admin' => 0,
    'Usuário' => 0,
    'Desconhecido' => 0,
];

foreach ($usuarios as $usuario) {
    // getRoleName() vem do arquivo functions.php
    $roleName = getRoleName($usuario['permissao']); 
    if (isset($permissoesContador[$roleName])) {
        $permissoesContador[$roleName]++;
    }
}

// Converte os dados do PHP para JSON para serem usados no JavaScript
$chartLabels = json_encode(array_keys($permissoesContador));
$chartData = json_encode(array_values($permissoesContador));

?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Relatório de Usuários</h1>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Usuários por Permissão</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="userRoleChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle" style="color: #4e73df;"></i> Super Admin
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle" style="color: #1cc88a;"></i> Admin
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle" style="color: #36b9cc;"></i> Usuário
                        </span>
                         <span class="mr-2">
                            <i class="fas fa-circle" style="color: #f6c23e;"></i> Desconhecido
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Todos os Usuários</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="datatablesSimple" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>WhatsApp</th>
                                    <th>Permissão</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome'] ?? 'Não informado'); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email'] ?? 'Não informado'); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['whatsapp'] ?? 'Não informado'); ?></td>
                                    <td><?php echo getRoleName($usuario['permissao']); ?></td>
                                    <td>
                                        <?php if ($usuario['status'] == 1): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="vendor/chart.js/Chart.min.js"></script>

<script>
// Configuração do Gráfico de Rosca (Doughnut Chart)
var ctx = document.getElementById("userRoleChart");
var myPieChart = new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: <?php echo $chartLabels; ?>,
    datasets: [{
      data: <?php echo $chartData; ?>,
      backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
      hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
      hoverBorderColor: "rgba(234, 236, 244, 1)",
    }],
  },
  options: {
    maintainAspectRatio: false,
    tooltips: {
      backgroundColor: "rgb(255,255,255)",
      bodyFontColor: "#858796",
      borderColor: '#dddfeb',
      borderWidth: 1,
      xPadding: 15,
      yPadding: 15,
      displayColors: false,
      caretPadding: 10,
    },
    legend: {
      display: false
    },
    cutoutPercentage: 80,
  },
});
</script>