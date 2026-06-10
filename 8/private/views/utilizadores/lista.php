<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

if (($_SESSION['profile'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/private/views/dashboard/dashboard.php');
    exit;
}

$erro = '';
$mensagem = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$utilizadores = [];
$resumo = [
    'total' => 0,
    'administradores' => 0,
    'tecnicos' => 0,
    'ativos' => 0
];
$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_resumo = $ligacao->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN profile = 'admin' THEN 1 ELSE 0 END) AS administradores,
            SUM(CASE WHEN profile <> 'admin' THEN 1 ELSE 0 END) AS tecnicos,
            SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS ativos
         FROM agents"
    );
    $resumo_db = $stmt_resumo->fetch(PDO::FETCH_ASSOC);
    if ($resumo_db) {
        $resumo = [
            'total' => (int) $resumo_db['total'],
            'administradores' => (int) $resumo_db['administradores'],
            'tecnicos' => (int) $resumo_db['tecnicos'],
            'ativos' => (int) $resumo_db['ativos']
        ];
    }

    $where = '';
    $params = [];

    if ($pesquisa !== '') {
        $where = "WHERE display_name LIKE :pesquisa
                  OR name LIKE :pesquisa
                  OR codigo LIKE :pesquisa
                  OR profile LIKE :pesquisa
                  OR identificador LIKE :pesquisa
                  OR notas LIKE :pesquisa";
        $params[':pesquisa'] = '%' . $pesquisa . '%';
    }

    $stmt = $ligacao->prepare(
        "SELECT id, display_name, codigo, name, profile, identificador, notas, fotografia, ativo, created_at, last_login
         FROM agents
         $where
         ORDER BY CASE WHEN profile = 'admin' THEN 0 ELSE 1 END, codigo ASC, display_name ASC"
    );

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }

    $stmt->execute();
    $utilizadores = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar os utilizadores.';
}

$ligacao = null;

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function nome_utilizador($utilizador)
{
    if (!empty($utilizador->display_name)) {
        return $utilizador->display_name;
    }

    return strstr($utilizador->name, '@', true) ?: $utilizador->name;
}

function perfil_utilizador($profile)
{
    return $profile === 'admin' ? 'Administrador' : 'Técnico';
}

function classe_perfil($profile)
{
    return $profile === 'admin' ? 'perfil-admin' : 'perfil-tecnico';
}

function data_utilizador($data)
{
    if (empty($data)) {
        return 'Sem registo';
    }

    return date('d/m/Y H:i', strtotime($data));
}

