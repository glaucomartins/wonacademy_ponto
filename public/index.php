<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<?php require_once __DIR__ . '/includes/topbar.php'; ?>

<div class="container-fluid">

    <?php if (isset($_GET['error']) && $_GET['error'] == 'access_denied'): ?>
        <div class="alert alert-danger">
            Você não tem permissão para acessar a página solicitada. Se você acredita que isso é um erro, por favor, entre em contato com o administrador do sistema.
        </div>
    <?php endif; ?>

    <?php if ($currentUser['permissao'] == 1): // PAINEL SUPER ADMIN ?>
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard do Super Admin</h1>
        </div>
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Usuários</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">150</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total de Instâncias</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">25</div>
                            </div>
                            <div class="col-auto"><i class="fab fa-whatsapp fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Aprovações Pendentes</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">3</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-user-check fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($currentUser['permissao'] == 2): // PAINEL ADMINISTRADOR ?>
        <?php
            // Lógica para buscar os dados do dashboard do administrador
            $admin_id = $currentUser['id_user'];

            // 1. Total de colaboradores
            $stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_usuarios WHERE id_administrador = ?");
            $stmt_total->execute([$admin_id]);
            $total_colaboradores = $stmt_total->fetchColumn();

            // 2. Colaboradores em férias (em_ferias = 0)
            $stmt_ferias = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_usuarios WHERE id_administrador = ? AND em_ferias = 0");
            $stmt_ferias->execute([$admin_id]);
            $total_ferias = $stmt_ferias->fetchColumn();

            // 3. Ocorrências no mês
            $mes_inicio = date('Y-m-01');
            $mes_fim = date('Y-m-t');
            $stmt_ocorrencias = $pdo->prepare("SELECT COUNT(p.id_ponto) 
                FROM tbl_ponto p
                JOIN tbl_usuarios u ON p.id_user = u.id_user
                WHERE u.id_administrador = ? 
                AND p.ocorrencias IS NOT NULL 
                AND p.ocorrencias != ''
                AND p.data BETWEEN ? AND ?
            ");
            $stmt_ocorrencias->execute([$admin_id, $mes_inicio, $mes_fim]);
            $total_ocorrencias_mes = $stmt_ocorrencias->fetchColumn();

            // 4. Colaboradores em atividade hoje
            $stmt_trabalhando = $pdo->prepare("SELECT u.nome, p.hora_entrada
                FROM tbl_ponto p
                JOIN tbl_usuarios u ON p.id_user = u.id_user
                WHERE u.id_administrador = ?
                AND p.data = CURDATE()
                AND p.hora_entrada IS NOT NULL
                AND p.hora_saida IS NULL
                ORDER BY p.hora_entrada ASC
            ");
            $stmt_trabalhando->execute([$admin_id]);
            $colaboradores_trabalhando = $stmt_trabalhando->fetchAll();

            // 5. Colaboradores em almoço
            $stmt_almoco = $pdo->prepare("SELECT u.nome, p.hora_saida_almoco
                FROM tbl_ponto p
                JOIN tbl_usuarios u ON p.id_user = u.id_user
                WHERE u.id_administrador = ?
                AND p.data = CURDATE()
                AND p.hora_saida_almoco IS NOT NULL
                AND p.hora_retorno_almoco IS NULL
                ORDER BY p.hora_saida_almoco ASC
            ");
            $stmt_almoco->execute([$admin_id]);
            $colaboradores_almoco = $stmt_almoco->fetchAll();
        ?>
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard do Administrador</h1>
        </div>
        <div class="row">
            <!-- Card Meus Colaboradores -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Meus Colaboradores</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_colaboradores; ?></div>
                            </div>
                            <div class="col-auto"><i class="fas fa-users-cog fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Colaboradores em Férias -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Colaboradores em Férias</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_ferias; ?></div>
                            </div>
                            <div class="col-auto"><i class="fas fa-plane-departure fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Ocorrências (Mês) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Ocorrências (Mês)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_ocorrencias_mes; ?></div>
                            </div>
                            <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Colaboradores Ativos -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Colaboradores Ativos (Agora)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($colaboradores_trabalhando); ?></div>
                            </div>
                            <div class="col-auto"><i class="fas fa-user-clock fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabelas de Status Atual -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Colaboradores em Atividade</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Entrada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($colaboradores_trabalhando)):
                                        ?>
                                        <tr><td colspan="2" class="text-center">Nenhum colaborador em atividade no momento.</td></tr>
                                    <?php else:
                                        ?>
                                        <?php foreach ($colaboradores_trabalhando as $colab):
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($colab['nome']); ?></td>
                                                <td><?php echo htmlspecialchars($colab['hora_entrada']); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        ?>
                                    <?php endif;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Colaboradores em Almoço</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Saída para Almoço</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($colaboradores_almoco)):
                                        ?>
                                        <tr><td colspan="2" class="text-center">Nenhum colaborador em almoço no momento.</td></tr>
                                    <?php else:
                                        ?>
                                        <?php foreach ($colaboradores_almoco as $colab):
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($colab['nome']); ?></td>
                                                <td><?php echo htmlspecialchars($colab['hora_saida_almoco']); ?></td>
                                            </tr>
                                        <?php endforeach;
                                        ?>
                                    <?php endif;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: // PAINEL USUÁRIO (COLABORADOR) ?>
        <?php
            // Busca os registros de ponto do usuário nos últimos 7 dias
            $stmt_registros = $pdo->prepare(
                "                SELECT data, hora_entrada, hora_saida_almoco, hora_retorno_almoco, hora_saida, total_horas\n                FROM tbl_ponto\n                WHERE id_user = ? AND data >= CURDATE() - INTERVAL 7 DAY\n                ORDER BY data DESC\n            ");
            $stmt_registros->execute([$currentUser['id_user']]);
            $registros_semana = $stmt_registros->fetchAll();

            // Busca o último registro de ponto do usuário
            $stmt_ultimo = $pdo->prepare("
                SELECT data, hora_entrada, hora_saida_almoco, hora_retorno_almoco, hora_saida
                FROM tbl_ponto
                WHERE id_user = ?
                ORDER BY data DESC, id_ponto DESC
                LIMIT 1
            ");
            $stmt_ultimo->execute([$currentUser['id_user']]);
            $ultimo_registro = $stmt_ultimo->fetch();

            // Busca o WhatsApp do admin para o botão de registro
            $whatsapp_url = '#'; // URL Padrão
            if (!empty($currentUser['id_administrador'])) {
                $stmt_admin_whatsapp = $pdo->prepare("SELECT ownerJid FROM tbl_instancia WHERE id_usuario = ? LIMIT 1");
                $stmt_admin_whatsapp->execute([$currentUser['id_administrador']]);
                $admin_instance = $stmt_admin_whatsapp->fetch();
                if ($admin_instance && !empty($admin_instance['ownerJid'])) {
                    $admin_phone = explode('@', $admin_instance['ownerJid'])[0];
                    $whatsapp_url = "https://wa.me/" . $admin_phone;
                }
            }
        ?>
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Bem-vindo(a), <?php echo htmlspecialchars($currentUser['nome']); ?>!</h1>
        </div>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Registro de Ponto</h6>
                    </div>
                    <div class="card-body text-center">
                        <h2 id="current-time" class="font-weight-bold text-gray-800">--:--:--</h2>
                        <p id="current-date"><?php echo date('d/m/Y'); ?></p>
                        <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn btn-success btn-icon-split btn-lg">
                            <span class="icon text-white-50"><i class="fab fa-whatsapp"></i></span>
                            <span class="text">Registrar Ponto</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Último Registro</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($ultimo_registro): ?>
                            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($ultimo_registro['data'])); ?></p>
                            <p><strong>Entrada:</strong> <?php echo htmlspecialchars($ultimo_registro['hora_entrada'] ?? '--:--'); ?></p>
                            <p><strong>Saída Almoço:</strong> <?php echo htmlspecialchars($ultimo_registro['hora_saida_almoco'] ?? '--:--'); ?></p>
                            <p><strong>Retorno Almoço:</strong> <?php echo htmlspecialchars($ultimo_registro['hora_retorno_almoco'] ?? '--:--'); ?></p>
                            <p><strong>Saída:</strong> <?php echo htmlspecialchars($ultimo_registro['hora_saida'] ?? '--:--'); ?></p>
                        <?php else: ?>
                            <p class="text-center">Nenhum registro de ponto encontrado.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Registros da Semana -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Meus Registros (Últimos 7 Dias)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
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
                            <?php if (empty($registros_semana)):
                                ?>
                                <tr><td colspan="6" class="text-center">Nenhum registro encontrado nos últimos 7 dias.</td></tr>
                            <?php else: ?>
                                <?php foreach ($registros_semana as $reg):
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($reg['data'])); ?></td>
                                        <td><?php echo htmlspecialchars($reg['hora_entrada'] ?? '--:--'); ?></td>
                                        <td><?php echo htmlspecialchars($reg['hora_saida_almoco'] ?? '--:--'); ?></td>
                                        <td><?php echo htmlspecialchars($reg['hora_retorno_almoco'] ?? '--:--'); ?></td>
                                        <td><?php echo htmlspecialchars($reg['hora_saida'] ?? '--:--'); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($reg['total_horas'] ?? 0, 2, ',', '.')); ?></td>
                                    </tr>
                                <?php endforeach;
                                ?>
                            <?php endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            function updateTime() {
                const timeEl = document.getElementById('current-time');
                if (timeEl) {
                    const now = new Date();
                    timeEl.textContent = now.toLocaleTimeString('pt-BR');
                }
            }
            setInterval(updateTime, 1000);
            updateTime();
        </script>

    <?php endif; ?>

</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>