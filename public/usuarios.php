<?php 
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 

// --- VERIFICAÇÃO DE PERMISSÃO ATUALIZADA ---
// Agora, Super Admins (1) e Admins (2) podem acessar. Usuários normais (3) são bloqueados.
if ($currentUser['permissao'] > 2) {
    header('Location: index?error=access_denied');
    exit;
}

// --- LÓGICA DE BUSCA DINÂMICA DE USUÁRIOS ---
$usuarios = [];
if ($currentUser['permissao'] == 1) {
    // Se for SUPER ADMIN: Busca todos, exceto outros Super Admins.
    $stmt = $pdo->prepare("SELECT * FROM tbl_usuarios WHERE permissao != 1 ORDER BY id_user ASC");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
} elseif ($currentUser['permissao'] == 2) {
    // Se for ADMIN: Busca apenas os usuários que ele cadastrou.
    $stmt = $pdo->prepare("SELECT * FROM tbl_usuarios WHERE id_administrador = ? ORDER BY id_user ASC");
    $stmt->execute([$currentUser['id_user']]);
    $usuarios = $stmt->fetchAll();
}
?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestão de Usuários</h1>
        <div>
            <a href="relatorios" class="btn btn-info btn-icon-split mr-2">
                <span class="icon text-white-50"><i class="fas fa-file-alt"></i></span>
                <span class="text">Relatórios de Ponto</span>
            </a>
            <button class="btn btn-primary btn-icon-split" data-toggle="modal" data-target="#createUserModal">
                <span class="icon text-white-50"><i class="fas fa-user-plus"></i></span>
                <span class="text">Adicionar Novo Usuário</span>
            </button>
        </div>
    </div>
    
    <?php if (isset($_GET['edit_status'])): ?>
        <div class="alert alert-<?php echo $_GET['edit_status'] == 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['create_status'])): ?>
        <div class="alert alert-<?php echo $_GET['create_status'] == 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['delete_status'])): ?>
        <div class="alert alert-<?php echo $_GET['delete_status'] == 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($_GET['message']); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo ($currentUser['permissao'] == 1) ? 'Todos os Usuários Gerenciáveis' : 'Meus Usuários Cadastrados'; ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email / WhatsApp</th>
                            <th>Nível</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Nenhum usuário encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id_user']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nome'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($usuario['email'] ?? 'N/A'); ?><br>
                                        <small><?php echo htmlspecialchars($usuario['whatsapp']); ?></small>
                                    </td>
                                    <td><?php echo getRoleName($usuario['permissao']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $usuario['status'] == 1 ? 'success' : 'secondary'; ?>">
                                            <?php echo $usuario['status'] == 1 ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                                data-toggle="modal" data-target="#editUserModal" 
                                                data-userid="<?php echo $usuario['id_user']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn" data-toggle="modal" data-target="#deleteUserModal" data-userid="<?php echo $usuario['id_user']; ?>" data-username="<?php echo htmlspecialchars($usuario['nome']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="usuario_editar_process" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuário</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="userId" id="edit-userId">
                    <div class="form-group">
                        <label for="edit-nome">Nome</label>
                        <input type="text" class="form-control" id="edit-nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-email">Email</label>
                        <input type="email" class="form-control" id="edit-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-whatsapp">WhatsApp</label>
                        <input type="text" class="form-control" id="edit-whatsapp" name="whatsapp" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-cpf">CPF</label>
                        <input type="text" class="form-control" id="edit-cpf" name="cpf" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-permissao">Nível de Permissão</label>
                        <select class="form-control" id="edit-permissao" name="permissao">
                            <?php if ($currentUser['permissao'] == 1): ?>
                                <option value="1">Super Admin</option>
                                <option value="2">Admin</option>
                            <?php endif; ?>
                            <option value="3">Colaborador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-status">Status</label>
                        <select class="form-control" id="edit-status" name="status">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit-notificacoes">Receber Notificações</label>
                        <select class="form-control" id="edit-notificacoes" name="notificacoes">
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit-nova_senha">Nova Senha</label>
                        <input type="password" class="form-control" id="edit-nova_senha" name="nova_senha">
                        <small class="form-text text-muted">Deixe em branco para não alterar a senha.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="usuario_novo_process" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Novo Usuário</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>WhatsApp</label>
                        <input type="text" class="form-control" name="whatsapp" id="whatsapp" required>
                    </div>
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" class="form-control" name="cpf" id="cpf" required>
                    </div>
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" class="form-control" name="senha" required>
                        <small>min 6 caractéres</small>
                    </div>
                    <div class="form-group">
                        <label>Nível de Permissão</label>
                        <select class="form-control" name="permissao">
                        <?php if ($currentUser['permissao'] == 1): ?>
                            <option value="2">Admin</option>
                        <?php endif; ?>
                            <option value="3" selected>Colaborador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="1" selected>Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Receber Notificações</label>
                        <select class="form-control" name="notificacoes">
                            <option value="1" selected>Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Usuário</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja excluir o usuário <strong id="modal-delete-username"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                <form action="usuario_excluir_process" method="POST">
                    <input type="hidden" name="userId" id="delete-userId" value="">
                    <button type="submit" class="btn btn-danger">Sim, Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
$(document).ready(function() {
    $('#whatsapp').mask('(00) 00000-0000');
    $('#cpf').mask('000.000.000-00', {reverse: true});
});
</script>

<script>
$(document).ready(function() {
    $('.edit-btn').on('click', function() {
        var userId = $(this).data('userid');
        var modal = $('#editUserModal');
        
        modal.find('form')[0].reset();
        modal.find('.modal-body').append('<p id="loading-text">Carregando...</p>');
        
        $.ajax({
            url: 'usuario_get_details_ajax', // Assumindo que este é o endpoint correto
            type: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(user) {
                modal.find('#edit-userId').val(user.id_user);
                modal.find('#edit-nome').val(user.nome);
                modal.find('#edit-email').val(user.email);
                modal.find('#edit-whatsapp').val(user.whatsapp);
                modal.find('#edit-permissao').val(user.permissao);
                modal.find('#edit-status').val(user.status);
                modal.find('#edit-notificacoes').val(user.notificacoes);
                modal.find('#edit-cpf').val(user.cpf);

                // --- SOLUÇÃO ---
                // Reaplica as máscaras nos campos que acabaram de ser preenchidos
                $('#edit-whatsapp').mask('(00) 00000-0000');
                $('#edit-cpf').mask('000.000.000-00', {reverse: true});
            },
            error: function() {
                alert('Não foi possível carregar os dados do usuário.');
                modal.modal('hide');
            },
            complete: function() {
                $('#loading-text').remove();
            }
        });
    });

    $('#deleteUserModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var userId = button.data('userid');
        var userName = button.data('username');
        
        var modal = $(this);
        modal.find('#modal-delete-username').text(userName);
        modal.find('#delete-userId').val(userId);
    });
});
</script>