<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];
$categorias = [];
$estados = [];
$localizacoes = [];
$resumo = [
    'total' => 0,
    'ativos' => 0,
    'manutencao' => 0,
    'inativos' => 0
];

$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'id';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'desc' ? 'desc' : 'asc';
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$filtro_categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$filtro_localizacao = isset($_GET['localizacao']) ? trim($_GET['localizacao']) : '';

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 6;
$offset = ($pagina - 1) * $registos_por_pagina;

$colunas_permitidas = [
    'id' => 'e.id',
    'codigo' => 'e.codigo_inventario',
    'designacao' => 'e.designacao',
    'categoria' => 'e.categoria',
    'marca' => 'e.marca',
    'modelo' => 'e.modelo',
    'localizacao' => 'l.servico',
    'estado' => 'e.estado'
];

$coluna_sql = isset($colunas_permitidas[$ordenar])
    ? $colunas_permitidas[$ordenar]
    : 'e.id';

$where = [];
$params = [];

if ($pesquisa !== '') {
    $where[] = '(e.codigo_inventario LIKE :pesquisa OR e.designacao LIKE :pesquisa OR e.numero_serie LIKE :pesquisa)';
    $params[':pesquisa'] = '%' . $pesquisa . '%';
}

if ($filtro_categoria !== '') {
    $where[] = 'e.categoria = :categoria';
    $params[':categoria'] = $filtro_categoria;
}

if ($filtro_estado !== '') {
    $where[] = 'e.estado = :estado';
    $params[':estado'] = $filtro_estado;
}

if ($filtro_localizacao !== '') {
    $where[] = "CONCAT(l.servico, ' - ', l.sala) = :localizacao";
    $params[':localizacao'] = $filtro_localizacao;
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $categorias = $ligacao->query(
        "SELECT DISTINCT categoria FROM equipamentos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria"
    )->fetchAll(PDO::FETCH_COLUMN);

    $estados = $ligacao->query(
        "SELECT DISTINCT estado FROM equipamentos WHERE estado IS NOT NULL AND estado <> '' ORDER BY estado"
    )->fetchAll(PDO::FETCH_COLUMN);

    $localizacoes = $ligacao->query(
        "SELECT DISTINCT CONCAT(servico, ' - ', sala) AS localizacao
         FROM localizacoes
         ORDER BY localizacao"
    )->fetchAll(PDO::FETCH_COLUMN);

    $stmt_resumo = $ligacao->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'Ativo' THEN 1 ELSE 0 END) AS ativos,
            SUM(CASE WHEN estado LIKE '%manutenção%' OR estado LIKE '%manutencao%' THEN 1 ELSE 0 END) AS manutencao,
            SUM(CASE WHEN estado = 'Inativo' THEN 1 ELSE 0 END) AS inativos
         FROM equipamentos"
    );

    $resumo_db = $stmt_resumo->fetch(PDO::FETCH_ASSOC);
    if ($resumo_db) {
        $resumo = [
            'total' => (int) $resumo_db['total'],
            'ativos' => (int) $resumo_db['ativos'],
            'manutencao' => (int) $resumo_db['manutencao'],
            'inativos' => (int) $resumo_db['inativos']
        ];
    }

    $stmt_total = $ligacao->prepare(
        "SELECT COUNT(*)
         FROM equipamentos e
         LEFT JOIN localizacoes l ON e.localizacao_id = l.id
         $where_sql"
    );

    foreach ($params as $chave => $valor) {
        $stmt_total->bindValue($chave, $valor);
    }

    $stmt_total->execute();
    $total_registos = (int) $stmt_total->fetchColumn();
    $total_paginas = max(1, (int) ceil($total_registos / $registos_por_pagina));

    if ($pagina > $total_paginas) {
        $pagina = $total_paginas;
        $offset = ($pagina - 1) * $registos_por_pagina;
    }

    $sql = "SELECT
                e.*,
                l.servico,
                l.sala
            FROM equipamentos e
            LEFT JOIN localizacoes l ON e.localizacao_id = l.id
            $where_sql
            ORDER BY $coluna_sql $direcao
            LIMIT :limite OFFSET :offset";

    $stmt = $ligacao->prepare($sql);

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }

    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar os equipamentos.';
    $resultados = [];
    $total_registos = 0;
    $total_paginas = 0;
}

