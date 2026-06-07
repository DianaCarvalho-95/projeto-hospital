<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];
$resultados_tipo = [];
$resultados_fornecedor = [];

$total_custo = 0;
$custo_medio = 0;
$total_manutencoes = 0;


/* Paginação da tabela de detalhe */
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 5;
$offset = ($pagina - 1) * $registos_por_pagina;

$total_registos = 0;
$total_paginas = 0;

try {


    /* Ligação à base de dados */
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    /* Indicadores principais */
    $total_custo = $ligacao
        ->query("SELECT COALESCE(SUM(custo), 0) FROM manutencoes")
        ->fetchColumn();

    $custo_medio = $ligacao
        ->query("SELECT COALESCE(AVG(custo), 0) FROM manutencoes")
        ->fetchColumn();

    $total_manutencoes = $ligacao
        ->query("SELECT COUNT(*) FROM manutencoes")
        ->fetchColumn();


    /* Custo agrupado por tipo de manutenção */
    $resultados_tipo = $ligacao
        ->query(
            "SELECT
                tipo_manutencao,
                COUNT(*) AS total,
                COALESCE(SUM(custo), 0) AS custo_total
             FROM manutencoes
             GROUP BY tipo_manutencao
             ORDER BY custo_total DESC"
        )
        ->fetchAll(PDO::FETCH_OBJ);



    /* Custo agrupado por fornecedor */
    $resultados_fornecedor = $ligacao
        ->query(
            "SELECT
                COALESCE(f.nome_empresa, 'Sem fornecedor') AS nome_empresa,
                COUNT(m.id) AS total,
                COALESCE(SUM(m.custo), 0) AS custo_total
             FROM manutencoes m
             LEFT JOIN fornecedores f
                ON m.fornecedor_id = f.id
             GROUP BY f.nome_empresa
             ORDER BY custo_total DESC"
        )
        ->fetchAll(PDO::FETCH_OBJ);

    /* Total de registos para paginação */
    $stmt_total = $ligacao->query("SELECT COUNT(*) FROM manutencoes");
    $total_registos = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_registos / $registos_por_pagina);

    
    /* Detalhe de custos com paginação */
    $sql = "SELECT
                m.*,
                e.codigo_inventario,
                e.designacao,
                f.nome_empresa
            FROM manutencoes m
            INNER JOIN equipamentos e
                ON m.equipamento_id = e.id
            LEFT JOIN fornecedores f
                ON m.fornecedor_id = f.id
            ORDER BY m.custo DESC
            LIMIT :limite OFFSET :offset";

    $stmt = $ligacao->prepare($sql);
    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar a estimativa de custos.';
}

