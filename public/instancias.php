<?php
require_once __DIR__ . '/includes/header.php';

// Segurança: Apenas Super Admins (1) e Admins (2) podem acessar.
if ($currentUser['permissao'] == 3) {
    header('Location: index.php?error=access_denied');
    exit;
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Meus Dispositivos</h1>

        <div class="text-right d-none d-sm-block">
            <span class="text-xs font-weight-bold text-primary text-uppercase mb-1">Uso do Plano</span>
            <div class="h5 mb-0 font-weight-bold text-gray-800">
                <?php echo count($instancias); ?> / <?php echo $limiteInstancias; ?>
            </div>
        </div>

        <a href="instancia_nova" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Conectar Dispositivo</span>
        </a>
    </div>

    <?php if (isset($_GET['delete_status'])): ?>
        <div class="alert alert-<?php echo $_GET['delete_status'] == 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($instancias)): ?>
            <div class="col-12 text-center card shadow py-5">
                <p>Você ainda não conectou nenhum dispositivo.</p>
                <a href="instancia_nova" class="btn btn-primary">Conectar meu primeiro Dispositivo</a>
            </div>
        <?php else: ?>
            <?php foreach ($instancias as $instancia): ?>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($instancia['instanceName'] ?? ''); ?></h6>
                            <i class="fas fa-cog text-gray-400"></i>
                        </div>
                        <div class="card-body">
                            <?php if ($instancia['connectionStatus'] === 'open'): ?>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($instancia['profilePicUrl'] ?? 'img/undraw_profile.svg'); ?>" class="rounded-circle mr-3" width="60" height="60">
                                    <div>
                                        <strong><?php echo htmlspecialchars($instancia['profileName'] ?? 'Nome não disponível'); ?></strong>
                                        <p class="mb-0 text-muted"><?php echo str_replace('@s.whatsapp.net', '', $instancia['ownerJid'] ?? ''); ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-around text-center">
                                    <div><div class="font-weight-bold"><?php echo $instancia['count_contacts'] ?? 0; ?></div><div class="small text-muted">Contatos</div></div>
                                    <div><div class="font-weight-bold"><?php echo $instancia['count_chats'] ?? 0; ?></div><div class="small text-muted">Chats</div></div>
                                </div>
                            <?php else: ?>
                                <div class="text-center">
                                    <p>A instância não está conectada.</p>
                                    <a href="instancia_conectar?instance=<?php echo htmlspecialchars($instancia['instanceName'] ?? ''); ?>" class="btn btn-success"><i class="fas fa-qrcode"></i> Conectar</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <span class="badge badge-<?php echo ($instancia['connectionStatus'] === 'open') ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($instancia['connectionStatus'] ?? 'desconectado'); ?>
                            </span>
                            <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                    data-toggle="modal" data-target="#deleteModal" 
                                    data-instancename="<?php echo htmlspecialchars($instancia['instanceName'] ?? ''); ?>">
                                Excluir
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
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
    // Quando o modal de exclusão for aberto
    $('#deleteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Botão que acionou o modal
        var instanceName = button.data('instancename'); // Extrai o nome da instância do data-* attribute
        
        var modal = $(this);
        modal.find('#modal-instance-name').text(instanceName); // Atualiza o nome no texto do modal
        modal.find('#form-instance-name').val(instanceName);  // Atualiza o nome no campo hidden do formulário
    });
});
</script>