<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];


/* Ordenação da tabela */
$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'data';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'asc' ? 'asc' : 'desc';


/* Paginação */
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 8;


/* Colunas permitidas para ordenação */
$colunas_permitidas = [
    'codigo' => 'e.codigo_inventario',
    'equipamento' => 'e.designacao',
    'origem' => 'mv.local_origem',
    'destino' => 'mv.local_destino',
    'data' => 'mv.data_movimentacao',
    'responsavel' => 'mv.responsavel',
    'motivo' => 'mv.motivo'
];

$coluna_sql = isset($colunas_permitidas[$ordenar])
    ? $colunas_permitidas[$ordenar]
    : 'mv.data_movimentacao';

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


    /* Consulta das movimentações dos equipamentos */
    $sql = "SELECT
                mv.*,
                e.codigo_inventario,
                e.designacao
            FROM movimentacoes mv
            INNER JOIN equipamentos e
                ON mv.equipamento_id = e.id
            ORDER BY $coluna_sql $direcao";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar o histórico de movimentações.';
}

$ligacao = null;


/* Gera o link de ordenação */
function link_ordenacao_movimentacoes($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo . '&direcao=' . $nova_direcao;
}

/* Mostra o ícone da ordenação */
function icone_ordenacao_movimentacoes($campo, $ordenar, $direcao)
{
    if ($ordenar != $campo) {
        return '<i class="fa-solid fa-sort ms-1"></i>';
    }

    if ($direcao == 'asc') {
        return '<i class="fa-solid fa-sort-up ms-1"></i>';
    }

    return '<i class="fa-solid fa-sort-down ms-1"></i>';
}


/* Contadores dos cartões superiores */
$total_movimentacoes = count($resultados);
$total_ano = 0;
$total_mes = 0;
$total_30_dias = 0;

foreach ($resultados as $movimentacao) {

    if (!empty($movimentacao->data_movimentacao)) {

        $data_movimentacao = strtotime($movimentacao->data_movimentacao);

        if (date('Y', $data_movimentacao) == date('Y')) {
            $total_ano++;
        }

        if (
            date('Y', $data_movimentacao) == date('Y') &&
            date('m', $data_movimentacao) == date('m')
        ) {
            $total_mes++;
        }

        if ($movimentacao->data_movimentacao >= date('Y-m-d', strtotime('-30 days'))) {
            $total_30_dias++;
        }
    }
}


/* Paginação dos resultados */
$total_registos = count($resultados);
$total_paginas = ceil($total_registos / $registos_por_pagina);
$offset = ($pagina - 1) * $registos_por_pagina;
$resultados_pagina = array_slice($resultados, $offset, $registos_por_pagina);

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>


<style>
    .historico-page {
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
        font-size: 1.8rem;
        font-weight: 700;
    }

    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
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

    .custom-table thead th a {
        color: #ffffff;
        text-decoration: none;
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
        margin-bottom: 16px;
    }
</style>


<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 historico-page">

            <div class="d-flex justify-content-between align-items-start mb-4">

                <div>

                    <h2 class="page-title mb-1">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>
                        Histórico de Movimentações
                    </h2>

                    <p class="page-subtitle mb-0">
                        Consulta das transferências e movimentações dos equipamentos entre serviços hospitalares.
                    </p>

                </div>

                <a href="exportar-historico.php"
                    class="btn btn-sm exportar-btn">
                    <i class="fa-solid fa-file-excel me-1"></i>
                    Exportar Excel
                </a>

            </div>

            <div class="row g-3 mb-4">

                <!-- MOVIMENTAÇÕES TOTAIS -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Movimentações Totais</div>
                        <div class="summary-value"><?= $total_movimentacoes ?></div>
                    </div>
                </div>

                <!-- ESTE ANO -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Este ano</div>
                        <div class="summary-value"><?= $total_ano ?></div>
                    </div>
                </div>

                <!-- ESTE MÊS -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Este mês</div>
                        <div class="summary-value"><?= $total_mes ?></div>
                    </div>
                </div>

                <!-- ÚLTIMOS 30 DIAS -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Últimos 30 dias</div>
                        <div class="summary-value"><?= $total_30_dias ?></div>
                    </div>
                </div>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php elseif ($total_registos == 0) : ?>

                <div class="mensagem-info">
                    Não existem movimentações para apresentar.
                </div>

            <?php else : ?>

                <div class="content-card">

                    <p class="page-subtitle mb-3">
                        Total: <?= $total_registos ?> registo(s)
                    </p>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle mb-0 custom-table">

                            <!-- ORDENAÇÃO DE LISTAS -->
                            <thead>
                                <tr>
                                    <th>
                                        <a href="<?= link_ordenacao_movimentacoes('codigo', $ordenar, $direcao) ?>">
                                            Código <?= icone_ordenacao_movimentacoes('codigo', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_movimentacoes('equipamento', $ordenar, $direcao) ?>">
                                            Equipamento <?= icone_ordenacao_movimentacoes('equipamento', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_movimentacoes('origem', $ordenar, $direcao) ?>">
                                            Origem <?= icone_ordenacao_movimentacoes('origem', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_movimentacoes('destino', $ordenar, $direcao) ?>">
                                            Destino <?= icone_ordenacao_movimentacoes('destino', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_movimentacoes('data', $ordenar, $direcao) ?>">
                                            Data <?= icone_ordenacao_movimentacoes('data', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_movimentacoes('responsavel', $ordenar, $direcao) ?>">
                                            Responsável <?= icone_ordenacao_movimentacoes('responsavel', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_movimentacoes('motivo', $ordenar, $direcao) ?>">
                                            Motivo <?= icone_ordenacao_movimentacoes('motivo', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>Observações</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($resultados_pagina as $movimentacao) : ?>

                                    <tr>
                                        <td><?= htmlspecialchars($movimentacao->codigo_inventario) ?></td>

                                        <td><?= htmlspecialchars($movimentacao->designacao) ?></td>

                                        <td><?= htmlspecialchars($movimentacao->local_origem) ?></td>

                                        <td><?= htmlspecialchars($movimentacao->local_destino) ?></td>

                                        <td>
                                            <?= !empty($movimentacao->data_movimentacao)
                                                ? date('d/m/Y', strtotime($movimentacao->data_movimentacao))
                                                : '-' ?>
                                        </td>

                                        <td><?= htmlspecialchars($movimentacao->responsavel ?? '-') ?></td>

                                        <td><?= htmlspecialchars($movimentacao->motivo ?? '-') ?></td>

                                        <td><?= htmlspecialchars($movimentacao->observacoes ?? '-') ?></td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <?php if ($total_paginas > 1) : ?>

                    <div class="pagination-wrapper">
                        <nav>
                            <ul class="pagination pagination-sm mb-0">

                                <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>

                                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                        <a class="page-link"
                                            href="?pagina=<?= $i ?>&ordenar=<?= urlencode($ordenar) ?>&direcao=<?= urlencode($direcao) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>

                                <?php endfor; ?>

                            </ul>
                        </nav>
                    </div>

                <?php endif; ?>

            <?php endif; ?>

            <div class="btn-voltar-wrapper">
                <a href="ferramentas.php" class="btn-voltar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>
            </div>


        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>