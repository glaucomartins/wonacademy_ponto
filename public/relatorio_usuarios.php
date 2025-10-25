<?php
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/includes/sidebar.php'; 
require_once __DIR__ . '/includes/topbar.php'; 

// --- VERIFICAÇÃO DE PERMISSÃO ---
// Apenas Super Admins (1) e Admins (2) podem gerar o relatório completo.
if ($currentUser['permissao'] > 2) {
    // Redireciona usuários não autorizados.
    header('Location: index?error=access_denied');
    exit;
}

// --- LÓGICA DE BUSCA DE USUÁRIOS ---
$usuarios = [];
try {
    if ($currentUser['permissao'] == 1) {
        // Super Admin: busca todos os usuários, exceto outros Super Admins.
        $stmt = $pdo->prepare("SELECT nome, email, whatsapp FROM tbl_usuarios WHERE permissao != 1 ORDER BY nome ASC");
    } else {
        // Admin: busca apenas os usuários que ele cadastrou.
        $stmt = $pdo->prepare("SELECT nome, email, whatsapp FROM tbl_usuarios WHERE id_administrador = :id_admin ORDER BY nome ASC");
        $stmt->bindParam(':id_admin', $currentUser['id_user'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em caso de erro na consulta, exibe uma mensagem amigável.
    die("Erro ao buscar usuários: " . $e->getMessage());
}

// --- GERAÇÃO DO RELATÓRIO EM HTML ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Usuários</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <h1>Relatório de Usuários</h1>
    <p>Total de usuários encontrados: <strong><?php echo count($usuarios); ?></strong></p>
    
    <button onclick="window.print()" class="no-print">Imprimir Relatório</button>
    <a href="index" class="no-print">Voltar ao Painel</a>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>WhatsApp</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="3" style="text-align:center;">Nenhum usuário para exibir.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nome'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($usuario['whatsapp'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
