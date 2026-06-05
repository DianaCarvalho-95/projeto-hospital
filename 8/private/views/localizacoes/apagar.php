<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$localizacao = null;
$erro = '';

if ($id <= 0) {

    $erro = 'Localização inválida.';

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

        /*Se o utilizador confirmar a eliminação, o registo é removido da base de dados*/
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $stmt = $ligacao->prepare(
                "DELETE FROM localizacoes
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id
            ]);

            header('Location: lista.php');
            exit;
        }

        /*Carrega a localização a eliminar*/
        $stmt = $ligacao->prepare(
            "SELECT *
             FROM localizacoes
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $localizacao = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$localizacao) {
            $erro = 'Localização não encontrada.';
        }

    } catch (PDOException $err) {

        $erro = 'Não foi possível eliminar a localização.';
    }

    $ligacao = null;
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>

    /*Fundo da página*/
    .apagar-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /*Título principal*/
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /*Subtítulo*/
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
    }

    /*Cartão principal*/
    .content-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /*Caixa de alerta*/
    .warning-box {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 18px;
    }

    /*Informação da localização*/
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

    /*Botão Cancelar*/
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

    /*Botão Eliminar*/
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

</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 apagar-page">

            <div class="mb-3">

                <h2 class="page-title mb-1">

                    <i class="fa-solid fa-trash-can me-2"></i>
                    Eliminar Localização

                </h2>

                <p class="page-subtitle">

                    Remoção permanente de uma localização hospitalar.

                </p>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="alert alert-danger">

                    <?= htmlspecialchars($erro) ?>

                </div>

                <a href="lista.php"
                   class="btn btn-cancelar-custom">

                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar

                </a>

            <?php elseif ($localizacao) : ?>

                <div class="content-card">

                    <div class="warning-box">

                        <i class="fa-solid fa-triangle-exclamation me-2"></i>

                        Tem a certeza que pretende eliminar esta localização?

                        Esta ação é permanente e não poderá ser revertida.

                    </div>

                    <div class="row">

                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Edifício
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($localizacao->edificio) ?>
                                </span>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Piso
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($localizacao->piso) ?>
                                </span>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Serviço / Departamento
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($localizacao->servico) ?>
                                </span>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Sala
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($localizacao->sala) ?>
                                </span>

                            </div>

                        </div>

                    </div>

                    <form action="apagar.php?id=<?= $localizacao->id ?>" method="post">

                        <div class="d-flex gap-2 mt-3">

                            <a href="lista.php"
                               class="btn btn-cancelar-custom">

                                <i class="fa-solid fa-xmark me-1"></i>
                                Cancelar

                            </a>

                            <button type="submit"
                                    class="btn btn-eliminar-custom">

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