<?php 
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 

// --- VERIFICAÇÃO DE PERMISSÃO ---
// Apenas Super Admins (nível 1) podem acessar esta página.
if ($currentUser['permissao'] != 1) {
    header('Location: index?error=access_denied');
    exit;
}

// --- LÓGICA DE BUSCA ---
// Busca TODAS as instâncias e junta com a tabela de usuários para pegar o nome do dono
$stmt = $pdo->prepare("
    SELECT i.*, u.nome as nome_usuario 
    FROM tbl_instancia i
    JOIN tbl_usuarios u ON i.id_usuario = u.id_user
    ORDER BY i.data_criacao DESC
");
$stmt->execute();
$todasInstancias = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Todas as Instâncias (Admin)</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Visão Geral de Todos os Dispositivos</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome da Instância</th>
                            <th>Proprietário</th>
                            <th>Status</th>
                            <th>Perfil Conectado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todasInstancias as $instancia): ?>
                            <tr>
                                <td><?php echo $instancia['id_instancia']; ?></td>
                                <td><?php echo htmlspecialchars($instancia['instanceName']); ?></td>
                                <td><strong><?php echo htmlspecialchars($instancia['nome_usuario']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo ($instancia['connectionStatus'] === 'open') ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($instancia['connectionStatus'] ?? 'desconectado'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($instancia['connectionStatus'] === 'open'): ?>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($instancia['profilePicUrl'] ?? 'img/undraw_profile.svg'); ?>" class="rounded-circle mr-2" width="30" height="30">
                                            <span><?php echo htmlspecialchars($instancia['profileName']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                            data-toggle="modal" data-target="#deleteModal" 
                                            data-instancename="<?php echo htmlspecialchars($instancia['instanceName']); ?>">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/includes/footer.php'; 
// Reutilizamos o mesmo modal de exclusão da página 'instancias.php'
// A lógica dele já é genérica o suficiente para funcionar aqui também.
?>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja excluir a instância <strong id="modal-instance-name"></strong>?</p>
                <p class="text-danger">Esta ação é irreversível e removerá a instância permanentemente.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                <form action="instancia_excluir_process" method="POST">
                    <input type="hidden" name="instanceName" id="form-instance-name" value="">
                    <button type="submit" class="btn btn-danger">Sim, Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#deleteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var instanceName = button.data('instancename');
        var modal = $(this);
        modal.find('#modal-instance-name').text(instanceName);
        modal.find('#form-instance-name').val(instanceName);
    });
});
</script>