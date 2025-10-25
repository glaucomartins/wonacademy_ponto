<?php 
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 

if ($currentUser['permissao'] > 2) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Acesso Negado. Você não tem permissão para criar novas instâncias.</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 1. Conta quantas instâncias o usuário já possui.
$stmt_current = $pdo->prepare("SELECT COUNT(*) FROM tbl_instancia WHERE id_usuario = ?");
$stmt_current->execute([$currentUser['id_user']]);
$instanciasAtuais = $stmt_current->fetchColumn();

// 2. Busca o limite de instâncias do plano do usuário.
$stmt_plan = $pdo->prepare("SELECT limite_instancias FROM tbl_instancia_plan WHERE id_usuario = ?");
$stmt_plan->execute([$currentUser['id_user']]);
$limiteInstancias = $stmt_plan->fetchColumn();

// Se não encontrar um plano (caso de usuários antigos), assume o padrão 1.
if ($limiteInstancias === false) { $limiteInstancias = 1; }

// 3. Verifica se o limite foi atingido.
$limiteAtingido = ($instanciasAtuais >= $limiteInstancias);

// --- FIM DA VERIFICAÇÃO ---


$qrCodeBase64 = $_SESSION['qr_code_base64'] ?? null;
$instanceName = $_SESSION['instance_name'] ?? null;
$apiError = $_SESSION['api_error'] ?? null;

unset($_SESSION['qr_code_base64'], $_SESSION['instance_name'], $_SESSION['api_error']);
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Novo Dispositivo</h1>

    <div class="row">
        <div class="col-lg-6">
             <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Criar Novo Dispositivo</h6>
                </div>
                <div class="card-body">
                    <?php if ($apiError): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($apiError); ?></div>
                    <?php endif; ?>

                    <?php if ($limiteAtingido): ?>
                        <div class="text-center">
                            <h5 class="font-weight-bold text-danger">Limite de Dispositivos Atingido</h5>
                            <p class="mb-4">
                                Seu plano atual permite a conexão de <strong><?php echo $limiteInstancias; ?> dispositivo(s)</strong>.
                                Para conectar mais aparelhos, você precisa fazer um upgrade no seu plano.
                            </p>
                            <a href="https://wonacademy.com.br" target="_blank" class="btn btn-success btn-lg">
                                <i class="fas fa-arrow-up"></i> Fazer Upgrade do Plano
                            </a>
                        </div>
                    <?php else: ?>
                        <form action="instancia_nova_process.php" method="POST">
                            <div class="form-group">
                                <label for="instanceName">Nome do Dispositivo</label>
                                <input type="text" class="form-control" name="instanceName" placeholder="Ex: minha_empresa" required>
                                <small class="form-text text-muted">Será adicionado um código único ao final do nome.</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Gerar QR Code</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($qrCodeBase64): ?>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Conecte seu WhatsApp</h6>
                    <small id="timer-feedback" class="text-muted"></small>
                </div>
                <div class="card-body text-center" id="qr-code-container" data-instancename="<?php echo htmlspecialchars($instanceName); ?>">
                    <p>Escaneie o QR Code. A verificação da conexão é automática.</p>
                    <img id="qr-code-image" src="<?php echo $qrCodeBase64; ?>" alt="QR Code" class="img-fluid">
                    <div id="loading-spinner" class="mt-3" style="display: none;">
                        <div class="spinner-border text-primary" role="status"><span class="sr-only"></span></div>
                        <p>Verificando conexão...</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const qrContainer = document.getElementById('qr-code-container');
    if (!qrContainer) return;

    const instanceName = qrContainer.dataset.instancename;
    const timerFeedback = document.getElementById('timer-feedback');
    const loadingSpinner = document.getElementById('loading-spinner');
    const qrCodeImage = document.getElementById('qr-code-image');
    const checkInterval = 30000; // Verificar a cada 10 segundos
    let timer;

    function checkStatus() {
        loadingSpinner.style.display = 'block';
        timerFeedback.textContent = 'Verificando...';

        fetch(`instancia_status_ajax?instance=${instanceName}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'connected') {
                    // SUCESSO! Redireciona para a lista de instâncias
                    timerFeedback.textContent = "Conectado!";
                    window.location.href = 'instancias';
                } else if (data.status === 'connecting') {
                    // Ainda conectando, atualiza o QR Code
                    qrCodeImage.src = data.qrCodeBase64;
                    startTimer(); // Reinicia o timer
                }
            })
            .catch(error => console.error('Erro na verificação:', error))
            .finally(() => loadingSpinner.style.display = 'none');
    }

    function startTimer() {
        let secondsLeft = checkInterval / 1000;
        timerFeedback.textContent = `Nova verificação em ${secondsLeft}s...`;
        
        const countdown = setInterval(() => {
            secondsLeft--;
            timerFeedback.textContent = `Nova verificação em ${secondsLeft}s...`;
            if (secondsLeft <= 0) clearInterval(countdown);
        }, 1000);

        clearTimeout(timer);
        timer = setTimeout(checkStatus, checkInterval);
    }
    
    startTimer(); // Inicia o ciclo de verificação
});
</script>