<?php

require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

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

            <p>Não existem equipamentos registados.</p>

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
                        <tr>
                            <td>[Código]</td>
                            <td>[Designação]</td>
                            <td>[Categoria]</td>
                            <td>[Marca]</td>
                            <td>[Modelo]</td>
                            <td>[Estado]</td>
                            <td>
                                <a href="detalhes.php" class="text-decoration-none me-2">
                                    <i class="fa-solid fa-eye"></i> Consultar
                                </a>

                                <a href="editar.php" class="text-decoration-none me-2">
                                    <i class="fa-regular fa-pen-to-square"></i> Editar
                                </a>

                                <a href="apagar.php" class="text-decoration-none text-danger">
                                    <i class="fa-solid fa-trash-can"></i> Eliminar
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>