function foto_utilizador($ficheiro)
{
    $ficheiro = $ficheiro ?: 'admin.png';
    return BASE_URL . '/private/assets/img/utilizadores/' . rawurlencode($ficheiro);
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .utilizadores-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 14px 20px;
    }

    .page-title {
        color: #1E3A5F;
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
    }

    .page-subtitle {
        color: #526985;
        font-size: 0.86rem;
        margin: 2px 0 0;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin: 10px 0;
    }

    .summary-card {
        background: #ffffff;
        border: 1px solid #dbe5ef;
        border-radius: 10px;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
        min-height: 62px;
        overflow: hidden;
        padding: 10px 13px;
        position: relative;
    }

    .summary-card:first-child {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #ffffff;
    }

    .summary-card::after {
        background: rgba(47, 93, 138, 0.10);
        border-radius: 999px;
        bottom: -22px;
        content: '';
        height: 64px;
        position: absolute;
        right: -18px;
        width: 64px;
    }

    .summary-label {
        color: #496583;
        font-size: 0.69rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .summary-card:first-child .summary-label,
    .summary-card:first-child .summary-text {
        color: rgba(255, 255, 255, 0.88);
    }

    .summary-value {
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.05;
    }

    .summary-card:first-child .summary-value {
        color: #ffffff;
    }

    .summary-text {
        color: #496583;
        font-size: 0.73rem;
    }

    .search-card,
    .content-card {
        background: #ffffff;
        border: 1px solid #dbe5ef;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        padding: 10px 12px;
    }

    .search-card {
        margin-bottom: 12px;
    }

    .form-label {
        color: #31506f;
        font-size: 0.69rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .form-control,
    .btn-sm {
        min-height: 32px;
        font-size: 0.82rem;
    }

    .btn-main {
        background: #2F5D8A;
        border-color: #2F5D8A;
        color: #ffffff;
        font-weight: 700;
    }

    .btn-main:hover {
        background: #1E3A5F;
        color: #ffffff;
    }

    .table-primary-custom th {
        background: #2F5D8A !important;
        border-color: #2F5D8A !important;
        color: #ffffff !important;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .table > :not(caption) > * > * {
        padding: 0.44rem 0.54rem;
    }

    .table td {
        font-size: 0.82rem;
        vertical-align: middle;
    }

    .user-cell {
        align-items: center;
        display: flex;
        gap: 10px;
        min-width: 220px;
    }

    .user-photo {
        background: #eef4fb;
        border: 2px solid #dce8f4;
        border-radius: 50%;
        height: 42px;
        object-fit: cover;
        width: 42px;
    }

    .user-name {
        color: #0f172a;
        font-weight: 700;
        line-height: 1.15;
    }

    .user-email,
    .muted-line {
        color: #61748c;
        font-size: 0.74rem;
    }

    .code-chip,
    .profile-badge,
    .status-badge {
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.71rem;
        font-weight: 700;
        padding: 3px 8px;
        white-space: nowrap;
    }

    .code-chip {
        background: #edf4ff;
        border: 1px solid #d6e7ff;
        color: #1E3A5F;
    }

    .perfil-admin {
        background: #dcfce7;
        color: #166534;
    }

    .perfil-tecnico {
        background: #fff3d0;
        color: #8a5200;
    }

    .status-ativo {
        background: #e7f5ee;
        color: #17623a;
    }

    .status-inativo {
        background: #fee2e2;
        color: #991b1b;
    }

    .action-btn {
        border-radius: 8px;
        font-size: 0.74rem;
        font-weight: 700;
        padding: 4px 9px;
        white-space: nowrap;
    }

    .btn-disable-user {
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #be123c;
    }

    .btn-enable-user {
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        color: #047857;
    }

    .btn-disabled-user {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #64748b;
        cursor: not-allowed;
    }


    .confirm-overlay {
        align-items: center;
        background: rgba(15, 23, 42, 0.45);
        bottom: 0;
        display: none;
        justify-content: center;
        left: 16.666666%;
        padding: 20px;
        position: fixed;
        right: 0;
        top: 0;
        z-index: 3000;
    }

    .confirm-overlay.is-visible {
        display: flex;
    }

    .confirm-modal {
        background: #ffffff;
        border: 1px solid #dbe5ef;
        border-radius: 14px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
        max-width: 430px;
        padding: 24px 26px;
        text-align: center;
        width: 100%;
    }

    .confirm-icon {
        align-items: center;
        background: #fff1f2;
        border-radius: 12px;
        color: #be123c;
        display: inline-flex;
        height: 44px;
        justify-content: center;
        margin: 0 auto 12px;
        width: 44px;
    }

    .confirm-title {
        color: #1E3A5F;
        font-size: 1.08rem;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .confirm-text {
        color: #526985;
        font-size: 0.9rem;
        line-height: 1.45;
        margin: 0 auto 20px;
        max-width: 330px;
    }

    .confirm-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .btn-confirm-cancel,
    .btn-confirm-submit {
        border-radius: 9px;
        font-size: 0.84rem;
        font-weight: 700;
        min-width: 104px;
        padding: 8px 13px;
    }

    .btn-confirm-cancel {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #1E3A5F;
    }

    .btn-confirm-submit {
        background: #be123c;
        border: 1px solid #be123c;
        color: #ffffff;
    }

    @media (max-width: 1200px) {
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991px) {
        .confirm-overlay {
            left: 25%;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 utilizadores-page">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h2 class="page-title">
                        <i class="fas fa-users me-2"></i>
                        Utilizadores
                    </h2>
                    <p class="page-subtitle">Consulta dos administradores e técnicos com acesso à área privada.</p>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Utilizadores</div>
                    <div class="summary-value"><?= (int) $resumo['total'] ?></div>
                    <div class="summary-text">Total registado</div>
                </div>
                <div class="summary-card" style="border-top: 4px solid #22c55e;">
                    <div class="summary-label">Administradores</div>
                    <div class="summary-value"><?= (int) $resumo['administradores'] ?></div>
                    <div class="summary-text">Gestão do sistema</div>
                </div>
                <div class="summary-card" style="border-top: 4px solid #f59e0b;">
                    <div class="summary-label">Técnicos</div>
                    <div class="summary-value"><?= (int) $resumo['tecnicos'] ?></div>
                    <div class="summary-text">Operação e manutenção</div>
                </div>
                <div class="summary-card" style="border-top: 4px solid #2563eb;">
                    <div class="summary-label">Ativos</div>
                    <div class="summary-value"><?= (int) $resumo['ativos'] ?></div>
                    <div class="summary-text">Com acesso disponível</div>
                </div>
            </div>

            <div class="search-card">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label">Pesquisa</label>
                        <input type="text" name="pesquisa" class="form-control" placeholder="Nome, email, código, perfil ou identificador" value="<?= h($pesquisa) ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-main flex-fill" type="submit">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>
                            Filtrar
                        </button>
                        <a href="lista.php" class="btn btn-sm btn-outline-secondary flex-fill">Limpar</a>
                    </div>
                </form>
            </div>

            <?php if ($mensagem === 'desativado') : ?>
                <div class="alert alert-success py-2">Utilizador desativado com sucesso.</div>
            <?php elseif ($mensagem === 'ativado') : ?>
                <div class="alert alert-success py-2">Utilizador ativado com sucesso.</div>
            <?php elseif ($mensagem === 'propria-conta') : ?>
                <div class="alert alert-warning py-2">Não é possível desativar a conta que está atualmente autenticada.</div>
            <?php elseif ($mensagem === 'erro') : ?>
                <div class="alert alert-danger py-2">Não foi possível alterar o estado do utilizador.</div>
            <?php endif; ?>

            <?php if ($erro !== '') : ?>
                <div class="alert alert-danger py-2"><?= h($erro) ?></div>
            <?php endif; ?>

            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="muted-line">
                        A mostrar <?= count($utilizadores) ?> de <?= (int) $resumo['total'] ?> utilizadores
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-primary-custom">
                            <tr>
                                <th>Código</th>
                                <th>Utilizador</th>
                                <th>Perfil</th>
                                <th>Identificador</th>
                                <th>Notas</th>
                                <th>Último login</th>
                                <th>Criado em</th>
                                <th>Estado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($utilizadores) === 0) : ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Não foram encontrados utilizadores.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($utilizadores as $utilizador) : ?>
                                <tr>
                                    <td><span class="code-chip"><?= h($utilizador->codigo) ?></span></td>
                                    <td>
                                        <div class="user-cell">
                                            <img src="<?= h(foto_utilizador($utilizador->fotografia)) ?>" alt="<?= h(nome_utilizador($utilizador)) ?>" class="user-photo">
                                            <div>
                                                <div class="user-name"><?= h(nome_utilizador($utilizador)) ?></div>
                                                <div class="user-email"><?= h($utilizador->name) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="profile-badge <?= h(classe_perfil($utilizador->profile)) ?>"><?= h(perfil_utilizador($utilizador->profile)) ?></span></td>
                                    <td><?= h($utilizador->identificador) ?></td>
                                    <td><?= h($utilizador->notas) ?></td>
                                    <td><?= h(data_utilizador($utilizador->last_login)) ?></td>
                                    <td><?= h(data_utilizador($utilizador->created_at)) ?></td>
                                    <td>
                                        <?php if ((int) $utilizador->ativo === 1) : ?>
                                            <span class="status-badge status-ativo">Ativo</span>
                                        <?php else : ?>
                                            <span class="status-badge status-inativo">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($utilizador->name ?? '') === ($_SESSION['utilizador'] ?? '')) : ?>
                                            <button class="action-btn btn-disabled-user" type="button" disabled>Conta atual</button>
                                        <?php else : ?>
                                            <form method="post" action="alterar-estado.php" class="m-0">
                                                <input type="hidden" name="id" value="<?= (int) $utilizador->id ?>">
                                                <input type="hidden" name="estado" value="<?= (int) $utilizador->ativo === 1 ? 0 : 1 ?>">
                                                <?php if ((int) $utilizador->ativo === 1) : ?>
                                                    <button class="action-btn btn-disable-user js-confirm-disable" type="button" data-user="<?= h(nome_utilizador($utilizador)) ?>">
                                                        <i class="fa-solid fa-user-slash me-1"></i>Desativar
                                                    </button>
                                                <?php else : ?>
                                                    <button class="action-btn btn-enable-user" type="submit">
                                                        <i class="fa-solid fa-user-check me-1"></i>Ativar
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="confirm-overlay" id="confirmDisableModal" aria-hidden="true">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmDisableTitle">
        <div class="confirm-icon">
            <i class="fa-solid fa-user-slash"></i>
        </div>
        <div class="confirm-title" id="confirmDisableTitle">Desativar utilizador</div>
        <div class="confirm-text" id="confirmDisableText">
            Pretende desativar este utilizador? O acesso à área privada ficará bloqueado.
        </div>
        <div class="confirm-actions">
            <button type="button" class="btn btn-confirm-cancel" id="cancelDisableUser">Cancelar</button>
            <button type="button" class="btn btn-confirm-submit" id="confirmDisableUser">Desativar</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('confirmDisableModal');
        const text = document.getElementById('confirmDisableText');
        const cancelBtn = document.getElementById('cancelDisableUser');
        const confirmBtn = document.getElementById('confirmDisableUser');
        let pendingForm = null;

        document.querySelectorAll('.js-confirm-disable').forEach(function (button) {
            button.addEventListener('click', function () {
                pendingForm = button.closest('form');
                const user = button.dataset.user || 'este utilizador';
                text.textContent = 'Pretende desativar ' + user + '? O acesso à área privada ficará bloqueado.';
                modal.classList.add('is-visible');
                modal.setAttribute('aria-hidden', 'false');
            });
        });

        function closeModal() {
            pendingForm = null;
            modal.classList.remove('is-visible');
            modal.setAttribute('aria-hidden', 'true');
        }

        cancelBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-visible')) {
                closeModal();
            }
        });

        confirmBtn.addEventListener('click', function () {
            if (pendingForm) {
                pendingForm.submit();
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>
