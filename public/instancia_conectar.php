<?php 
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 

// Pega o nome da instância a ser conectada a partir da URL
$instanceName = $_GET['instance'] ?? null;

if (!$instanceName) {
    // Se nenhum nome de instância for fornecido, redireciona para a lista
    header('Location: instancias');
    exit;
}
?>
<style>
.text-muted
 {
    color: #E91E63 !important;
    font-weight: 700 !important;
}
</style>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Conectar Dispositivo</h1>

    <div class="row d-flex justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Conecte seu WhatsApp</h6>
                    <small id="timer-feedback" class="text-muted"></small>
                </div>
                <div class="card-body text-center" id="qr-code-container" data-instancename="<?php echo htmlspecialchars($instanceName); ?>">
                    <h5 class="mb-3">Instância "<?php echo htmlspecialchars($instanceName); ?>"</h5>
                    <p>Escaneie o QR Code abaixo. A verificação da conexão é automática.</p>
                    <center>
                        <img id="qr-code-image" src="" alt="Carregando QR Code..." class="img-fluid" style="display: none;">
                    <center>
                    <div id="loading-spinner" class="mt-3">
                        <div class="spinner-border text-primary" role="status"><span class="sr-only"></span></div>
                        <p>Buscando QR Code...</p>
                    </div>
                </div>
            </div>
        </div>
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
    const checkInterval = 40000; // Verificar a cada 40 segundos
    let timer;

    function checkStatus() {
        loadingSpinner.style.display = 'block';
        qrCodeImage.style.display = 'none'; // Oculta a imagem durante a verificação
        timerFeedback.textContent = 'Verificando...';

        fetch(`instancia_status_ajax?instance=${instanceName}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'connected') {
                    // SUCESSO! Redireciona para a lista de instâncias
                    timerFeedback.textContent = "Conectado!";
                    window.location.href = 'instancias';
                } else if (data.status === 'connecting' && data.qrCodeBase64) {
                    // Ainda conectando, atualiza o QR Code
                    qrCodeImage.src = data.qrCodeBase64;
                    qrCodeImage.style.display = 'block'; // Mostra a imagem atualizada
                    startTimer(); // Reinicia o timer para a próxima verificação
                } else {
                    // Caso de erro ou se não houver QR Code
                    timerFeedback.textContent = 'Erro ao obter QR Code.';
                    qrCodeImage.style.display = 'none';
                    startTimer(); // Tenta novamente após o intervalo
                }
            })
            .catch(error => {
                console.error('Erro na verificação:', error);
                timerFeedback.textContent = 'Erro de conexão.';
            })
            .finally(() => {
                loadingSpinner.style.display = 'none';
            });
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
    
    // Inicia a primeira verificação assim que a página carrega
    checkStatus();
});
</script>