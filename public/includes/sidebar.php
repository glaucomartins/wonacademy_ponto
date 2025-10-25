<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index">
        <div class="sidebar-brand-icon">
            <img src="https://wonecosystem.com.br/wp-content/uploads/2025/03/5.png" style="width: 100%;">
        </div>
        <div class="sidebar-brand-text mx-3"><sup>V1.0</sup></div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item">
        <a class="nav-link" href="index">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Menu
    </div>

    <?php // Menu para Admins e Super Admins ?>
    <?php if ($currentUser['permissao'] <= 2): ?>

        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                aria-expanded="true" aria-controls="collapseTwo">
                <i class="fab fa-fw fa-whatsapp"></i>
                <span>Whatsapp</span>
            </a>
            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Dispositivos: <?php echo count($instancias); ?> / <?php echo $limiteInstancias; ?></h6>
                    <a class="collapse-item" href="instancias">Meus Dispositivos</a>
                    <a class="collapse-item" href="instancia_nova">Novo Dispositivo</a>
                    <?php if ($currentUser['permissao'] == 1): ?>
                        <a class="collapse-item" href="instancias_admin">Listar Dispositivos</a>
                    <?php endif; ?>
                </div>
            </div>
        </li>

        <div class="sidebar-heading">
            Administração
        </div>

        <li class="nav-item">
            <a class="nav-link" href="usuarios">
                <i class="fas fa-fw fa-users-cog"></i>
                <span>Colaboradores</span>
            </a>
            <a class="nav-link" href="usuarios_ativos">
                <i class="fas fa-fw fa-user-check"></i>
                <span>Usuários Ativos</span>
            </a>
            <?php if ($currentUser['permissao'] == 1): ?>
            <a class="nav-link" href="usuarios_aprovacao">
                <i class="fas fa-fw fa-user-check"></i>
                <span>Aprovações</span>
                <?php
                    // Opcional: Adicionar um contador de pendentes
                    $stmt_pending = $pdo->query("SELECT COUNT(*) FROM tbl_usuarios WHERE status = 0");
                    $pending_count = $stmt_pending->fetchColumn();
                    if ($pending_count > 0) {
                        echo "<span class=\"badge badge-danger ml-2\">{$pending_count}</span>";
                    }
                ?>
            </a>
            <?php endif; ?>
            <a class="nav-link" href="relatorios">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Relatório de Ponto</span>
            </a>
            <?php if ($currentUser['permissao'] == 1): ?>
            <a class="nav-link" href="relatorios_b">
                <i class="fas fa-fw fa-users"></i>
                <span>Relatório de Usuários</span>
            </a>
            <?php endif; ?>
        </li>

    <?php endif; ?>

    <?php // Menu para Usuários ?>
    <?php if ($currentUser['permissao'] == 3): ?>
        <li class="nav-item">
            <a class="nav-link" href="registros_ponto.php">
                <i class="fas fa-fw fa-clock"></i>
                <span>Meus Pontos</span>
            </a>
        </li>
    <?php endif; ?>
    
    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>