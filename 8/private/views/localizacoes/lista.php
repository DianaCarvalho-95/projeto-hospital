<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];
$edificios = [];
$pisos = [];
$servicos = [];
$salas = [];
$resumo = [
    'total' => 0,
    'equipamentos' => 0,
    'edificios' => 0,
    'media' => 0
];

$filtro_edificio = isset($_GET['edificio']) ? trim($_GET['edificio']) : '';
$filtro_piso = isset($_GET['piso']) ? trim($_GET['piso']) : '';
$filtro_servico = isset($_GET['servico']) ? trim($_GET['servico']) : '';
$filtro_sala = isset($_GET['sala']) ? trim($_GET['sala']) : '';

$where = [];
$params = [];

if ($filtro_edificio !== '') {
    $where[] = 'l.edificio = :edificio';
    $params[':edificio'] = $filtro_edificio;
}

if ($filtro_piso !== '') {
    $where[] = 'l.piso = :piso';
    $params[':piso'] = $filtro_piso;
}

if ($filtro_servico !== '') {
    $where[] = 'l.servico = :servico';
    $params[':servico'] = $filtro_servico;
}

if ($filtro_sala !== '') {
    $where[] = 'l.sala = :sala';
    $params[':sala'] = $filtro_sala;
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
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

    $stmt_pisos = $ligacao->prepare(
        "SELECT DISTINCT piso
         FROM localizacoes
         WHERE piso IS NOT NULL AND piso <> ''
         AND (:edificio = '' OR edificio = :edificio)
         ORDER BY piso"
    );
    $stmt_pisos->execute([':edificio' => $filtro_edificio]);
    $pisos = $stmt_pisos->fetchAll(PDO::FETCH_COLUMN);

    $stmt_servicos = $ligacao->prepare(
        "SELECT DISTINCT servico
         FROM localizacoes
         WHERE servico IS NOT NULL AND servico <> ''
         AND (:edificio = '' OR edificio = :edificio)
         AND (:piso = '' OR piso = :piso)
         ORDER BY servico"
    );
    $stmt_servicos->execute([':edificio' => $filtro_edificio, ':piso' => $filtro_piso]);
    $servicos = $stmt_servicos->fetchAll(PDO::FETCH_COLUMN);

    $stmt_salas = $ligacao->prepare(
        "SELECT DISTINCT sala
         FROM localizacoes
         WHERE sala IS NOT NULL AND sala <> ''
         AND (:edificio = '' OR edificio = :edificio)
         AND (:piso = '' OR piso = :piso)
         AND (:servico = '' OR servico = :servico)
         ORDER BY sala"
    );
    $stmt_salas->execute([':edificio' => $filtro_edificio, ':piso' => $filtro_piso, ':servico' => $filtro_servico]);
    $salas = $stmt_salas->fetchAll(PDO::FETCH_COLUMN);

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
                    <div class="export-actions">
                        <a href="exportar-localizacoes.php?formato=excel" class="btn btn-sm exportar-btn">
                            <i class="fa-solid fa-file-excel me-1"></i>
                            Excel
                        </a>
                        <a href="exportar-localizacoes.php?formato=csv" class="btn btn-sm exportar-btn exportar-btn-secondary">
                            <i class="fa-solid fa-file-csv me-1"></i>
                            CSV
                        </a>
                        <a href="exportar-localizacoes.php?formato=imprimir" target="_blank" class="btn btn-sm exportar-btn exportar-btn-secondary">
                            <i class="fa-solid fa-file-pdf me-1"></i>
                            PDF
                        </a>
                    </div>

                    <a href="novo-edificio.php" class="btn btn-sm novo-btn">
                        <i class="fa-solid fa-building me-1"></i>
                        Novo edifício
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

                <form method="get" class="filters-card" id="locationFilters">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-2 col-md-4">
                            <div class="filter-label">Edifício</div>
                            <select name="edificio" class="form-select auto-submit-filter">
                                <option value="">Todos</option>
                                <?php foreach ($edificios as $edificio) : ?>
                                    <option value="<?= h($edificio) ?>" <?= $filtro_edificio === $edificio ? 'selected' : '' ?>><?= h($edificio) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <div class="filter-label">Piso</div>
                            <select name="piso" class="form-select auto-submit-filter">
                                <option value="">Todos</option>
                                <?php foreach ($pisos as $piso) : ?>
                                    <option value="<?= h($piso) ?>" <?= $filtro_piso === $piso ? 'selected' : '' ?>><?= h($piso) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-4">
                            <div class="filter-label">Serviço</div>
                            <select name="servico" class="form-select auto-submit-filter">
                                <option value="">Todos</option>
                                <?php foreach ($servicos as $servico) : ?>
                                    <option value="<?= h($servico) ?>" <?= $filtro_servico === $servico ? 'selected' : '' ?>><?= h($servico) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <div class="filter-label">Sala</div>
                            <select name="sala" class="form-select auto-submit-filter">
                                <option value="">Todas</option>
                                <?php foreach ($salas as $sala) : ?>
                                    <option value="<?= h($sala) ?>" <?= $filtro_sala === $sala ? 'selected' : '' ?>><?= h($sala) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-8 d-flex gap-2">
                            <button type="submit" class="btn btn-primary filter-btn">
                                <i class="fa-solid fa-filter me-1"></i>
                                Filtrar
                            </button>
                            <a href="lista.php" class="btn clear-btn filter-btn">Limpar</a>
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









