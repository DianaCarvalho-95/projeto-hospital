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

        /*Carrega a localização selecionada.*/
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

        $erro = 'Aconteceu um erro ao consultar a localização.';
    }

    $ligacao = null;
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>

    /*Fundo da página*/
    .detalhes-page {

        background: #f5f7fa;

        min-height: 100vh;

        padding: 24px;
    }

    /*Título principal.*/
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

    /*Títulos das secções*/
    .section-title {

        color: #1E3A5F;

        font-weight: 600;

        font-size: 1rem;

        margin-bottom: 14px;

        border-bottom: 1px solid #e5e7eb;

        padding-bottom: 8px;
    }

    /*Informação apresentada*/
    .info-item {

        margin-bottom: 12px;
    }

    .info-label {

        display: block;

        color: #64748b;

        font-size: 0.78rem;

        font-weight: 600;

        margin-bottom: 2px;
    }

    .info-value {

        color: #0f172a;

        font-weight: 500;
    }

    /*Botão Editar*/
    .btn-editar-custom {
        background: #2F5D8A;
        border-color: #2F5D8A;
        color: #ffffff;
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-editar-custom:hover {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #ffffff;
    }

    /*Botão Voltar*/
    .btn-voltar-custom {
        background: #e5e7eb;
        border-color: #e5e7eb;
        color: #1E3A5F;
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-editar-custom:hover {
        background: #fff0c2;
        color: #a97700;
    }

</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 detalhes-page">

            <div class="mb-3">

                <h2 class="page-title mb-1">

                    <i class="fa-solid fa-eye me-2"></i>
                    Detalhes da Localização

                </h2>

                <p class="page-subtitle">

                    Consulta detalhada dos dados da localização hospitalar.

                </p>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="alert alert-danger">

                    <?= htmlspecialchars($erro) ?>

                </div>

                <a href="lista.php" class="btn btn-voltar-custom">

                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar

                </a>

            <?php else : ?>

                <div class="content-card">

                    <div class="row">

                        <div class="col-md-6">

                            <h5 class="section-title">

                                <i class="fa-solid fa-location-dot me-2"></i>
                                Dados da Localização

                            </h5>

                            <div class="info-item">

                                <span class="info-label">
                                    Edifício
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($localizacao->edificio) ?>
                                </span>

                            </div>

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

                            <h5 class="section-title">

                                <i class="fa-solid fa-building me-2"></i>
                                Serviço

                            </h5>

                            <div class="info-item">

                                <span class="info-label">
                                    Serviço / Departamento
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($localizacao->servico) ?>
                                </span>

                            </div>

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

                    <div class="mt-3">

                        <h5 class="section-title">

                            <i class="fa-solid fa-note-sticky me-2"></i>
                            Observações

                        </h5>

                        <p class="mb-0">

                            <?= !empty($localizacao->observacoes)
                                ? htmlspecialchars($localizacao->observacoes)
                                : 'Sem observações registadas.' ?>

                        </p>

                    </div>

                    <div class="mt-4 d-flex gap-2">

                        <a href="lista.php"
                           class="btn btn-voltar-custom">

                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar

                        </a>

                        <a href="editar.php?id=<?= $localizacao->id ?>"
                           class="btn btn-editar-custom">

                            <i class="fa-regular fa-pen-to-square me-1"></i>
                            Editar

                        </a>

                    </div>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>