$ligacao = null;

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .cost-page {
        background: #f5f7fa;
        padding: 24px;
    }

    .page-title {
        color: #1E3A5F;
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
    }

    .exportar-btn {
        background: #e8f5ee;
        border: 1px solid #cfead9;
        color: #198754;
        border-radius: 10px;
        font-weight: 600;
    }

    .exportar-btn:hover {
        background: #d9f0e3;
        border-color: #badfc9;
        color: #146c43;
    }

    .summary-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-left: 5px solid #2F5D8A;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        height: 100%;
    }

    .summary-title {
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .summary-value {
        color: #0f172a;
        font-size: 1.6rem;
        font-weight: 700;
    }

    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    .section-title {
        color: #1E3A5F;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 14px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 8px;
    }

    .custom-table {
        font-size: 0.88rem;
    }

    .custom-table thead th {
        background: #2F5D8A;
        color: #ffffff;
        border-color: #2F5D8A;
        white-space: nowrap;
        vertical-align: middle;
    }

    .custom-table tbody td {
        vertical-align: middle;
        border-color: #eef2f7;
    }

    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 22px;
    }

    .pagination .page-link {
        color: #2F5D8A;
        border-radius: 8px;
        margin: 0 2px;
        font-weight: 600;
    }

    .pagination .page-item.active .page-link {
        background-color: #2F5D8A;
        border-color: #2F5D8A;
        color: #ffffff;
    }

    .btn-voltar-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 16px;
    }

    .btn-voltar-custom {
        background: #eef2f7;
        border: 1px solid #dbe3ec;
        color: #475569;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 18px;
        text-decoration: none;
    }

    .btn-voltar-custom:hover {
        background: #e2e8f0;
        color: #334155;
    }

    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }

    .mensagem-info {
        background: #edf4ff;
        color: #2F5D8A;
        border: 1px solid #d6e7ff;
        border-radius: 10px;
        padding: 12px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 cost-page">

            <div class="mb-4">

                <div class="d-flex justify-content-between align-items-start mb-4">

                    <div>

                        <h2 class="page-title mb-1">
                            <i class="fa-solid fa-euro-sign me-2"></i>
                            Estimativa de Custo
                        </h2>

                        <p class="page-subtitle mb-0">
                            Análise dos custos associados às manutenções registadas.
                        </p>

                    </div>

                    <a href="exportar-custos.php" class="btn btn-sm exportar-btn">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Exportar Excel
                    </a>

                </div>

                <p class="page-subtitle mb-0">
                    Análise dos custos associados às manutenções registadas.
                </p>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php else : ?>

                <div class="row g-3 mb-4">

                    <!-- Custo Total -->
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="summary-title">Custo Total</div>
                            <div class="summary-value">
                                <?= number_format($total_custo, 2, ',', '.') ?> €
                            </div>
                        </div>
                    </div>

                    <!-- Custo Médio -->
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="summary-title">Custo Médio</div>
                            <div class="summary-value">
                                <?= number_format($custo_medio, 2, ',', '.') ?> €
                            </div>
                        </div>
                    </div>

                    <!-- Total de Manutenções -->
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="summary-title">Total de Manutenções</div>
                            <div class="summary-value">
                                <?= $total_manutencoes ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row g-3 mb-4">

                    <!-- Custo por Tipo de Manutenção -->
                    <div class="col-md-6">
                        <div class="content-card">

                            <h5 class="section-title">
                                <i class="fa-solid fa-screwdriver-wrench me-2"></i>
                                Custo por Tipo de Manutenção
                            </h5>

                            <div class="table-responsive">

                                <table class="table table-hover align-middle mb-0 custom-table">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Total</th>
                                            <th>Custo Total</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($resultados_tipo as $linha) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars($linha->tipo_manutencao ?? '-') ?></td>
                                                <td><?= $linha->total ?></td>
                                                <td><?= number_format($linha->custo_total, 2, ',', '.') ?> €</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                            </div>

                        </div>
                    </div>

                    <!-- Custo por Fornecedor -->
                    <div class="col-md-6">
                        <div class="content-card">

                            <h5 class="section-title">
                                <i class="fa-solid fa-truck-medical me-2"></i>
                                Custo por Fornecedor
                            </h5>

                            <div class="table-responsive">

                                <table class="table table-hover align-middle mb-0 custom-table">
                                    <thead>
                                        <tr>
                                            <th>Fornecedor</th>
                                            <th>Total</th>
                                            <th>Custo Total</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($resultados_fornecedor as $linha) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars($linha->nome_empresa) ?></td>
                                                <td><?= $linha->total ?></td>
                                                <td><?= number_format($linha->custo_total, 2, ',', '.') ?> €</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                            </div>

                        </div>
                    </div>

                </div>

                <!-- Detalhe de Custos -->
                <div class="content-card">

                    <h5 class="section-title">
                        <i class="fa-solid fa-list me-2"></i>
                        Detalhe de Custos
                    </h5>

                    <?php if (count($resultados) == 0) : ?>

                        <div class="mensagem-info">
                            Não existem custos registados.
                        </div>

                    <?php else : ?>

                        <div class="table-responsive">

                            <table class="table table-hover align-middle mb-0 custom-table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Equipamento</th>
                                        <th>Tipo</th>
                                        <th>Fornecedor</th>
                                        <th>Data</th>
                                        <th>Custo</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($resultados as $registo) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($registo->codigo_inventario) ?></td>
                                            <td><?= htmlspecialchars($registo->designacao) ?></td>
                                            <td><?= htmlspecialchars($registo->tipo_manutencao ?? '-') ?></td>
                                            <td><?= htmlspecialchars($registo->nome_empresa ?? '-') ?></td>
                                            <td>
                                                <?= !empty($registo->data_manutencao)
                                                    ? date('d/m/Y', strtotime($registo->data_manutencao))
                                                    : '-' ?>
                                            </td>
                                            <td><?= number_format($registo->custo, 2, ',', '.') ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>

                    <?php endif; ?>

                    <?php if ($total_paginas > 1) : ?>

                        <div class="pagination-wrapper">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">

                                    <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>

                                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="?pagina=<?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>

                                    <?php endfor; ?>

                                </ul>
                            </nav>
                        </div>

                    <?php endif; ?>

                    <div class="btn-voltar-wrapper">
                        <a href="ferramentas.php" class="btn-voltar-custom">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar
                        </a>
                    </div>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>