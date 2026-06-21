<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];
$tipos = [];
$resumo = [
    'total' => 0,
    'com_equipamentos' => 0,
    'tipos' => 0,
    'equipamentos' => 0
];

$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$filtro_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';

$where = [];
$params = [];

if ($pesquisa !== '') {
    $where[] = '(f.nome_empresa LIKE :pesquisa OR f.nif LIKE :pesquisa OR f.email LIKE :pesquisa OR f.pessoa_contacto LIKE :pesquisa)';
    $params[':pesquisa'] = '%' . $pesquisa . '%';
}

if ($filtro_tipo !== '') {
    $where[] = 'f.tipo_fornecedor = :tipo';
    $params[':tipo'] = $filtro_tipo;
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tipos = $ligacao->query(
        "SELECT DISTINCT tipo_fornecedor
         FROM fornecedores
         WHERE tipo_fornecedor IS NOT NULL AND tipo_fornecedor <> ''
         ORDER BY tipo_fornecedor"
    )->fetchAll(PDO::FETCH_COLUMN);

    $stmt_resumo = $ligacao->query(
        "SELECT
            COUNT(DISTINCT f.id) AS total,
            COUNT(DISTINCT CASE WHEN e.id IS NOT NULL THEN f.id END) AS com_equipamentos,
            COUNT(DISTINCT f.tipo_fornecedor) AS tipos,
            COUNT(e.id) AS equipamentos
         FROM fornecedores f
         LEFT JOIN equipamentos e ON e.fornecedor_id = f.id"
    );
    $resumo_db = $stmt_resumo->fetch(PDO::FETCH_ASSOC);
    if ($resumo_db) {
        $resumo = [
            'total' => (int) $resumo_db['total'],
            'com_equipamentos' => (int) $resumo_db['com_equipamentos'],
            'tipos' => (int) $resumo_db['tipos'],
            'equipamentos' => (int) $resumo_db['equipamentos']
        ];
    }

    $stmt = $ligacao->prepare(
        "SELECT
            f.*,
            COUNT(e.id) AS total_equipamentos
         FROM fornecedores f
         LEFT JOIN equipamentos e ON e.fornecedor_id = f.id
         $where_sql
         GROUP BY f.id
         ORDER BY f.nome_empresa"
    );

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }

    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar os fornecedores.';
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

        <main class="col-md-9 col-lg-10 fornecedores-page">
            <div class="d-flex justify-content-between align-items-start mb-3 gap-3">
                <div>
                    <h2 class="page-title mb-1">
                        <i class="fas fa-truck-medical me-2"></i>
                        Listagem de Fornecedores
                    </h2>
                    <p class="page-subtitle">Gestão e consulta dos fornecedores associados ao parque tecnológico hospitalar.</p>
                </div>

                <div class="d-flex gap-2">
                    <div class="export-actions">
                        <a href="exportar-fornecedores.php?formato=excel" class="btn btn-sm exportar-btn">
                            <i class="fa-solid fa-file-excel me-1"></i>
                            Excel
                        </a>
                        <a href="exportar-fornecedores.php?formato=csv" class="btn btn-sm exportar-btn exportar-btn-secondary">
                            <i class="fa-solid fa-file-csv me-1"></i>
                            CSV
                        </a>
                        <a href="exportar-fornecedores.php?formato=imprimir" target="_blank" class="btn btn-sm exportar-btn exportar-btn-secondary">
                            <i class="fa-solid fa-file-pdf me-1"></i>
                            PDF
                        </a>
                    </div>

                    <a href="novo.php" class="btn btn-sm novo-btn">
                        <i class="fa-solid fa-plus me-1"></i>
                        Novo fornecedor
                    </a>
                </div>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="mensagem-erro-custom text-center"><?= h($erro) ?></div>
            <?php else : ?>
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-label">Fornecedores</div>
                        <div class="summary-value"><?= $resumo['total'] ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Com equipamentos</div>
                        <div class="summary-value"><?= $resumo['com_equipamentos'] ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Tipos</div>
                        <div class="summary-value"><?= $resumo['tipos'] ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Equipamentos associados</div>
                        <div class="summary-value"><?= $resumo['equipamentos'] ?></div>
                    </div>
                </div>

                <form method="get" class="filters-card">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-5">
                            <div class="filter-label">Pesquisa</div>
                            <input type="text" name="pesquisa" class="form-control" value="<?= h($pesquisa) ?>" placeholder="Pesquisar por empresa, NIF, email ou contacto">
                        </div>
                        <div class="col-lg-3">
                            <div class="filter-label">Tipo</div>
                            <select name="tipo" class="form-select">
                                <option value="">Todos os tipos</option>
                                <?php foreach ($tipos as $tipo) : ?>
                                    <option value="<?= h($tipo) ?>" <?= $filtro_tipo === $tipo ? 'selected' : '' ?>><?= h($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary filter-btn">
                                <i class="fa-solid fa-magnifying-glass me-1"></i>
                                Filtrar
                            </button>
                            <a href="lista.php" class="btn clear-btn filter-btn">Limpar</a>
                        </div>
                    </div>
                </form>

                <?php if (count($resultados) == 0) : ?>
                    <div class="alert alert-info">Não existem fornecedores para os filtros selecionados.</div>
                <?php else : ?>
                    <div class="content-card">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>Fornecedor</th>
                                        <th>NIF</th>
                                        <th>Contacto</th>
                                        <th>Tipo</th>
                                        <th>Equipamentos</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados as $fornecedor) : ?>
                                        <tr>
                                            <td>
                                                <div class="supplier-main"><?= h($fornecedor->nome_empresa) ?></div>
                                                <div class="supplier-sub"><?= h($fornecedor->morada ?: 'Morada não definida') ?></div>
                                            </td>
                                            <td><?= h($fornecedor->nif) ?></td>
                                            <td>
                                                <div><?= h($fornecedor->email) ?></div>
                                                <div class="supplier-sub"><?= h($fornecedor->telefone) ?></div>
                                            </td>
                                            <td><?= h($fornecedor->tipo_fornecedor) ?></td>
                                            <td><span class="count-pill"><?= (int) $fornecedor->total_equipamentos ?></span></td>
                                            <td>
                                                <a href="detalhes.php?id=<?= $fornecedor->id ?>" class="action-btn action-consultar">
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



