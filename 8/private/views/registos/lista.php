<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

if (($_SESSION['profile'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/private/views/dashboard/dashboard.php');
    exit;
}

function h($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$erro = '';
$registos = [];
$resumo = [
    'total' => 0,
    'hoje' => 0,
    'utilizadores' => 0,
    'modulos' => 0
];

$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$modulo = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
$acao = isset($_GET['acao']) ? trim($_GET['acao']) : '';
$pagina_atual = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$registos_por_pagina = 6;
$offset = ($pagina_atual - 1) * $registos_por_pagina;
$total_registos = 0;
$total_paginas = 1;
$modulos = [];
$acoes = [];
$export_params = $_GET;
unset($export_params['pagina']);
$export_query = http_build_query($export_params);
$export_prefix = 'exportar-registos.php' . ($export_query !== '' ? '?' . h($export_query) . '&' : '?');

$where = [];
$params = [];

if ($pesquisa !== '') {
    $where[] = '(utilizador LIKE :pesquisa OR descricao LIKE :pesquisa OR entidade LIKE :pesquisa)';
    $params[':pesquisa'] = '%' . $pesquisa . '%';
}

if ($modulo !== '') {
    $where[] = 'modulo = :modulo';
    $params[':modulo'] = $modulo;
}

if ($acao !== '') {
    $where[] = 'acao = :acao';
    $params[':acao'] = $acao;
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $resumo = $ligacao->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN DATE(data_evento) = CURDATE() THEN 1 ELSE 0 END) AS hoje,
            COUNT(DISTINCT utilizador) AS utilizadores,
            COUNT(DISTINCT modulo) AS modulos
         FROM eventos_logs"
    )->fetch(PDO::FETCH_ASSOC) ?: $resumo;

    $modulos = $ligacao->query("SELECT DISTINCT modulo FROM eventos_logs ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN);
    $acoes = $ligacao->query("SELECT DISTINCT acao FROM eventos_logs ORDER BY acao")->fetchAll(PDO::FETCH_COLUMN);

    $stmt_total = $ligacao->prepare("SELECT COUNT(*) FROM eventos_logs $where_sql");
    foreach ($params as $chave => $valor) {
        $stmt_total->bindValue($chave, $valor);
    }
    $stmt_total->execute();
    $total_registos = (int) $stmt_total->fetchColumn();
    $total_paginas = max(1, (int) ceil($total_registos / $registos_por_pagina));

    $stmt = $ligacao->prepare(
        "SELECT *
         FROM eventos_logs
         $where_sql
         ORDER BY data_evento DESC, id DESC
         LIMIT :limite OFFSET :offset"
    );
    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }
    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $registos = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar os registos de eventos.';
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 registos-page">
            <div class="d-flex justify-content-between align-items-start mb-3 gap-3 flex-wrap">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>
                        Registos de Eventos
                    </h2>
                    <p class="page-subtitle">Consulta das ações registadas na área privada da plataforma.</p>
                </div>
                <div class="export-actions">
                    <a href="<?= $export_prefix ?>formato=excel" class="btn btn-sm exportar-btn">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Excel
                    </a>
                    <a href="<?= $export_prefix ?>formato=csv" class="btn btn-sm exportar-btn exportar-btn-secondary">
                        <i class="fa-solid fa-file-csv me-1"></i>
                        CSV
                    </a>
                    <a href="<?= $export_prefix ?>formato=imprimir" target="_blank" class="btn btn-sm exportar-btn exportar-btn-secondary">
                        <i class="fa-solid fa-file-pdf me-1"></i>
                        PDF
                    </a>
                </div>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="mensagem-erro-custom text-center"><?= h($erro) ?></div>
            <?php else : ?>
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-label">Eventos</div>
                        <div class="summary-value"><?= (int) $resumo['total'] ?></div>
                        <div class="summary-text">Total registado</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Hoje</div>
                        <div class="summary-value"><?= (int) $resumo['hoje'] ?></div>
                        <div class="summary-text">Atividade do dia</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Utilizadores</div>
                        <div class="summary-value"><?= (int) $resumo['utilizadores'] ?></div>
                        <div class="summary-text">Com atividade</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Módulos</div>
                        <div class="summary-value"><?= (int) $resumo['modulos'] ?></div>
                        <div class="summary-text">Áreas registadas</div>
                    </div>
                </div>

                <form method="get" class="filters-card">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-4">
                            <div class="filter-label">Pesquisa</div>
                            <input type="text" name="pesquisa" class="form-control" value="<?= h($pesquisa) ?>" placeholder="Utilizador, entidade ou descrição">
                        </div>
                        <div class="col-lg-3">
                            <div class="filter-label">Módulo</div>
                            <select name="modulo" class="form-select">
                                <option value="">Todos os módulos</option>
                                <?php foreach ($modulos as $item) : ?>
                                    <option value="<?= h($item) ?>" <?= $modulo === $item ? 'selected' : '' ?>><?= h($item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <div class="filter-label">Ação</div>
                            <select name="acao" class="form-select">
                                <option value="">Todas as ações</option>
                                <?php foreach ($acoes as $item) : ?>
                                    <option value="<?= h($item) ?>" <?= $acao === $item ? 'selected' : '' ?>><?= h($item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary filter-btn">
                                <i class="fa-solid fa-filter me-1"></i>
                                Filtrar
                            </button>
                            <a href="lista.php" class="btn clear-btn filter-btn">Limpar</a>
                        </div>
                    </div>
                </form>

                <div class="content-card">
                    <?php if (count($registos) === 0) : ?>
                        <div class="alert alert-info mb-0">Ainda não existem registos para os filtros selecionados.</div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>Data</th>
                                        <th>Utilizador</th>
                                        <th>Módulo</th>
                                        <th>Ação</th>
                                        <th>Entidade</th>
                                        <th>Descrição</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registos as $registo) : ?>
                                        <tr>
                                            <td><?= h(date('d/m/Y H:i', strtotime($registo->data_evento))) ?></td>
                                            <td>
                                                <strong><?= h($registo->utilizador ?: 'Sistema') ?></strong>
                                                <div class="muted-line"><?= h($registo->perfil ?: 'Sem perfil') ?></div>
                                            </td>
                                            <td><span class="count-pill"><?= h($registo->modulo) ?></span></td>
                                            <td><?= h($registo->acao) ?></td>
                                            <td>
                                                <?= h($registo->entidade ?: '-') ?>
                                                <?php if (!empty($registo->entidade_id)) : ?>
                                                    <div class="muted-line">ID <?= (int) $registo->entidade_id ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= h($registo->descricao ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_paginas > 1) : ?>
                    <div class="pagination-wrap">
                        <ul class="pagination pagination-sm">
                            <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>
                                <?php
                                $query = $_GET;
                                $query['pagina'] = $i;
                                ?>
                                <li class="page-item <?= $i === $pagina_atual ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= h(http_build_query($query)) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>




