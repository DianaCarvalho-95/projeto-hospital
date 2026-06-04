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

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 5;
$offset = ($pagina - 1) * $registos_por_pagina;

$total_registos = 0;
$total_paginas = 0;

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $total_custo = $ligacao
        ->query("SELECT COALESCE(SUM(custo), 0) FROM manutencoes")
        ->fetchColumn();

    $custo_medio = $ligacao
        ->query("SELECT COALESCE(AVG(custo), 0) FROM manutencoes")
        ->fetchColumn();

    $total_manutencoes = $ligacao
        ->query("SELECT COUNT(*) FROM manutencoes")
        ->fetchColumn();

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

    $resultados_fornecedor = $ligacao
        ->query(
            "SELECT
                COALESCE(f.nome_empresa, 'Sem fornecedor') AS nome_empresa,
                COUNT(m.id) AS total,
                COALESCE(SUM(m.custo), 0) AS custo_total
             FROM manutencoes m
             LEFT JOIN fornecedores f ON m.fornecedor_id = f.id
             GROUP BY f.nome_empresa
             ORDER BY custo_total DESC"
        )
        ->fetchAll(PDO::FETCH_OBJ);

    $stmt_total = $ligacao->query("SELECT COUNT(*) FROM manutencoes");
    $total_registos = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_registos / $registos_por_pagina);

    $sql = "SELECT
                m.*,
                e.codigo_inventario,
                e.designacao,
                f.nome_empresa
            FROM manutencoes m
            INNER JOIN equipamentos e ON m.equipamento_id = e.id
            LEFT JOIN fornecedores f ON m.fornecedor_id = f.id
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
        padding-bottom: 20px;
    }

    .cost-card {
        border: none;
        border-radius: 18px;
        padding: 20px;
        color: #fff;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.10);
        min-height: 115px;
    }

    .cost-card h6 {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .cost-number {
        font-size: 1.9rem;
        font-weight: 700;
    }

    .cost-dark {
        background: linear-gradient(135deg, #1f2937, #111827);
    }

    .cost-blue {
        background: linear-gradient(135deg, #0d6efd, #084298);
    }

    .cost-green {
        background: linear-gradient(135deg, #198754, #0f5132);
    }

    .summary-box {
        border: none;
        border-radius: 18px;
        padding: 18px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
        height: 100%;
    }

    .summary-box h5 {
        font-weight: 700;
        margin-bottom: 12px;
    }

    .details-box {
        border: none;
        border-radius: 18px;
        padding: 18px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
    }

    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 18px;
        margin-bottom: 12px;
    }

    .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
        justify-content: center;
    }

    .pagination .page-link {
        color: #0d6efd;
        border-radius: 8px;
        margin: 2px;
    }

    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    .back-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 8px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4 cost-page">

            <h2>
                <i class="fa-solid fa-euro-sign me-2"></i>
                Estimativa de Custo
            </h2>

            <p class="text-muted">
                Análise dos custos associados às manutenções registadas.
            </p>

            <?php if (!empty($erro)) : ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php else : ?>

                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <div class="cost-card cost-dark">
                            <h6>Custo Total</h6>
                            <div class="cost-number">
                                <?= number_format($total_custo, 2, ',', '.') ?> €
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="cost-card cost-blue">
                            <h6>Custo Médio</h6>
                            <div class="cost-number">
                                <?= number_format($custo_medio, 2, ',', '.') ?> €
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="cost-card cost-green">
                            <h6>Total de Manutenções</h6>
                            <div class="cost-number">
                                <?= $total_manutencoes ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <div class="summary-box">
                            <h5>
                                <i class="fa-solid fa-screwdriver-wrench me-2"></i>
                                Custo por Tipo de Manutenção
                            </h5>

                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Total</th>
                                        <th>Custo Total</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($resultados_tipo as $linha) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($linha->tipo_manutencao) ?></td>
                                            <td><?= $linha->total ?></td>
                                            <td><?= number_format($linha->custo_total, 2, ',', '.') ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="summary-box">
                            <h5>
                                <i class="fa-solid fa-truck-medical me-2"></i>
                                Custo por Fornecedor
                            </h5>

                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-dark">
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

                <div class="details-box">

                    <h5>
                        <i class="fa-solid fa-list me-2"></i>
                        Detalhe de Custos
                    </h5>

                    <?php if (count($resultados) == 0) : ?>

                        <p class="text-muted">
                            Não existem custos registados.
                        </p>

                    <?php else : ?>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-dark">
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
                                            <td><?= htmlspecialchars($registo->tipo_manutencao) ?></td>
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

                        <?php if ($total_paginas > 1) : ?>

                            <nav class="pagination-wrapper">
                                <ul class="pagination pagination-sm">

                                    <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>

                                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="?pagina=<?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>

                                    <?php endfor; ?>

                                </ul>
                            </nav>

                        <?php endif; ?>

                        <div class="back-wrapper">
                            <a href="ferramentas.php" class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left me-1"></i>
                                Voltar
                            </a>
                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>