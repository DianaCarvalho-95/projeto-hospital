<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erros = [];
$sucesso = '';
$fornecedor = null;

if ($id <= 0) {

    $erros[] = 'Fornecedor inválido.';

} else {

    try {

        /*Ligação à base de dados*/
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
                ";dbname=" . MYSQL_DATABASE .
                ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /*Se o formulário for submetido, o fornecedor é eliminado*/
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $stmt = $ligacao->prepare(
                "DELETE FROM fornecedores
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id
            ]);

            $sucesso = 'Fornecedor eliminado com sucesso.';

        } else {

            /*Carrega os dados do fornecedor antes da confirmação*/
            $stmt = $ligacao->prepare(
                "SELECT *
                 FROM fornecedores
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id
            ]);

            $fornecedor = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$fornecedor) {
                $erros[] = 'Fornecedor não encontrado.';
            }
        }

    } catch (PDOException $err) {

        $erros[] = 'Não foi possível eliminar o fornecedor.';
    }

    $ligacao = null;
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .apagar-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    .warning-box {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 18px;
    }

    .info-item {
        margin-bottom: 12px;
    }

    .info-label {
        display: block;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .info-value {
        color: #0f172a;
        font-weight: 500;
    }

    .btn-cancelar-custom {
        background: #eef2f7;
        border: 1px solid #dbe3ec;
        color: #475569;
        border-radius: 8px;
        font-weight: 600;
    }

    .btn-cancelar-custom:hover {
        background: #e2e8f0;
        color: #334155;
    }

    .btn-eliminar-custom {
        background: #fdeaea;
        border: 1px solid #f8d3d3;
        color: #dc3545;
        border-radius: 8px;
        font-weight: 600;
    }

    .btn-eliminar-custom:hover {
        background: #fbdcdc;
        color: #bb2d3b;
    }

    .btn-voltar-custom {
        background: #edf4ff;
        border: 1px solid #d6e7ff;
        color: #2F5D8A;
        border-radius: 8px;
        font-weight: 600;
    }

    .btn-voltar-custom:hover {
        background: #dcecff;
        color: #1E3A5F;
    }

    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }

    .mensagem-sucesso {
        background: #e8f5ee;
        color: #198754;
        border: 1px solid #cfead9;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 apagar-page">

            <!-- Eliminar fornecedor -->
            <div class="mb-3">
                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-trash-can me-2"></i>
                    Eliminar Fornecedor
                </h2>

                <p class="page-subtitle">
                    Confirmação da remoção de um fornecedor registado no sistema.
                </p>
            </div>

            <?php if (!empty($erros)) : ?>

                <div class="mensagem-erro">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= htmlspecialchars($erro) ?></div>
                    <?php endforeach; ?>
                </div>

                <a href="lista.php" class="btn btn-cancelar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>

            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>

                <div class="mensagem-sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

                <a href="lista.php" class="btn btn-voltar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar à listagem
                </a>

            <?php endif; ?>

            <?php if ($fornecedor && empty($sucesso)) : ?>

                <div class="content-card">

                    <div class="warning-box">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Tem a certeza que pretende eliminar este fornecedor?
                        Esta ação é permanente e não poderá ser revertida.
                    </div>

                    <div class="row">
                        
                        <!-- Empresa -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Empresa</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->nome_empresa) ?>
                                </span>
                            </div>
                        </div>

                         <!-- NIF -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">NIF</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->nif) ?>
                                </span>
                            </div>
                        </div>

                         <!-- Email -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->email) ?>
                                </span>
                            </div>
                        </div>

                         <!-- Telefone -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Telefone</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->telefone) ?>
                                </span>
                            </div>
                        </div>

                    </div>

                     <!-- Botões "Cancelar" e "Confirmar eliminação" -->
                    <form action="apagar.php?id=<?= $fornecedor->id ?>" method="post">

                        <div class="d-flex gap-2 mt-3">

                            <a href="lista.php" class="btn btn-cancelar-custom">
                                <i class="fa-solid fa-xmark me-1"></i>
                                Cancelar
                            </a>

                            <button type="submit" class="btn btn-eliminar-custom">
                                <i class="fa-solid fa-trash-can me-1"></i>
                                Confirmar eliminação
                            </button>

                        </div>

                    </form>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>