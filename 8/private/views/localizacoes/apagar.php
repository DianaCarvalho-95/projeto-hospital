<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$localizacao = null;
$total_equipamentos = 0;
$erro = '';

if ($id <= 0) {

    $erro = 'Localização inválida.';

} else {

    try {

        /*Ligação à base de dados*/
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
            ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt_total = $ligacao->prepare(
            "SELECT COUNT(*)
             FROM equipamentos
             WHERE localizacao_id = :id"
        );
        $stmt_total->execute([':id' => $id]);
        $total_equipamentos = (int) $stmt_total->fetchColumn();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($total_equipamentos > 0) {
                $erro = 'Esta localização não pode ser eliminada porque tem equipamentos associados.';
            } else {
                $stmt = $ligacao->prepare(
                    "DELETE FROM localizacoes
                     WHERE id = :id"
                );
                $stmt->execute([':id' => $id]);

                header('Location: lista.php');
                exit;
            }
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

                        <?php if ($total_equipamentos > 0) : ?>
                            Esta localização tem <?= $total_equipamentos ?> equipamento(s) associado(s) e não pode ser eliminada.
                        <?php else : ?>
                            Tem a certeza que pretende eliminar esta localização? Esta ação é permanente e não poderá ser revertida.
                        <?php endif; ?>

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

