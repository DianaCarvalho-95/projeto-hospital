<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];
$edificios = [];
$resumo = [
    'total' => 0,
    'equipamentos' => 0,
    'edificios' => 0,
    'media' => 0
];

$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$filtro_edificio = isset($_GET['edificio']) ? trim($_GET['edificio']) : '';

$where = [];
$params = [];

if ($pesquisa !== '') {
    $where[] = '(l.edificio LIKE :pesquisa OR l.piso LIKE :pesquisa OR l.servico LIKE :pesquisa OR l.sala LIKE :pesquisa)';
    $params[':pesquisa'] = '%' . $pesquisa . '%';
}

if ($filtro_edificio !== '') {
    $where[] = 'l.edificio = :edificio';
    $params[':edificio'] = $filtro_edificio;
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $edificios = $ligacao->query(
        "SELECT DISTINCT edificio
         FROM localizacoes
         WHERE edificio IS NOT NULL AND edificio <> ''
         ORDER BY edificio"
    )->fetchAll(PDO::FETCH_COLUMN);

    $stmt_resumo = $ligacao->query(
        "SELECT
            COUNT(DISTINCT l.id) AS total,
            COUNT(e.id) AS equipamentos,
            COUNT(DISTINCT l.edificio) AS edificios
         FROM localizacoes l
         LEFT JOIN equipamentos e ON e.localizacao_id = l.id"
    );

    $resumo_db = $stmt_resumo->fetch(PDO::FETCH_ASSOC);
    if ($resumo_db) {
        $resumo['total'] = (int) $resumo_db['total'];
        $resumo['equipamentos'] = (int) $resumo_db['equipamentos'];
        $resumo['edificios'] = (int) $resumo_db['edificios'];
        $resumo['media'] = $resumo['total'] > 0
            ? round($resumo['equipamentos'] / $resumo['total'], 1)
            : 0;
    }

    $stmt = $ligacao->prepare(
        "SELECT
            l.*,
            COUNT(e.id) AS total_equipamentos
         FROM localizacoes l
         LEFT JOIN equipamentos e ON e.localizacao_id = l.id
         $where_sql
         GROUP BY l.id
         ORDER BY l.edificio, l.piso, l.servico, l.sala"
    );

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }

    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar as localizações.';
    $resultados = [];
}

