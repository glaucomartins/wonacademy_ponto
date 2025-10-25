<?php 
require_once __DIR__ . '/includes/header.php'; 

// Segurança: Apenas Super Admins (nível 1) podem aceder a esta página.
if ($currentUser['permissao'] != 1) {
    header('Location: index.php?error=access_denied');
    exit;
}

require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 

// Busca todos os utilizadores com status pendente (0)
$stmt = $pdo->prepare("SELECT * FROM tbl_usuarios WHERE status = 0 ORDER BY data_criacao DESC");
$stmt->execute();
$usuariosPendente = $stmt->fetchAll();
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Aprovação de Novos Usuários</h1>
    <p class="mb-4">Abaixo estão os utilizadores que se registaram e aguardam a sua aprovação para aceder ao sistema.</p>

    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Utilizadores Pendentes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>WhatsApp</th>
                            <th>Data de Registo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuariosPendente)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Não há utilizadores pendentes de aprovação.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuariosPendente as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['whatsapp']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['data_criacao'])); ?></td>
                                    <td>
                                        <form action="usuario_aprovar_process.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                                            <button type="submit" name="action" value="aprovar" class="btn btn-success btn-sm" title="Aprovar Usuário">
                                                <i class="fas fa-check"></i> Aprovar
                                            </button>
                                            <button type="submit" name="action" value="rejeitar" class="btn btn-danger btn-sm" title="Rejeitar Usuário">
                                                <i class="fas fa-times"></i> Rejeitar
                                            </button>
                                        </form>
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