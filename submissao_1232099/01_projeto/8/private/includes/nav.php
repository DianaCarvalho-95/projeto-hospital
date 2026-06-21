<?php

$nome_topbar = 'Utilizador';
$perfil_topbar = 'Acesso privado';
$foto_topbar = BASE_URL . '/private/assets/img/utilizadores/admin.png';

$notificacoes_topbar = [];
$data_notificacoes_topbar = date('d/m/Y');

try {
    $ligacao_notif = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao_notif->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_notif = $ligacao_notif->query(
        "SELECT e.codigo_inventario, e.designacao, m.tipo_manutencao, m.responsavel
         FROM manutencoes m
         INNER JOIN equipamentos e ON e.id = m.equipamento_id
         WHERE m.proxima_manutencao = CURDATE()
         ORDER BY e.codigo_inventario
         LIMIT 4"
    );
    foreach ($stmt_notif->fetchAll(PDO::FETCH_OBJ) as $linha) {
        $notificacoes_topbar[] = [
            'classe' => 'notif-maintenance',
            'tipo' => 'Manutenção',
            'titulo' => $linha->codigo_inventario . ' - ' . ($linha->tipo_manutencao ?: 'Intervenção'),
            'descricao' => $linha->designacao . ($linha->responsavel ? ' - ' . $linha->responsavel : '')
        ];
    }

    $stmt_notif = $ligacao_notif->query(
        "SELECT e.codigo_inventario, mv.local_origem, mv.local_destino
         FROM movimentacoes mv
         INNER JOIN equipamentos e ON e.id = mv.equipamento_id
         WHERE mv.data_movimentacao = CURDATE()
         ORDER BY mv.id DESC
         LIMIT 4"
    );
    foreach ($stmt_notif->fetchAll(PDO::FETCH_OBJ) as $linha) {
        $notificacoes_topbar[] = [
            'classe' => 'notif-move',
            'tipo' => 'Movimentação',
            'titulo' => $linha->codigo_inventario . ' - Transferência',
            'descricao' => $linha->local_origem . ' - ' . $linha->local_destino
        ];
    }

    $stmt_notif = $ligacao_notif->query(
        "SELECT e.codigo_inventario, em.servico_origem, em.servico_destino
         FROM emprestimos em
         INNER JOIN equipamentos e ON e.id = em.equipamento_id
         WHERE em.data_emprestimo = CURDATE()
            OR (em.data_prevista_devolucao = CURDATE() AND em.data_devolucao IS NULL)
         ORDER BY em.id DESC
         LIMIT 4"
    );
    foreach ($stmt_notif->fetchAll(PDO::FETCH_OBJ) as $linha) {
        $notificacoes_topbar[] = [
            'classe' => 'notif-loan',
            'tipo' => 'Empréstimo',
            'titulo' => $linha->codigo_inventario . ' - Empréstimo',
            'descricao' => $linha->servico_origem . ' - ' . $linha->servico_destino
        ];
    }

    $stmt_notif = $ligacao_notif->query(
        "SELECT e.codigo_inventario, gc.tipo_contrato, gc.entidade_responsavel
         FROM garantias_contratos gc
         INNER JOIN equipamentos e ON e.id = gc.equipamento_id
         WHERE gc.data_fim = CURDATE()
         ORDER BY e.codigo_inventario
         LIMIT 4"
    );
    foreach ($stmt_notif->fetchAll(PDO::FETCH_OBJ) as $linha) {
        $notificacoes_topbar[] = [
            'classe' => 'notif-warranty',
            'tipo' => 'Garantia',
            'titulo' => $linha->codigo_inventario . ' - ' . ($linha->tipo_contrato ?: 'Garantia'),
            'descricao' => 'Termina hoje' . ($linha->entidade_responsavel ? ' - ' . $linha->entidade_responsavel : '')
        ];
    }
} catch (PDOException $err) {
    $notificacoes_topbar = [];
}

