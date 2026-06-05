<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /*
        Lista as localizações e conta quantos equipamentos
        estão atualmente associados a cada localização.
    */
    $resultados = $ligacao
        ->query(
            "SELECT
                l.*,
                COUNT(e.id) AS total_equipamentos
             FROM localizacoes l
             LEFT JOIN equipamentos e
                ON e.localizacao_id = l.id
             GROUP BY l.id
             ORDER BY l.edificio, l.piso, l.servico, l.sala"
        )
        ->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar as localizações.';
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
                    <i class="fas fa-location-dot me-2"></i>Listagem de Localizações
                </h2>

                <a href="novo.php" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>Nova localização
                </a>
            </div>

            <?php if (!empty($erro)) : ?>

                <p class="text-center text-danger">
                    <?= htmlspecialchars($erro) ?>
                </p>

            <?php else : ?>

                <?php if (count($resultados) == 0) : ?>

                    <p class="text-muted">
                        Não existem localizações registadas.
                    </p>

                <?php else : ?>

                    <p class="text-muted">
                        Total: <?= count($resultados) ?> localização(ões)
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Edifício</th>
                                    <th>Piso</th>
                                    <th>Serviço / Departamento</th>
                                    <th>Sala</th>
                                    <th>N.º Equipamentos</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($resultados as $localizacao) : ?>

                                    <tr>
                                        <td><?= htmlspecialchars($localizacao->edificio) ?></td>
                                        <td><?= htmlspecialchars($localizacao->piso) ?></td>
                                        <td><?= htmlspecialchars($localizacao->servico) ?></td>
                                        <td><?= htmlspecialchars($localizacao->sala) ?></td>
                                        <td><?= $localizacao->total_equipamentos ?></td>

                                        <td>
                                            <a href="detalhes.php?id=<?= $localizacao->id ?>"
                                               class="text-success text-decoration-none me-3">
                                                <i class="fa-solid fa-eye"></i>
                                                Consultar
                                            </a>

                                            <a href="editar.php?id=<?= $localizacao->id ?>"
                                               class="text-warning text-decoration-none me-3">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                                Editar
                                            </a>

                                            <a href="apagar.php?id=<?= $localizacao->id ?>"
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