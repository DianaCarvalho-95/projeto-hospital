<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

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

    
    /* Consulta dos fornecedores ordenados alfabeticamente */
    $resultados = $ligacao
        ->query("SELECT * FROM fornecedores ORDER BY nome_empresa")
        ->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar os fornecedores.';
    $resultados = [];
}

$ligacao = null;

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /* Fundo da página */
    .fornecedores-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /* Título principal */
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /* Subtítulo */
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /* Botão principal */
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

    /* Botão de exportação */
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

    /* Cartão branco da tabela */
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /* Cabeçalho da tabela */
    .table-primary-custom th {
        background: #2F5D8A !important;
        color: #ffffff !important;
        border-color: #2F5D8A !important;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .table td {
        font-size: 0.9rem;
        vertical-align: middle;
    }

    /* Botões de ação */
    .action-btn {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 600;
        text-decoration: none;
        margin-right: 4px;
        transition: 0.2s;
        white-space: nowrap;
    }

    .action-consultar {
        background: #e8f5ee;
        color: #198754;
    }

    .action-editar {
        background: #fff6dd;
        color: #c79200;
    }

    .action-eliminar {
        background: #fdeaea;
        color: #dc3545;
    }

    .action-btn:hover {
        opacity: 0.85;
    }

    /* Mensagem de erro */
    .mensagem-erro-custom {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 fornecedores-page">

            <div class="d-flex justify-content-between align-items-start mb-3">

                <div>

                    <h2 class="page-title mb-1">
                        <i class="fas fa-truck-medical me-2"></i>
                        Listagem de Fornecedores
                    </h2>

                    <p class="page-subtitle">
                        Gestão e consulta dos fornecedores associados ao parque tecnológico hospitalar.
                    </p>

                </div>

                <div class="d-flex gap-2">

                    <a href="exportar-fornecedores.php" class="btn btn-sm exportar-btn">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Exportar Excel
                    </a>

                    <a href="novo.php" class="btn btn-sm novo-btn">
                        <i class="fa-solid fa-plus me-1"></i>
                        Novo fornecedor
                    </a>

                </div>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro-custom text-center">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php else : ?>

                <?php if (count($resultados) == 0) : ?>

                    <div class="alert alert-info">
                        Não existem fornecedores registados.
                    </div>

                <?php else : ?>

                    <div class="content-card">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover align-middle mb-0">

                                <thead class="table-primary-custom">
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

                                    <?php foreach ($resultados as $fornecedor) : ?>

                                        <tr>
                                            <td><?= htmlspecialchars($fornecedor->nome_empresa) ?></td>
                                            <td><?= htmlspecialchars($fornecedor->nif) ?></td>
                                            <td><?= htmlspecialchars($fornecedor->email) ?></td>
                                            <td><?= htmlspecialchars($fornecedor->telefone) ?></td>
                                            <td><?= htmlspecialchars($fornecedor->tipo_fornecedor) ?></td>

                                            <td>
                                                <a href="detalhes.php?id=<?= $fornecedor->id ?>"
                                                   class="action-btn action-consultar">
                                                    <i class="fa-solid fa-eye me-1"></i>
                                                    Consultar
                                                </a>

                                                <a href="editar.php?id=<?= $fornecedor->id ?>"
                                                   class="action-btn action-editar">
                                                    <i class="fa-regular fa-pen-to-square me-1"></i>
                                                    Editar
                                                </a>

                                                <a href="apagar.php?id=<?= $fornecedor->id ?>"
                                                   class="action-btn action-eliminar">
                                                    <i class="fa-solid fa-trash-can me-1"></i>
                                                    Eliminar
                                                </a>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>