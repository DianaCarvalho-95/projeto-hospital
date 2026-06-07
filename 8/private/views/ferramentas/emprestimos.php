<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'data';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'asc' ? 'asc' : 'desc';

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 6;

$colunas_permitidas = [
    'codigo' => 'e.codigo_inventario',
    'equipamento' => 'e.designacao',
    'origem' => 'ep.servico_origem',
    'destino' => 'ep.servico_destino',
    'data' => 'ep.data_emprestimo',
    'prevista' => 'ep.data_prevista_devolucao',
    'devolucao' => 'ep.data_devolucao',
    'responsavel' => 'ep.responsavel',
    'estado' => 'ep.estado'
];

$coluna_sql = isset($colunas_permitidas[$ordenar])
    ? $colunas_permitidas[$ordenar]
    : 'ep.data_emprestimo';

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT
                ep.*,
                e.codigo_inventario,
                e.designacao
            FROM emprestimos ep
            INNER JOIN equipamentos e
                ON ep.equipamento_id = e.id
            ORDER BY $coluna_sql $direcao";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar os empréstimos.';
}

$ligacao = null;

function estado_visual($emprestimo)
{
    if (
        $emprestimo->estado == 'Ativo' &&
        !empty($emprestimo->data_prevista_devolucao) &&
        $emprestimo->data_prevista_devolucao < date('Y-m-d')
    ) {
        return 'Em atraso';
    }

    return $emprestimo->estado;
}

function classe_estado($estado)
{
    if ($estado == 'Devolvido') {
        return 'badge-devolvido';
    }

    if ($estado == 'Em atraso') {
        return 'badge-atraso';
    }

    return 'badge-ativo';
}

function link_ordenacao($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo . '&direcao=' . $nova_direcao;
}

function icone_ordenacao($campo, $ordenar, $direcao)
{
    if ($ordenar != $campo) {
        return '<i class="fa-solid fa-sort ms-1"></i>';
    }

    if ($direcao == 'asc') {
        return '<i class="fa-solid fa-sort-up ms-1"></i>';
    }

    return '<i class="fa-solid fa-sort-down ms-1"></i>';
}

$total = count($resultados);
$ativos = 0;
$devolvidos = 0;
$atraso = 0;

foreach ($resultados as $item) {

    $estado = estado_visual($item);

    if ($estado == 'Ativo') {
        $ativos++;
    } elseif ($estado == 'Devolvido') {
        $devolvidos++;
    } elseif ($estado == 'Em atraso') {
        $atraso++;
    }
}