$total_notificacoes_topbar = count($notificacoes_topbar);
$notificacoes_topbar_chave = date('Y-m-d');


if (!empty($_SESSION['utilizador'])) {
    try {
        $ligacao_nav = new PDO(
            "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );
        $ligacao_nav->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt_nav = $ligacao_nav->prepare(
            "SELECT display_name, name, profile, fotografia
             FROM agents
             WHERE name = :utilizador
             LIMIT 1"
        );
        $stmt_nav->execute([':utilizador' => $_SESSION['utilizador']]);
        $utilizador_nav = $stmt_nav->fetch(PDO::FETCH_OBJ);

        if ($utilizador_nav) {
            $nome_topbar = $utilizador_nav->display_name ?: $utilizador_nav->name;
            $perfil_topbar = $utilizador_nav->profile === 'admin' ? 'Administrador' : 'Técnico';
            if (!empty($utilizador_nav->fotografia)) {
                $foto_topbar = BASE_URL . '/private/assets/img/utilizadores/' . rawurlencode($utilizador_nav->fotografia);
            }
        }
    } catch (PDOException $err) {
        $perfil_topbar = ($_SESSION['profile'] ?? '') === 'admin' ? 'Administrador' : 'Técnico';
    }
}

?>

<header class="topbar-custom">
    <div class="dropdown notification-menu" data-notification-menu data-notification-date="<?= htmlspecialchars($notificacoes_topbar_chave, ENT_QUOTES, 'UTF-8') ?>">
        <button class="notification-button" type="button" data-notification-button data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificações" title="Notificações">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
            </svg>
                    <?php if ($total_notificacoes_topbar > 0) : ?>
                <span class="notification-daily-indicator" data-notification-indicator><?= (int) $total_notificacoes_topbar ?></span>
            <?php endif; ?>
</button>
        <div class="dropdown-menu dropdown-menu-end notification-dropdown">
            <div class="notification-header">
                <strong>Notificações de hoje</strong>
                <span><?= htmlspecialchars($data_notificacoes_topbar, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if ($total_notificacoes_topbar > 0) : ?>
                <div class="notification-list">
                    <?php foreach (array_slice($notificacoes_topbar, 0, 6) as $notificacao) : ?>
                        <div class="notification-item <?= htmlspecialchars($notificacao['classe'], ENT_QUOTES, 'UTF-8') ?>">
                            <span><?= htmlspecialchars($notificacao['tipo'], ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= htmlspecialchars($notificacao['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($notificacao['descricao'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="notification-empty">Sem notificações planeadas para hoje.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="dropdown user-menu">
        <button class="btn user-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?= htmlspecialchars($foto_topbar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($nome_topbar, ENT_QUOTES, 'UTF-8') ?>" class="user-avatar-img">
            <span class="user-info">
                <span class="user-role"><?= htmlspecialchars($perfil_topbar, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-email"><?= htmlspecialchars($_SESSION['utilizador'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </span>
        </button>

        <ul class="dropdown-menu dropdown-menu-end user-dropdown">
            <li class="dropdown-header user-dropdown-header">
                <img src="<?= htmlspecialchars($foto_topbar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($nome_topbar, ENT_QUOTES, 'UTF-8') ?>" class="dropdown-avatar">
                <span>
                    <strong><?= htmlspecialchars($nome_topbar, ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars($perfil_topbar, ENT_QUOTES, 'UTF-8') ?></small>
                </span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item user-dropdown-item" href="<?php echo BASE_URL; ?>/private/views/utilizadores/alterar-password.php">
                    <i class="fa-solid fa-key"></i>
                    Alterar password
                </a>
            </li>
            <li>
                <a class="dropdown-item user-dropdown-item logout-item" href="<?php echo BASE_URL; ?>/private/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Sair
                </a>
            </li>
        </ul>
    </div>
</header>

