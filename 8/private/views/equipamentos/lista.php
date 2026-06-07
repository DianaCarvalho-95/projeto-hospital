<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'id';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'desc' ? 'desc' : 'asc';

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 14;
$offset = ($pagina - 1) * $registos_por_pagina;


/*Colunas permitidas para ordenação*/
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
try {


    /*Ligação à base de dados.*/
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /*Total de equipamentos usado para calcular a paginação*/
    $stmt_total = $ligacao->query("SELECT COUNT(*) FROM equipamentos");

    $total_registos = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_registos / $registos_por_pagina);


    /*Consulta principal dos equipamentos.*/
    $sql = "SELECT
                e.*,
                l.servico,
                l.sala
            FROM equipamentos e
            LEFT JOIN localizacoes l
                ON e.localizacao_id = l.id
            ORDER BY $coluna_sql $direcao
            LIMIT :limite OFFSET :offset";

    $stmt = $ligacao->prepare($sql);

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


/*Cria o link de ordenação*/
function link_ordenacao_equipamentos($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo .
        '&direcao=' . $nova_direcao;
}


/*Mostra o ícone correto da ordenação.*/
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

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*Fundo da página.*/
    .equipamentos-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /*Título principal*/
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /*Subtítulo da página*/
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /*Botão principal*/
    .novo-btn {
        background: #2F5D8A;
        border-color: #2F5D8A;
        color: #fff;
        border-radius: 10px;
        font-weight: 600;
    }

    .novo-btn:hover {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #fff;
    }

    /*Cartão branco que envolve a tabela.*/
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /*Cabeçalho da tabela*/
    .table-primary-custom th {
        background: #2F5D8A !important;
        color: #ffffff !important;
        border-color: #2F5D8A !important;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .table-primary-custom a {
        color: #ffffff;
        text-decoration: none;
    }

    .table td {
        font-size: 0.9rem;
        vertical-align: middle;
    }

    /*Paginação centrada*/
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 22px;
        margin-bottom: 6px;
    }

    .pagination .page-link {
        color: #2F5D8A;
        border-radius: 8px;
        margin: 0 2px;
    }

    .pagination .page-item.active .page-link {
        background-color: #2F5D8A;
        border-color: #2F5D8A;
        color: #fff;
    }

    /*Botões das ações*/
    .action-btn {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 600;
        text-decoration: none;
        margin-right: 4px;
        transition: 0.2s;
    }

    /*Botão Excel */
    .exportar-btn {
        background: #e8f5ee;
        border-color: #cfead9;
        color: #198754;
        border-radius: 10px;
        font-weight: 600;
    }

    .exportar-btn:hover {
        background: #d9f0e3;
        border-color: #badfc9;
        color: #146c43;
    }

    /*Consultar*/
    .action-consultar {
        background: #e8f5ee;
        color: #198754;
    }

    /*Editar*/
    .action-editar {
        background: #fff6dd;
        color: #c79200;
    }

    /*Desativar*/
    .action-desativar {
        background: #fdeaea;
        color: #dc3545;
    }

    .action-btn:hover {
        opacity: 0.85;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 equipamentos-page">

            <div class="d-flex justify-content-between align-items-start mb-3">

                <div>

                    <!-- Título principal da página -->
                    <h2 class="page-title mb-1">
                        <i class="fas fa-cogs me-2"></i>
                        Listagem de Equipamentos
                    </h2>

                    <!-- Texto explicativo semelhante ao da Dashboard -->
                    <p class="page-subtitle">
                        Consulta, pesquisa e acompanhamento dos equipamentos médicos registados.
                    </p>

                </div>

                <!-- Botões para criação de novo equipamento/exportar -->
                <div class="d-flex gap-2">

                    <a href="exportar-equipamentos.php" class="btn btn-sm exportar-btn">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Exportar Excel
                    </a>

                    <a href="novo.php" class="btn btn-sm novo-btn">
                        <i class="fa-solid fa-plus me-1"></i>
                        Novo equipamento
                    </a>

                </div>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="alert alert-danger text-center">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php else : ?>

                <?php if (count($resultados) == 0) : ?>

                    <div class="alert alert-info">
                        Não existem equipamentos registados.
                    </div>

                <?php else : ?>

                    <div class="content-card">

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
                                                Designação <?= icone_ordenacao_equipamentos('designacao', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('categoria', $ordenar, $direcao) ?>">
                                                Categoria <?= icone_ordenacao_equipamentos('categoria', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('marca', $ordenar, $direcao) ?>">
                                                Marca <?= icone_ordenacao_equipamentos('marca', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>
                                            <a href="<?= link_ordenacao_equipamentos('modelo', $ordenar, $direcao) ?>">
                                                Modelo <?= icone_ordenacao_equipamentos('modelo', $ordenar, $direcao) ?>
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
                                            <td><?= htmlspecialchars($equipamento->codigo_inventario) ?></td>
                                            <td><?= htmlspecialchars($equipamento->designacao) ?></td>
                                            <td><?= htmlspecialchars($equipamento->categoria) ?></td>
                                            <td><?= htmlspecialchars($equipamento->marca) ?></td>
                                            <td><?= htmlspecialchars($equipamento->modelo) ?></td>

                                            <td>
                                                <?= !empty($equipamento->servico)
                                                    ? htmlspecialchars($equipamento->servico . ' - ' . $equipamento->sala)
                                                    : '-' ?>
                                            </td>

                                            <td><?= htmlspecialchars($equipamento->estado) ?></td>

                                            <td>
                                                <a href="detalhes.php?id=<?= $equipamento->id ?>"
                                                    class="action-btn action-consultar">
                                                    <i class="fa-solid fa-eye me-1"></i>
                                                    Consultar
                                                </a>

                                                <a href="editar.php?id=<?= $equipamento->id ?>"
                                                    class="action-btn action-editar">
                                                    <i class="fa-regular fa-pen-to-square me-1"></i>
                                                    Editar
                                                </a>

                                                <a href="apagar.php?id=<?= $equipamento->id ?>"
                                                    class="action-btn action-desativar">
                                                    <i class="fa-solid fa-trash-can me-1"></i>
                                                    Desativar
                                                </a>
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

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>