$ligacao = null;

function query_equipamentos($alteracoes = [])
{
    $params = $_GET;

    foreach ($alteracoes as $chave => $valor) {
        if ($valor === null) {
            unset($params[$chave]);
        } else {
            $params[$chave] = $valor;
        }
    }

    foreach ($params as $chave => $valor) {
        if ($valor === '') {
            unset($params[$chave]);
        }
    }

    return '?' . http_build_query($params);
}

function link_ordenacao_equipamentos($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return query_equipamentos([
        'ordenar' => $campo,
        'direcao' => $nova_direcao,
        'pagina' => null
    ]);
}

function icone_ordenacao_equipamentos($campo, $ordenar, $direcao)
{
    if ($ordenar != $campo) {
        return '<i class="fa-solid fa-sort ms-1"></i>';
    }

    if ($direcao == 'asc') {
        return '<i class="fa-solid fa-sort-up ms-1"></i>';
    }

    return '<i class="fa-solid fa-sort-down ms-1"></i>';
}

function classe_estado_equipamento($estado)
{
    $estado_normalizado = mb_strtolower($estado ?? '', 'UTF-8');

    if (strpos($estado_normalizado, 'ativo') !== false && strpos($estado_normalizado, 'inativo') === false) {
        return 'estado-ativo';
    }

    if (strpos($estado_normalizado, 'manuten') !== false) {
        return 'estado-manutencao';
    }

    if (strpos($estado_normalizado, 'calibra') !== false) {
        return 'estado-calibracao';
    }

    if (strpos($estado_normalizado, 'inativo') !== false) {
        return 'estado-inativo';
    }

    return 'estado-neutro';
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .equipamentos-page {
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
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
        min-height: 74px;
        overflow: hidden;
        padding: 12px 14px;
        position: relative;
    }

    .summary-card::after {
        content: "";
        position: absolute;
        inset: auto -24px -34px auto;
        width: 72px;
        height: 72px;
        border-radius: 999px;
        background: rgba(47, 93, 138, 0.08);
    }

    .summary-card.primary {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #ffffff;
    }

    .summary-card.primary::after {
        background: rgba(255, 255, 255, 0.13);
    }

    .summary-card.success { border-top: 4px solid #22c55e; }
    .summary-card.warning { border-top: 4px solid #f59e0b; }
    .summary-card.danger { border-top: 4px solid #ef4444; }

    .summary-label {
        color: #52677d;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .summary-card.primary .summary-label,
    .summary-card.primary .summary-help,
    .summary-card.primary .summary-icon {
        color: rgba(255, 255, 255, 0.78);
    }

    .summary-value {
        color: #0f172a;
        font-size: 1.42rem;
        font-weight: 900;
        line-height: 1;
        margin-top: 4px;
    }

    .summary-card.primary .summary-value {
        color: #ffffff;
    }

    .summary-help {
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 750;
        margin-top: 4px;
    }

    .summary-icon {
        color: #2F5D8A;
        font-size: 1rem;
        position: absolute;
        right: 12px;
        top: 12px;
        z-index: 1;
    }

    .filters-card,
    .content-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 12px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    .filters-card {
        margin-bottom: 14px;
    }

    .form-label {
        font-size: 0.72rem;
        font-weight: 800;
        color: #31506f;
        text-transform: uppercase;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        border-color: #dbe3ec;
        font-size: 0.86rem;
    }


    .form-control,
    .form-select,
    .btn-sm {
        min-height: 34px;
    }

    .table > :not(caption) > * > * {
        padding: 0.56rem 0.62rem;
    }

    .table-primary-custom th {
        background: #2F5D8A !important;
        color: #ffffff !important;
        border-color: #2F5D8A !important;
        font-weight: 700;
        font-size: 0.84rem;
    }

    .table-primary-custom a {
        color: #ffffff;
        text-decoration: none;
    }

    .table td {
        font-size: 0.86rem;
        vertical-align: middle;
    }

    .code-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #edf4ff;
        border: 1px solid #d6e7ff;
        color: #1E3A5F;
        padding: 3px 9px;
        font-weight: 800;
        font-size: 0.78rem;
    }

    .equipment-name {
        font-weight: 700;
        color: #0f172a;
    }

    .muted-line {
        color: #64748b;
        font-size: 0.78rem;
    }

    .estado-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .estado-ativo {
        background: #e7f5ee;
        border: 1px solid #bfe5cf;
        color: #17623a;
    }

    .estado-manutencao {
        background: #fff4dc;
        border: 1px solid #f3d28b;
        color: #7a4b00;
    }

    .estado-calibracao {
        background: #e8f1fb;
        border: 1px solid #bdd5f0;
        color: #24527a;
    }

    .estado-inativo {
        background: #f3f4f6;
        border: 1px solid #d7dce3;
        color: #6b2730;
    }

    .estado-neutro {
        background: #e2e8f0;
        color: #334155;
    }

    .action-group {
        display: inline-flex;
        gap: 6px;
        flex-wrap: nowrap;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 7px;
        font-size: 0.76rem;
        font-weight: 800;
        text-decoration: none;
        transition: 0.15s;
        white-space: nowrap;
    }

    .action-consultar {
        background: #edf4ff;
        border: 1px solid #cfe0f5;
        color: #1E3A5F;
    }

    .action-btn:hover {
        opacity: 0.85;
        color: inherit;
    }

    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 8px;
        margin-bottom: 0;
    }

    .pagination .page-link {
        color: #2F5D8A;
        border-radius: 8px;
        margin: 0 2px;
    }

    .pagination .page-item.active .page-link {
        background-color: #2F5D8A;
        border-color: #2F5D8A;
        color: #fff;
    }

    @media (max-width: 992px) {
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 576px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 equipamentos-page">

            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h2 class="page-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Listagem de Equipamentos
                    </h2>
                    <p class="page-subtitle">
                        Consulta, pesquisa e acompanhamento dos equipamentos médicos registados.
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <a href="exportar-equipamentos.php" class="btn btn-sm exportar-btn">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Exportar Excel
                    </a>

                    <a href="novo.php" class="btn btn-sm novo-btn">
                        <i class="fa-solid fa-plus me-1"></i>
                        Novo equipamento
                    </a>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-card primary">
                    <i class="fas fa-desktop summary-icon"></i>
                    <div class="summary-label">Equipamentos</div>
                    <div class="summary-value"><?= htmlspecialchars($resumo['total']) ?></div>
                    <div class="summary-help">Total registado</div>
                </div>
                <div class="summary-card success">
                    <i class="fas fa-circle-check summary-icon"></i>
                    <div class="summary-label">Ativos</div>
                    <div class="summary-value"><?= htmlspecialchars($resumo['ativos']) ?></div>
                    <div class="summary-help">Em utilização</div>
                </div>
                <div class="summary-card warning">
                    <i class="fas fa-screwdriver-wrench summary-icon"></i>
                    <div class="summary-label">Manutenção</div>
                    <div class="summary-value"><?= htmlspecialchars($resumo['manutencao']) ?></div>
                    <div class="summary-help">Acompanhamento técnico</div>
                </div>
                <div class="summary-card danger">
                    <i class="fas fa-ban summary-icon"></i>
                    <div class="summary-label">Inativos</div>
                    <div class="summary-value"><?= htmlspecialchars($resumo['inativos']) ?></div>
                    <div class="summary-help">Fora de utilização</div>
                </div>
            </div>

            <div class="filters-card">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Pesquisar</label>
                        <input type="text" name="pesquisa" class="form-control" placeholder="Código, nome ou série" value="<?= htmlspecialchars($pesquisa) ?>">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Categoria</label>
                        <select name="categoria" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $categoria) : ?>
                                <option value="<?= htmlspecialchars($categoria) ?>" <?= $filtro_categoria == $categoria ? 'selected' : '' ?>><?= htmlspecialchars($categoria) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $estado) : ?>
                                <option value="<?= htmlspecialchars($estado) ?>" <?= $filtro_estado == $estado ? 'selected' : '' ?>><?= htmlspecialchars($estado) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Localização</label>
                        <select name="localizacao" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($localizacoes as $localizacao) : ?>
                                <option value="<?= htmlspecialchars($localizacao) ?>" <?= $filtro_localizacao == $localizacao ? 'selected' : '' ?>><?= htmlspecialchars($localizacao) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="ordenar" value="<?= htmlspecialchars($ordenar) ?>">
                    <input type="hidden" name="direcao" value="<?= htmlspecialchars($direcao) ?>">

                    <div class="col-lg-2 col-md-12 d-flex gap-2">
                        <button type="submit" class="btn btn-sm novo-btn flex-fill">
                            <i class="fa-solid fa-filter me-1"></i>
                            Filtrar
                        </button>
                        <a href="lista.php" class="btn btn-sm btn-outline-secondary flex-fill">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="alert alert-danger text-center">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php else : ?>

                <div class="content-card">
                    <?php if (count($resultados) == 0) : ?>
                        <div class="alert alert-info mb-0">
                            Não existem equipamentos para os filtros selecionados.
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('codigo', $ordenar, $direcao) ?>">
                                                Código <?= icone_ordenacao_equipamentos('codigo', $ordenar, $direcao) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('designacao', $ordenar, $direcao) ?>">
                                                Equipamento <?= icone_ordenacao_equipamentos('designacao', $ordenar, $direcao) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('categoria', $ordenar, $direcao) ?>">
                                                Categoria <?= icone_ordenacao_equipamentos('categoria', $ordenar, $direcao) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('marca', $ordenar, $direcao) ?>">
                                                Marca / Modelo <?= icone_ordenacao_equipamentos('marca', $ordenar, $direcao) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('localizacao', $ordenar, $direcao) ?>">
                                                Localização <?= icone_ordenacao_equipamentos('localizacao', $ordenar, $direcao) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('estado', $ordenar, $direcao) ?>">
                                                Estado <?= icone_ordenacao_equipamentos('estado', $ordenar, $direcao) ?>
                                            </a>
                                        </th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados as $equipamento) : ?>
                                        <tr>
                                            <td><span class="code-chip"><?= htmlspecialchars($equipamento->codigo_inventario) ?></span></td>
                                            <td><span class="equipment-name"><?= htmlspecialchars($equipamento->designacao) ?></span></td>
                                            <td><?= htmlspecialchars($equipamento->categoria) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($equipamento->marca) ?></strong>
                                                <div class="muted-line"><?= htmlspecialchars($equipamento->modelo) ?></div>
                                            </td>
                                            <td>
                                                <?= !empty($equipamento->servico)
                                                    ? htmlspecialchars($equipamento->servico . ' - ' . $equipamento->sala)
                                                    : '-' ?>
                                            </td>
                                            <td>
                                                <span class="estado-badge <?= classe_estado_equipamento($equipamento->estado) ?>">
                                                    <?= htmlspecialchars($equipamento->estado) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-group">
                                                    <a href="detalhes.php?id=<?= $equipamento->id ?>" class="action-btn action-consultar" title="Consultar ficha">
                                                        <i class="fa-solid fa-eye"></i>
                                                        Consultar
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_paginas > 1) : ?>
                    <div class="pagination-wrapper">
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>
                                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= query_equipamentos(['pagina' => $i]) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
