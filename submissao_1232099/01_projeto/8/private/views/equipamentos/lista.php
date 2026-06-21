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
$filtro_alerta = isset($_GET['alerta']) ? trim($_GET['alerta']) : '';

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

$where = ['e.estado <> :estado_excluido'];
$params = [':estado_excluido' => 'Inativo'];

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
        "SELECT DISTINCT estado FROM equipamentos WHERE estado IS NOT NULL AND estado <> '' AND estado <> 'Inativo' ORDER BY estado"
    )->fetchAll(PDO::FETCH_COLUMN);

    $localizacoes = $ligacao->query(
        "SELECT DISTINCT CONCAT(servico, ' - ', sala) AS localizacao
         FROM localizacoes
         ORDER BY localizacao"
    )->fetchAll(PDO::FETCH_COLUMN);

    $stmt_resumo = $ligacao->query(
        "SELECT
            SUM(CASE WHEN estado <> 'Inativo' THEN 1 ELSE 0 END) AS total,
            SUM(CASE WHEN estado = 'Ativo' THEN 1 ELSE 0 END) AS ativos,
            SUM(CASE WHEN estado LIKE '%manuten%' THEN 1 ELSE 0 END) AS manutencao,
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
                    <div class="export-actions">
                        <a href="exportar-equipamentos.php?formato=excel" class="btn btn-sm exportar-btn">
                            <i class="fa-solid fa-file-excel me-1"></i>
                            Excel
                        </a>
                        <a href="exportar-equipamentos.php?formato=csv" class="btn btn-sm exportar-btn exportar-btn-secondary">
                            <i class="fa-solid fa-file-csv me-1"></i>
                            CSV
                        </a>
                        <a href="exportar-equipamentos.php?formato=imprimir" target="_blank" class="btn btn-sm exportar-btn exportar-btn-secondary">
                            <i class="fa-solid fa-file-pdf me-1"></i>
                            PDF
                        </a>
                    </div>

                    <a href="novo.php" class="btn btn-sm novo-btn">
                        <i class="fa-solid fa-plus me-1"></i>
                        Novo equipamento
                    </a>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-card primary">
                    <i class="fas fa-desktop summary-icon"></i>
                    <div class="summary-label">Parque operacional</div>
                    <div class="summary-value"><?= htmlspecialchars($resumo['total']) ?></div>
                    <div class="summary-help">Sem equipamentos arquivados</div>
                </div>
                <div class="summary-card success">
                    <i class="fas fa-circle-check summary-icon"></i>
                    <div class="summary-label">Ativos</div>
                    <div class="summary-value"><?= htmlspecialchars($resumo['ativos']) ?></div>
                    <div class="summary-help">Disponíveis para utilização</div>
                </div>
                <div class="summary-card warning">
                    <i class="fas fa-screwdriver-wrench summary-icon"></i>
                    <div class="summary-label">Em manutenção</div>
                    <div class="summary-value"><?= htmlspecialchars($resumo['manutencao']) ?></div>
                    <div class="summary-help">Intervenções em curso</div>
                </div>
                <a href="../arquivo/lista.php" class="summary-card-link">
                    <div class="summary-card archive">
                        <i class="fas fa-box-archive summary-icon"></i>
                        <div class="summary-label">Arquivo</div>
                        <div class="summary-value"><?= htmlspecialchars($resumo['inativos']) ?></div>
                        <div class="summary-help">Inativos fora da listagem</div>
                    </div>
                </a>
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















