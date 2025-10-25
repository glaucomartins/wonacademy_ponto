<?php 
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Meu Perfil</h1>

    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informações da Conta</h6>
                </div>
                <div class="card-body">
                    <form action="perfil_process.php" method="POST">
                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($currentUser['nome'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="whatsapp">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" value="<?php echo htmlspecialchars($currentUser['whatsapp'] ?? ''); ?>" readonly>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="matricula">Matrícula</label>
                                    <input type="text" class="form-control" id="matricula" value="<?php echo htmlspecialchars($currentUser['matricula'] ?? 'Não informado'); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cpf">CPF</label>
                                    <input type="text" class="form-control" id="cpf" value="<?php echo htmlspecialchars($currentUser['cpf'] ?? 'Não informado'); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="setor">Setor</label>
                                    <input type="text" class="form-control" id="setor" value="<?php echo htmlspecialchars($currentUser['setor'] ?? 'Não informado'); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cargo">Cargo</label>
                                    <input type="text" class="form-control" id="cargo" value="<?php echo htmlspecialchars($currentUser['cargo'] ?? 'Não informado'); ?>" readonly>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Alterar Senha</h6>
                </div>
                <div class="card-body">
                        <div class="form-group">
                            <label for="senha_atual">Senha Atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                            <small class="form-text text-muted">Necessária apenas se for alterar a senha.</small>
                        </div>
                        <div class="form-group">
                            <label for="nova_senha">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                            <small class="form-text text-muted">Mínimo de 6 caracteres. Deixe em branco para não alterar.</small>
                        </div>
                        <div class="form-group">
                            <label for="confirma_senha">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirma_senha" name="confirma_senha">
                        </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </form> </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>