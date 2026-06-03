<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

// --------------------------------------------------------------------
// LIGAÇÃO À BASE DE DADOS
// --------------------------------------------------------------------

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $resultados = $ligacao
        ->query("SELECT * FROM equipamentos")
        ->fetchAll(PDO::FETCH_OBJ);

    $erro = '';
} catch (PDOException $err) {

    $erro = 'Aconteceu um erro na ligação à base de dados.';
    $resultados = [];
}

$ligacao = null;

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

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
                                                Eliminar

                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>