$total_paginas = ceil($total / $registos_por_pagina);
$offset = ($pagina - 1) * $registos_por_pagina;
$resultados_pagina = array_slice($resultados, $offset, $registos_por_pagina);

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .emprestimos-page {
        background: #f5f7fa;
        min-height: 100vh;
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

    .btn-novo-custom {
        background: #2F5D8A;
        border: 1px solid #2F5D8A;
        color: #ffffff;
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 16px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-novo-custom:hover {
        background: #1E3A5F;
        color: #ffffff;
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

    .estado-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-ativo {
        background: #fff6dd;
        color: #c79200;
    }

    .badge-devolvido {
        background: #e8f5ee;
        color: #198754;
    }

    .badge-atraso {
        background: #fdeaea;
        color: #dc3545;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all 0.2s ease;
        white-space: nowrap;
        min-width: 105px;
    }

    .action-devolver {
        background: #edf4ff;
        color: #2F5D8A;
        border-color: #d6e7ff;
    }

    .action-devolver:hover {
        background: #dcecff;
        color: #1E3A5F;
    }

    .action-disabled {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
        cursor: not-allowed;
        pointer-events: none;
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

        <main class="col-md-9 col-lg-10 emprestimos-page">

            <div class="d-flex justify-content-between align-items-start mb-4">

                <div>
                    <h2 class="page-title mb-1">
                        <i class="fa-solid fa-handshake me-2"></i>
                        Empréstimos entre Serviços
                    </h2>

                    <p class="page-subtitle mb-0">
                        Controlo de equipamentos emprestados entre serviços hospitalares.
                    </p>
                </div>

                <a href="novo-emprestimo.php" class="btn-novo-custom">
                    <i class="fa-solid fa-plus me-1"></i>
                    Novo Empréstimo
                </a>

            </div>

             <!-- CARTÕES DO TOPO -->
            <div class="row g-3 mb-4">

                <!-- TOTAL -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Total</div>
                        <div class="summary-value"><?= $total ?></div>
                    </div>
                </div>

                 <!-- ATIVOS -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Ativos</div>
                        <div class="summary-value"><?= $ativos ?></div>
                    </div>
                </div>

                 <!-- DEVOLVIDOS -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Devolvidos</div>
                        <div class="summary-value"><?= $devolvidos ?></div>
                    </div>
                </div>

                 <!-- EM ATRASO -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Em atraso</div>
                        <div class="summary-value"><?= $atraso ?></div>
                    </div>
                </div>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php elseif (count($resultados) == 0) : ?>

                <div class="mensagem-info">
                    Não existem empréstimos registados.
                </div>

            <?php else : ?>

                <div class="content-card">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle mb-0 custom-table">

                            <thead>
                                <tr>
                                    <th>
                                         <!-- ORDENAÇÃO -->
                                        <a href="<?= link_ordenacao('codigo', $ordenar, $direcao) ?>">
                                            Código <?= icone_ordenacao('codigo', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('equipamento', $ordenar, $direcao) ?>">
                                            Equipamento <?= icone_ordenacao('equipamento', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('origem', $ordenar, $direcao) ?>">
                                            Origem <?= icone_ordenacao('origem', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('destino', $ordenar, $direcao) ?>">
                                            Destino <?= icone_ordenacao('destino', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('data', $ordenar, $direcao) ?>">
                                            Empréstimo <?= icone_ordenacao('data', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('prevista', $ordenar, $direcao) ?>">
                                            Prev. Devolução <?= icone_ordenacao('prevista', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('devolucao', $ordenar, $direcao) ?>">
                                            Devolução <?= icone_ordenacao('devolucao', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('responsavel', $ordenar, $direcao) ?>">
                                            Responsável <?= icone_ordenacao('responsavel', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('estado', $ordenar, $direcao) ?>">
                                            Estado <?= icone_ordenacao('estado', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>Observações</th>

                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($resultados_pagina as $emprestimo) : ?>

                                    <?php
                                        $estado = estado_visual($emprestimo);
                                        $classe = classe_estado($estado);
                                    ?>

                                    <tr>
                                        <td><?= htmlspecialchars($emprestimo->codigo_inventario) ?></td>

                                        <td><?= htmlspecialchars($emprestimo->designacao) ?></td>

                                        <td><?= htmlspecialchars($emprestimo->servico_origem) ?></td>

                                        <td><?= htmlspecialchars($emprestimo->servico_destino) ?></td>

                                        <td>
                                            <?= !empty($emprestimo->data_emprestimo)
                                                ? date('d/m/Y', strtotime($emprestimo->data_emprestimo))
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <?= !empty($emprestimo->data_prevista_devolucao)
                                                ? date('d/m/Y', strtotime($emprestimo->data_prevista_devolucao))
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <?= !empty($emprestimo->data_devolucao)
                                                ? date('d/m/Y', strtotime($emprestimo->data_devolucao))
                                                : '-' ?>
                                        </td>

                                        <td><?= htmlspecialchars($emprestimo->responsavel ?? '-') ?></td>

                                        <td>
                                            <span class="estado-badge <?= $classe ?>">
                                                <?= htmlspecialchars($estado) ?>
                                            </span>
                                        </td>

                                        <td><?= htmlspecialchars($emprestimo->observacoes ?? '-') ?></td>

                                        <td class="text-center">
                                            <?php if ($emprestimo->estado != 'Devolvido') : ?>

                                                <a href="devolver.php?id=<?= $emprestimo->id ?>"
                                                   class="action-btn action-devolver">
                                                    <i class="fa-solid fa-arrow-rotate-left"></i>
                                                    Devolver
                                                </a>

                                            <?php else : ?>

                                                <span class="action-btn action-disabled">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Devolvido
                                                </span>

                                            <?php endif; ?>
                                        </td>
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