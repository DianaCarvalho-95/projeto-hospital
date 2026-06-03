<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">
                    <i class="fas fa-truck-medical me-2"></i>Listagem de Fornecedores
                </h2>

                <a href="novo.php" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>Novo fornecedor
                </a>
            </div>

            <p>Não existem fornecedores registados.</p>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Empresa</th>
                            <th>NIF</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Tipo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>[Nome Empresa]</td>
                            <td>[NIF]</td>
                            <td>[email]</td>
                            <td>[telefone]</td>
                            <td>[tipo_fornecedor]</td>
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