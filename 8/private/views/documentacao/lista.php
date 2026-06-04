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

    $sql = "SELECT 
                d.*,
                e.codigo_inventario,
                e.designacao,
                f.nome_empresa
            FROM documentacao d
            INNER JOIN equipamentos e ON d.equipamento_id = e.id
            LEFT JOIN fornecedores f ON d.fornecedor_id = f.id
            ORDER BY d.id DESC";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar a documentação.';
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
                        Total: <?= count($resultados) ?> documento(s)
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Equipamento</th>
                                    <th>Fornecedor</th>
                                    <th>Data</th>
                                    <th>Validade</th>
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
                                        <td><?= htmlspecialchars($documento->data_documento) ?></td>
                                        <td><?= htmlspecialchars($documento->data_validade) ?></td>

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

                <?php endif; ?>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>