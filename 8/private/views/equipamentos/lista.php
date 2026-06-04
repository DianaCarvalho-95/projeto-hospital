<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 5;
$offset = ($pagina - 1) * $registos_por_pagina;

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $where = [];
    $params = [];

    if (!empty($pesquisa)) {
        $where[] = "(codigo_inventario LIKE :pesquisa
                    OR designacao LIKE :pesquisa
                    OR marca LIKE :pesquisa
                    OR modelo LIKE :pesquisa)";
        $params[':pesquisa'] = '%' . $pesquisa . '%';
    }

    if (!empty($estado)) {
        $where[] = "estado = :estado";
        $params[':estado'] = $estado;
    }

    $sql_where = '';

    if (!empty($where)) {
        $sql_where = ' WHERE ' . implode(' AND ', $where);
    }

    $stmt_total = $ligacao->prepare(
        "SELECT COUNT(*) FROM equipamentos" . $sql_where
    );

    foreach ($params as $chave => $valor) {
        $stmt_total->bindValue($chave, $valor);
    }

    $stmt_total->execute();

    $total_registos = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_registos / $registos_por_pagina);

    $sql = "SELECT * FROM equipamentos"
        . $sql_where .
        " ORDER BY id ASC LIMIT :limite OFFSET :offset";

    $stmt = $ligacao->prepare($sql);

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }

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

            <form method="get" class="row mb-3">

                <div class="col-md-6 mb-2">
                    <input type="text"
                           name="pesquisa"
                           class="form-control"
                           placeholder="Pesquisar por código, designação, marca ou modelo"
                           value="<?= htmlspecialchars($pesquisa) ?>">
                </div>

                <div class="col-md-4 mb-2">
                    <select name="estado" class="form-control">
                        <option value="">Todos os estados</option>
                        <option value="Ativo" <?= $estado == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="Em manutenção" <?= $estado == 'Em manutenção' ? 'selected' : '' ?>>Em manutenção</option>
                        <option value="Inativo" <?= $estado == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        <option value="Em calibração" <?= $estado == 'Em calibração' ? 'selected' : '' ?>>Em calibração</option>
                        <option value="Abatido" <?= $estado == 'Abatido' ? 'selected' : '' ?>>Abatido</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        Filtrar
                    </button>
                </div>

            </form>

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
                                    <th>Código</th>
                                    <th>Designação</th>
                                    <th>Categoria</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Estado</th>
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
                                               href="?pagina=<?= $i ?>&pesquisa=<?= urlencode($pesquisa) ?>&estado=<?= urlencode($estado) ?>">
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