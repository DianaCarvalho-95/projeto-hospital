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

<style>
    .fornecedores-page {
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

    .supplier-main {
        font-weight: 800;
        color: #0f172a;
    }

    .supplier-sub {
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
                    <a href="exportar-fornecedores.php" class="btn btn-sm exportar-btn">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Exportar Excel
                    </a>

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
