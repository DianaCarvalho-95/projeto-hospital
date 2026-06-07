<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

/* Ordenação da tabela */
$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'codigo';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'desc' ? 'desc' : 'asc';


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
    'tipo' => 'gc.tipo_contrato',
    'entidade' => 'gc.entidade_responsavel',
    'inicio' => 'gc.data_inicio',
    'fim' => 'gc.data_fim',
    'periodicidade' => 'gc.periodicidade',
    'estado' => 'gc.data_fim'
];

$coluna_sql = isset($colunas_permitidas[$ordenar])
    ? $colunas_permitidas[$ordenar]
    : 'e.codigo_inventario';

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

    /* Consulta das garantias e contratos associados aos equipamentos */
    $sql = "SELECT
                e.id AS equipamento_id,
                e.codigo_inventario,
                e.designacao,
                gc.tipo_contrato,
                gc.entidade_responsavel,
                gc.data_inicio,
                gc.data_fim,
                gc.periodicidade,
                gc.existe_contrato
            FROM equipamentos e
            LEFT JOIN garantias_contratos gc
                ON gc.equipamento_id = e.id
            ORDER BY $coluna_sql $direcao";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar as garantias e contratos.';
}

$ligacao = null;

/* Calcula o estado da garantia/contrato */
function calcular_estado($registo)
{
    if (empty($registo->tipo_contrato)) {
        return ['estado' => 'Sem registo', 'classe' => 'badge-sem-registo'];
    }

    if (!empty($registo->data_fim)) {
        $hoje = date('Y-m-d');

        if ($registo->data_fim < $hoje) {
            return ['estado' => 'Expirado', 'classe' => 'badge-expirado'];
        }

        if ($registo->data_fim <= date('Y-m-d', strtotime('+30 days'))) {
            return ['estado' => 'A expirar', 'classe' => 'badge-expirar'];
        }

        return ['estado' => 'Ativo', 'classe' => 'badge-ativo'];
    }

    return ['estado' => 'Sem data', 'classe' => 'badge-sem-registo'];
}


/* Gera o link de ordenação */
function link_ordenacao($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo . '&direcao=' . $nova_direcao;
}


/* Mostra o ícone da ordenação */
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


/* Contadores para os cartões superiores */
$total_ativos = 0;
$total_a_expirar = 0;
$total_expirados = 0;
$total_sem_registo = 0;

foreach ($resultados as $registo) {

    $dados_estado = calcular_estado($registo);

    if ($dados_estado['estado'] == 'Ativo') {
        $total_ativos++;
    } elseif ($dados_estado['estado'] == 'A expirar') {
        $total_a_expirar++;
    } elseif ($dados_estado['estado'] == 'Expirado') {
        $total_expirados++;
    } elseif ($dados_estado['estado'] == 'Sem registo') {
        $total_sem_registo++;
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
    .garantias-page {
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

    .estado-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-ativo {
        background: #e8f5ee;
        color: #198754;
    }

    .badge-expirar {
        background: #fff6dd;
        color: #c79200;
    }

    .badge-expirado {
        background: #fdeaea;
        color: #dc3545;
    }

    .badge-sem-registo {
        background: #eef2f7;
        color: #64748b;
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

        <main class="col-md-9 col-lg-10 garantias-page">

            <div class="d-flex justify-content-between align-items-start mb-4">

                <div>

                    <h2 class="page-title mb-1">
                        <i class="fa-solid fa-file-signature me-2"></i>
                        Garantias e Contratos
                    </h2>

                    <p class="page-subtitle mb-0">
                        Consulta e ordenação de garantias e contratos associados aos equipamentos.
                    </p>

                </div>

                <a href="exportar-garantias.php"
                    class="btn btn-sm exportar-btn">
                    <i class="fa-solid fa-file-excel me-1"></i>
                    Exportar Excel
                </a>

            </div>

            <div class="row g-3 mb-4">

                <!-- Ativos -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Ativos</div>
                        <div class="summary-value"><?= $total_ativos ?></div>
                    </div>
                </div>

                <!-- A expirar -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">A expirar</div>
                        <div class="summary-value"><?= $total_a_expirar ?></div>
                    </div>
                </div>

                <!-- Expirados -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Expirados</div>
                        <div class="summary-value"><?= $total_expirados ?></div>
                    </div>
                </div>

                <!-- Sem registo -->
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-title">Sem registo</div>
                        <div class="summary-value"><?= $total_sem_registo ?></div>
                    </div>
                </div>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php elseif ($total_registos == 0) : ?>

                <div class="mensagem-info">
                    Não existem registos para apresentar.
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
                                        <a href="<?= link_ordenacao('tipo', $ordenar, $direcao) ?>">
                                            Tipo de Contrato <?= icone_ordenacao('tipo', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('entidade', $ordenar, $direcao) ?>">
                                            Entidade <?= icone_ordenacao('entidade', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('inicio', $ordenar, $direcao) ?>">
                                            Data Início <?= icone_ordenacao('inicio', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('fim', $ordenar, $direcao) ?>">
                                            Data Fim <?= icone_ordenacao('fim', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('periodicidade', $ordenar, $direcao) ?>">
                                            Periodicidade <?= icone_ordenacao('periodicidade', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao('estado', $ordenar, $direcao) ?>">
                                            Estado <?= icone_ordenacao('estado', $ordenar, $direcao) ?>
                                        </a>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($resultados_pagina as $registo) : ?>

                                    <?php
                                    $dados_estado = calcular_estado($registo);
                                    $estado = $dados_estado['estado'];
                                    $classe = $dados_estado['classe'];
                                    ?>

                                    <tr>
                                        <td><?= htmlspecialchars($registo->codigo_inventario) ?></td>

                                        <td><?= htmlspecialchars($registo->designacao) ?></td>

                                        <td><?= htmlspecialchars($registo->tipo_contrato ?? '-') ?></td>

                                        <td><?= htmlspecialchars($registo->entidade_responsavel ?? '-') ?></td>

                                        <td>
                                            <?= !empty($registo->data_inicio)
                                                ? date('d/m/Y', strtotime($registo->data_inicio))
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <?= !empty($registo->data_fim)
                                                ? date('d/m/Y', strtotime($registo->data_fim))
                                                : '-' ?>
                                        </td>

                                        <td><?= htmlspecialchars($registo->periodicidade ?? '-') ?></td>

                                        <td>
                                            <span class="estado-badge <?= $classe ?>">
                                                <?= htmlspecialchars($estado) ?>
                                            </span>
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