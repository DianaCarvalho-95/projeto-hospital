<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];
$categorias = [];
$localizacoes = [];
$resumo = [
    'total' => 0,
    'categorias' => 0,
    'localizacoes' => 0,
    'valor' => 0
];

$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$filtro_categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$filtro_localizacao = isset($_GET['localizacao']) ? trim($_GET['localizacao']) : '';
$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$registos_por_pagina = 6;
$offset = ($pagina - 1) * $registos_por_pagina;
$total_paginas = 1;
$total_registos = 0;

$where = ['e.estado = :estado_arquivo'];
$params = [':estado_arquivo' => 'Inativo'];

if ($pesquisa !== '') {
    $where[] = '(e.codigo_inventario LIKE :pesquisa OR e.designacao LIKE :pesquisa OR e.numero_serie LIKE :pesquisa OR e.marca LIKE :pesquisa OR e.modelo LIKE :pesquisa)';
    $params[':pesquisa'] = '%' . $pesquisa . '%';
}

if ($filtro_categoria !== '') {
    $where[] = 'e.categoria = :categoria';
    $params[':categoria'] = $filtro_categoria;
}

if ($filtro_localizacao !== '') {
    $where[] = "CONCAT(l.servico, ' - ', l.sala) = :localizacao";
    $params[':localizacao'] = $filtro_localizacao;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $categorias = $ligacao->query(
        "SELECT DISTINCT categoria FROM equipamentos WHERE estado = 'Inativo' AND categoria IS NOT NULL AND categoria <> '' ORDER BY categoria"
    )->fetchAll(PDO::FETCH_COLUMN);

    $localizacoes = $ligacao->query(
        "SELECT DISTINCT CONCAT(l.servico, ' - ', l.sala) AS localizacao
         FROM equipamentos e
         LEFT JOIN localizacoes l ON e.localizacao_id = l.id
         WHERE e.estado = 'Inativo' AND l.servico IS NOT NULL
         ORDER BY localizacao"
    )->fetchAll(PDO::FETCH_COLUMN);

    $stmt_resumo = $ligacao->query(
        "SELECT
            COUNT(*) AS total,
            COUNT(DISTINCT categoria) AS categorias,
            COUNT(DISTINCT localizacao_id) AS localizacoes,
            COALESCE(SUM(custo_aquisicao), 0) AS valor
         FROM equipamentos
         WHERE estado = 'Inativo'"
    );

    $resumo_db = $stmt_resumo->fetch(PDO::FETCH_ASSOC);
    if ($resumo_db) {
        $resumo = [
            'total' => (int) $resumo_db['total'],
            'categorias' => (int) $resumo_db['categorias'],
            'localizacoes' => (int) $resumo_db['localizacoes'],
            'valor' => (float) $resumo_db['valor']
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

    $stmt = $ligacao->prepare(
        "SELECT e.*, l.servico, l.sala
         FROM equipamentos e
         LEFT JOIN localizacoes l ON e.localizacao_id = l.id
         $where_sql
         ORDER BY e.codigo_inventario ASC
         LIMIT :limite OFFSET :offset"
    );

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }

    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar o arquivo.';
    $total_paginas = 1;
}

$ligacao = null;

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function moeda_arquivo($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' €';
}

function query_arquivo($alteracoes = [])
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

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 arquivo-page">
            <div class="page-head">
                <div>
                    <h2 class="page-title"><i class="fas fa-box-archive me-2"></i>Arquivo</h2>
                    <p class="page-subtitle">Equipamentos inativos ou retirados da utilização ativa.</p>
                </div>
                <a href="../equipamentos/lista.php" class="back-btn">
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar aos equipamentos
                </a>
            </div>

            <div class="summary-grid">
                <div class="summary-card primary">
                    <i class="fas fa-box-archive summary-icon"></i>
                    <div class="summary-label">Inativos</div>
                    <div class="summary-value"><?= h($resumo['total']) ?></div>
                    <div class="summary-help">No arquivo</div>
                </div>
                <div class="summary-card info">
                    <i class="fas fa-layer-group summary-icon"></i>
                    <div class="summary-label">Categorias</div>
                    <div class="summary-value"><?= h($resumo['categorias']) ?></div>
                    <div class="summary-help">Com equipamentos arquivados</div>
                </div>
                <div class="summary-card warning">
                    <i class="fas fa-location-dot summary-icon"></i>
                    <div class="summary-label">Localizações</div>
                    <div class="summary-value"><?= h($resumo['localizacoes']) ?></div>
                    <div class="summary-help">Último registo conhecido</div>
                </div>
                <div class="summary-card danger">
                    <i class="fas fa-euro-sign summary-icon"></i>
                    <div class="summary-label">Valor registado</div>
                    <div class="summary-value"><?= h(moeda_arquivo($resumo['valor'])) ?></div>
                    <div class="summary-help">Aquisição histórica</div>
                </div>
            </div>

            <div class="filters-card">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label">Pesquisar</label>
                        <input type="text" name="pesquisa" class="form-control" placeholder="Código, nome, marca ou série" value="<?= h($pesquisa) ?>">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Categoria</label>
                        <select name="categoria" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $categoria) : ?>
                                <option value="<?= h($categoria) ?>" <?= $filtro_categoria == $categoria ? 'selected' : '' ?>><?= h($categoria) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Localização</label>
                        <select name="localizacao" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($localizacoes as $localizacao) : ?>
                                <option value="<?= h($localizacao) ?>" <?= $filtro_localizacao == $localizacao ? 'selected' : '' ?>><?= h($localizacao) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 d-flex gap-2">
                        <button type="submit" class="filter-btn flex-fill">
                            <i class="fa-solid fa-filter"></i>
                            Filtrar
                        </button>
                        <a href="lista.php" class="btn btn-sm btn-outline-secondary flex-fill d-inline-flex align-items-center justify-content-center">Limpar</a>
                    </div>
                </form>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="alert alert-danger text-center"><?= h($erro) ?></div>
            <?php else : ?>
                <div class="content-card">
                    <?php if (count($resultados) == 0) : ?>
                        <div class="alert alert-info mb-0">Não existem equipamentos arquivados para os filtros selecionados.</div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>Código</th>
                                        <th>Equipamento</th>
                                        <th>Categoria</th>
                                        <th>Marca / Modelo</th>
                                        <th>Última localização</th>
                                        <th>Estado</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados as $equipamento) : ?>
                                        <tr>
                                            <td><span class="code-chip"><?= h($equipamento->codigo_inventario) ?></span></td>
                                            <td><span class="equipment-name"><?= h($equipamento->designacao) ?></span></td>
                                            <td><?= h($equipamento->categoria) ?></td>
                                            <td>
                                                <strong><?= h($equipamento->marca) ?></strong>
                                                <div class="muted-line"><?= h($equipamento->modelo) ?></div>
                                            </td>
                                            <td><?= !empty($equipamento->servico) ? h($equipamento->servico . ' - ' . $equipamento->sala) : '-' ?></td>
                                            <td><span class="estado-badge"><?= h($equipamento->estado) ?></span></td>
                                            <td>
                                                <a href="../equipamentos/detalhes.php?id=<?= h($equipamento->id) ?>" class="action-btn">
                                                    <i class="fa-solid fa-eye"></i>
                                                    Consultar
                                                </a>
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
                                        <a class="page-link" href="<?= query_arquivo(['pagina' => $i]) ?>"><?= $i ?></a>
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


