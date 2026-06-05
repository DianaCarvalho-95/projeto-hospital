<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'id';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'asc' ? 'asc' : 'desc';

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 15;
$offset = ($pagina - 1) * $registos_por_pagina;

$colunas_permitidas = [
    'id' => 'd.id',
    'nome' => 'd.nome_documento',
    'tipo' => 'd.tipo_documento',
    'equipamento' => 'e.designacao',
    'fornecedor' => 'f.nome_empresa',
    'data' => 'd.data_documento',
    'validade' => 'd.data_validade'
];

$coluna_sql = isset($colunas_permitidas[$ordenar])
    ? $colunas_permitidas[$ordenar]
    : 'd.id';

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_total = $ligacao->query("SELECT COUNT(*) FROM documentacao");

    $total_registos = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_registos / $registos_por_pagina);

    $sql = "SELECT 
                d.*,
                e.codigo_inventario,
                e.designacao,
                f.nome_empresa
            FROM documentacao d
            INNER JOIN equipamentos e ON d.equipamento_id = e.id
            LEFT JOIN fornecedores f ON d.fornecedor_id = f.id
            ORDER BY $coluna_sql $direcao
            LIMIT :limite OFFSET :offset";

    $stmt = $ligacao->prepare($sql);
    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar a documentação.';
    $resultados = [];
    $total_registos = 0;
    $total_paginas = 0;
}

$ligacao = null;

function link_ordenacao_documentacao($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo .
        '&direcao=' . $nova_direcao;
}

function icone_ordenacao_documentacao($campo, $ordenar, $direcao)
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
                    <i class="fas fa-file-medical me-2"></i>Listagem de Documentação
                </h2>

                <a href="novo.php" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>Novo documento
                </a>
            </div>

            <?php if (!empty($erro)) : ?>

                <p class="text-center text-danger">
                    <?= htmlspecialchars($erro) ?>
                </p>

            <?php else : ?>

                <?php if (count($resultados) == 0) : ?>

                    <p class="text-muted">
                        Não existem documentos registados.
                    </p>

                <?php else : ?>

                    <p class="text-muted">
                        Total: <?= $total_registos ?> documento(s)
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>
                                        <a href="<?= link_ordenacao_documentacao('nome', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Nome <?= icone_ordenacao_documentacao('nome', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_documentacao('tipo', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Tipo <?= icone_ordenacao_documentacao('tipo', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_documentacao('equipamento', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Equipamento <?= icone_ordenacao_documentacao('equipamento', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_documentacao('fornecedor', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Fornecedor <?= icone_ordenacao_documentacao('fornecedor', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_documentacao('data', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Data <?= icone_ordenacao_documentacao('data', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>
                                        <a href="<?= link_ordenacao_documentacao('validade', $ordenar, $direcao) ?>"
                                           class="text-white text-decoration-none">
                                            Validade <?= icone_ordenacao_documentacao('validade', $ordenar, $direcao) ?>
                                        </a>
                                    </th>

                                    <th>Ações</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($resultados as $documento) : ?>

                                    <tr>
                                        <td><?= htmlspecialchars($documento->nome_documento) ?></td>
                                        <td><?= htmlspecialchars($documento->tipo_documento) ?></td>
                                        <td>
                                            <?= htmlspecialchars($documento->codigo_inventario) ?>
                                            -
                                            <?= htmlspecialchars($documento->designacao) ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($documento->nome_empresa)) : ?>
                                                <?= htmlspecialchars($documento->nome_empresa) ?>
                                            <?php else : ?>
                                                Sem fornecedor
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= !empty($documento->data_documento)
                                                ? date('d/m/Y', strtotime($documento->data_documento))
                                                : '-' ?>
                                        </td>
                                        <td>
                                            <?= !empty($documento->data_validade)
                                                ? date('d/m/Y', strtotime($documento->data_validade))
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <a href="detalhes.php?id=<?= $documento->id ?>"
                                               class="text-success text-decoration-none me-3">
                                                <i class="fa-solid fa-eye"></i>
                                                Consultar
                                            </a>

                                            <a href="editar.php?id=<?= $documento->id ?>"
                                               class="text-warning text-decoration-none me-3">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                                Editar
                                            </a>

                                            <a href="apagar.php?id=<?= $documento->id ?>"
                                               class="text-danger text-decoration-none">
                                                <i class="fa-solid fa-trash-can"></i>
                                                Eliminar
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