<?php 
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php'; 

$idColaborador = $_GET['colaborador'] ?? '';
$dataInicial = $_GET['data_inicial'] ?? '';
$dataFinal = $_GET['data_final'] ?? '';

$pontos = [];
$params = [];

// Base da consulta SQL com o auto-join para o administrador
// AJUSTE: Trocado "p.id_colaborador" por "p.id_user" para corresponder ao banco de dados atualizado.
$sql = "
    SELECT
        p.data,
        p.hora_entrada,
        p.hora_saida_almoco,
        p.hora_retorno_almoco,
        p.hora_saida,
        p.total_horas,
        p.ocorrencias,
        tu.id_user,
        tu.nome,
        tu.matricula,
        ta.nome AS nome_administrador
    FROM tbl_ponto AS p
    JOIN tbl_usuarios AS tu ON p.id_user = tu.id_user
    LEFT JOIN tbl_usuarios AS ta ON tu.id_administrador = ta.id_user
    WHERE 1=1
";

// Se o usuário logado for um administrador (permissao 2), filtre para ver apenas seus colaboradores.
if ($currentUser['permissao'] == 2) {
    $sql .= " AND tu.id_administrador = ?";
    $params[] = $currentUser['id_user'];
}

if (!empty($idColaborador)) {
    // AJUSTE: Trocado "p.id_colaborador" por "p.id_user" no filtro.
    $sql .= " AND p.id_user = ?";
    $params[] = $idColaborador;
}

if (!empty($dataInicial) && !empty($dataFinal)) {
    $sql .= " AND p.data BETWEEN ? AND ?";
    $params[] = $dataInicial;
    $params[] = $dataFinal;
}

$sql .= " ORDER BY p.data DESC, tu.nome ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pontos = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Erro ao buscar dados do relatório: " . $e->getMessage();
}

// Busca apenas usuários com permissão de "Usuário" para o filtro.
// Se for admin, busca apenas seus colaboradores. Super admin vê todos.
if ($currentUser['permissao'] == 2) {
    $stmtColaboradores = $pdo->prepare("SELECT id_user, nome FROM tbl_usuarios WHERE permissao = 3 AND id_administrador = ? ORDER BY nome ASC");
    $stmtColaboradores->execute([$currentUser['id_user']]);
    $colaboradores = $stmtColaboradores->fetchAll();
} else {
    $colaboradores = $pdo->query("SELECT id_user, nome FROM tbl_usuarios WHERE permissao = 3 ORDER BY nome ASC")->fetchAll();
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Relatórios de Ponto</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar Relatório</h6>
        </div>
        <div class="card-body">
            <form action="relatorios.php" method="GET">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="colaborador">Colaborador:</label>
                        <select class="form-control" id="colaborador" name="colaborador">
                            <option value="">Todos os Colaboradores</option>
                            <?php foreach ($colaboradores as $colaborador): ?>
                                <option 
                                    value="<?php echo htmlspecialchars($colaborador['id_user']); ?>"
                                    <?php echo ($idColaborador == $colaborador['id_user']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($colaborador['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="data_inicial">Data Inicial:</label>
                        <input type="date" class="form-control" id="data_inicial" name="data_inicial" value="<?php echo htmlspecialchars($dataInicial); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="data_final">Data Final:</label>
                        <input type="date" class="form-control" id="data_final" name="data_final" value="<?php echo htmlspecialchars($dataFinal); ?>">
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Registros de Ponto</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Colaborador</th>
                            <th>Admin. Responsável</th>
                            <th>Entrada</th>
                            <th>Almoço Saída</th>
                            <th>Almoço Retorno</th>
                            <th>Saída</th>
                            <th>Total Horas</th>
                            <th>Ocorrências</th>
                            <th>Ações</th> </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pontos) > 0): ?>
                            <?php foreach ($pontos as $ponto): 
                                //var_dump($ponto);
                                ?>
    <tr>
        <td><?php echo date('d/m/Y', strtotime($ponto['data'])); ?></td>
        <td><?php echo htmlspecialchars($ponto['nome'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($ponto['nome_administrador'] ?? 'N/A'); ?></td>
        
        <td><?php echo htmlspecialchars($ponto['hora_entrada'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($ponto['hora_saida_almoco'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($ponto['hora_retorno_almoco'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($ponto['hora_saida'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars(number_format($ponto['total_horas'] ?? 0, 2, ',', '.')); ?></td>
        <td><?php echo htmlspecialchars($ponto['ocorrencias'] ?? ''); ?></td>

        <td>
            <a href="relatorio_ponto_individual?id=<?php echo $ponto['id_user']; ?>&mes=<?php echo date('Y-m', strtotime($ponto['data'])); ?>" 
               class="btn btn-info btn-sm" 
               title="Gerar Espelho de Ponto"
               target="_blank">
                <i class="fas fa-file-invoice"></i>
            </a>
        </td>
    </tr>
<?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Nenhum registro encontrado para os filtros selecionados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>