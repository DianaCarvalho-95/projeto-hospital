<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erro = '';
$documento = null;

if ($id <= 0) {

    $erro = 'Documento inválido.';

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

        /*Se o formulário for submetido, elimina o documento*/
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $stmt = $ligacao->prepare(
                "DELETE FROM documentacao
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id
            ]);

            header('Location: lista.php');
            exit;
        }

        /*Carrega o documento antes da confirmação*/
        $stmt = $ligacao->prepare(
            "SELECT *
             FROM documentacao
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $documento = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$documento) {
            $erro = 'Documento não encontrado.';
        }

    } catch (PDOException $err) {

        $erro = 'Não foi possível eliminar o documento.';
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

    .mensagem-erro {
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

        <main class="col-md-9 col-lg-10 apagar-page">

            <div class="mb-3">

                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-trash-can me-2"></i>
                    Eliminar Documento
                </h2>

                <p class="page-subtitle">
                    Confirmação da remoção de um documento registado no sistema.
                </p>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

                <a href="lista.php" class="btn btn-cancelar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>

            <?php elseif ($documento) : ?>

                <div class="content-card">

                    <div class="warning-box">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Tem a certeza que pretende eliminar este documento?
                        Esta ação é permanente e não poderá ser revertida.
                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Documento</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($documento->nome_documento) ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Tipo</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($documento->tipo_documento) ?>
                                </span>
                            </div>
                        </div>

                    </div>


                    <!-- Opções Cancelar ou Confirmar eliminação -->
                    <form action="apagar.php?id=<?= $documento->id ?>" method="post">

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