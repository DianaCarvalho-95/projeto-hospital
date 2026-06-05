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

$registos_por_pagina = 15;
$offset = ($pagina - 1) * $registos_por_pagina;

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

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_total = $ligacao->query("SELECT COUNT(*) FROM equipamentos");

    $total_registos = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_registos / $registos_por_pagina);

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

function link_ordenacao_equipamentos($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo .
        '&direcao=' . $nova_direcao;
}

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
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 22px;
        margin-bottom: 14px;
    }

    .pagination .page-link {
        color: #0d6efd;
        border-radius: 8px;
        margin: 0 2px;
    }

    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>Listagem de Equipamentos
                </h2>

                <a href="novo.php" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>Novo equipamento
                </a>
            </div>

            <?php if (!empty($erro)) : ?>

                <p class="text-center text-danger">
                    <?= htmlspecialchars($erro) ?>
                </p>

            <?php else : ?>

                <?php if (count($resultados) == 0) : ?>

                    <p class="text-muted">
                        Não existem equipamentos registados.
                    </p>

                <?php else : ?>

                    <p class="text-muted">
                        Total: <?= $total_registos ?> equipamento(s)
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>
                                        <a href="<?= link_ordenacao_equipamentos('codigo', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Código <?= icone_ordenacao_equipamentos('codigo', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_equipamentos('designacao', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Designação <?= icone_ordenacao_equipamentos('designacao', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_equipamentos('categoria', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Categoria <?= icone_ordenacao_equipamentos('categoria', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_equipamentos('marca', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Marca <?= icone_ordenacao_equipamentos('marca', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_equipamentos('modelo', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Modelo <?= icone_ordenacao_equipamentos('modelo', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_equipamentos('localizacao', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Localização <?= icone_ordenacao_equipamentos('localizacao', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_equipamentos('estado', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
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
                                               class="text-success text-decoration-none me-3">
                                                <i class="fa-solid fa-eye"></i>
                                                Consultar
                                            </a>

                                            <a href="editar.php?id=<?= $equipamento->id ?>"
                                               class="text-warning text-decoration-none me-3">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                                Editar
                                            </a>

                                            <a href="apagar.php?id=<?= $equipamento->id ?>"
                                               class="text-danger text-decoration-none">
                                                <i class="fa-solid fa-trash-can"></i>
                                                Desativar
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
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