$ligacao = null;

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .localizacoes-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    .page-title {
        font-weight: 700;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .novo-btn {
        background: #2F5D8A;
        border-color: #2F5D8A;
        color: #fff;
        border-radius: 8px;
        font-weight: 700;
    }

    .novo-btn:hover {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #fff;
    }

    .exportar-btn {
        background: #e8f5ee;
        border-color: #cfead9;
        color: #198754;
        border-radius: 8px;
        font-weight: 700;
    }

    .exportar-btn:hover {
        background: #d9f0e3;
        border-color: #badfc9;
        color: #146c43;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
    }

    .summary-label {
        color: #55708d;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .summary-value {
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .filters-card,
    .content-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
    }

    .filters-card {
        padding: 12px;
        margin-bottom: 14px;
    }

    .filter-label {
        color: #52677d;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .form-control,
    .form-select {
        border-color: #d8e1ec;
        border-radius: 8px;
        font-size: 0.86rem;
    }

    .filter-btn {
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.84rem;
    }

    .clear-btn {
        color: #1E3A5F;
        border-color: #d8e1ec;
        background: #fff;
    }

    .clear-btn:hover {
        background: #f6faff;
        color: #1E3A5F;
    }

    .content-card {
        padding: 16px;
    }

    .table {
        border-color: #d9e2ec;
    }

    .table-primary-custom th {
        background: #2F5D8A !important;
        color: #ffffff !important;
        border-color: #2F5D8A !important;
        font-weight: 700;
        font-size: 0.86rem;
        white-space: nowrap;
    }

    .table td {
        color: #0f172a;
        font-size: 0.88rem;
        vertical-align: middle;
    }

    .location-main {
        font-weight: 700;
        color: #0f172a;
    }

    .location-sub {
        color: #64748b;
        font-size: 0.78rem;
        margin-top: 2px;
    }

    .count-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        padding: 3px 9px;
        border-radius: 999px;
        background: #e8f1fb;
        color: #1E3A5F;
        font-weight: 800;
        font-size: 0.78rem;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 9px;
        border-radius: 7px;
        font-size: 0.76rem;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
    }

    .action-consultar {
        background: #eaf5ef;
        color: #087443;
    }

    .action-consultar:hover {
        background: #d9eee3;
        color: #075f38;
    }

    .mensagem-erro-custom {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }

    @media (max-width: 991px) {
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 localizacoes-page">

            <div class="d-flex justify-content-between align-items-start mb-3 gap-3">
                <div>
                    <h2 class="page-title mb-1">
                        <i class="fas fa-location-dot me-2"></i>
                        Listagem de Localizações
                    </h2>

                    <p class="page-subtitle">
                        Consulta das localizações hospitalares e dos equipamentos associados a cada espaço.
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <a href="exportar-localizacoes.php" class="btn btn-sm exportar-btn">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Exportar Excel
                    </a>

                    <a href="novo.php" class="btn btn-sm novo-btn">
                        <i class="fa-solid fa-plus me-1"></i>
                        Nova localização
                    </a>
                </div>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="mensagem-erro-custom text-center">
                    <?= h($erro) ?>
                </div>
            <?php else : ?>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-label">Localizações</div>
                        <div class="summary-value"><?= $resumo['total'] ?></div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-label">Equipamentos</div>
                        <div class="summary-value"><?= $resumo['equipamentos'] ?></div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-label">Edifícios</div>
                        <div class="summary-value"><?= $resumo['edificios'] ?></div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-label">Média de equipamentos</div>
                        <div class="summary-value"><?= number_format($resumo['media'], 1, ',', '.') ?></div>
                    </div>
                </div>

                <form method="get" class="filters-card">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-5">
                            <div class="filter-label">Pesquisa</div>
                            <input type="text" name="pesquisa" class="form-control" value="<?= h($pesquisa) ?>" placeholder="Pesquisar por edifício, piso, serviço ou sala">
                        </div>

                        <div class="col-lg-3">
                            <div class="filter-label">Edifício</div>
                            <select name="edificio" class="form-select">
                                <option value="">Todos os edifícios</option>
                                <?php foreach ($edificios as $edificio) : ?>
                                    <option value="<?= h($edificio) ?>" <?= $filtro_edificio === $edificio ? 'selected' : '' ?>>
                                        <?= h($edificio) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary filter-btn">
                                <i class="fa-solid fa-magnifying-glass me-1"></i>
                                Filtrar
                            </button>

                            <a href="lista.php" class="btn clear-btn filter-btn">
                                Limpar
                            </a>
                        </div>
                    </div>
                </form>

                <?php if (count($resultados) == 0) : ?>
                    <div class="alert alert-info">
                        Não existem localizações para os filtros selecionados.
                    </div>
                <?php else : ?>
                    <div class="content-card">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>Localização</th>
                                        <th>Edifício</th>
                                        <th>Piso</th>
                                        <th>Serviço / Departamento</th>
                                        <th>Sala</th>
                                        <th>N.º Equipamentos</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($resultados as $localizacao) : ?>
                                        <tr>
                                            <td>
                                                <div class="location-main"><?= h($localizacao->servico) ?></div>
                                                <div class="location-sub"><?= h($localizacao->edificio . ' - ' . $localizacao->piso . ' - ' . $localizacao->sala) ?></div>
                                            </td>
                                            <td><?= h($localizacao->edificio) ?></td>
                                            <td><?= h($localizacao->piso) ?></td>
                                            <td><?= h($localizacao->servico) ?></td>
                                            <td><?= h($localizacao->sala) ?></td>
                                            <td><span class="count-pill"><?= (int) $localizacao->total_equipamentos ?></span></td>
                                            <td>
                                                <a href="detalhes.php?id=<?= $localizacao->id ?>" class="action-btn action-consultar">
                                                    <i class="fa-solid fa-eye"></i>
                                                    Ver detalhes
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>
