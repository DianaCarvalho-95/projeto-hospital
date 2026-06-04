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

        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        <main class="col-md-9 col-lg-10 p-4">

            <h2>
                <i class="fa-solid fa-trash-can me-2"></i>
                Eliminar Localização
            </h2>

            <hr>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

                <a href="lista.php" class="btn btn-secondary">
                    Voltar
                </a>

            <?php elseif ($localizacao) : ?>

                <div class="card p-4">

                    <p>
                        Tem a certeza que pretende eliminar esta localização?
                    </p>

                    <p>
                        <strong>Edifício:</strong>
                        <?= htmlspecialchars($localizacao->edificio) ?>
                    </p>

                    <p>
                        <strong>Piso:</strong>
                        <?= htmlspecialchars($localizacao->piso) ?>
                    </p>

                    <p>
                        <strong>Serviço:</strong>
                        <?= htmlspecialchars($localizacao->servico) ?>
                    </p>

                    <p>
                        <strong>Sala:</strong>
                        <?= htmlspecialchars($localizacao->sala) ?>
                    </p>

                    <form action="apagar.php?id=<?= $localizacao->id ?>" method="post">

                        <a href="lista.php" class="btn btn-secondary">
                            <i class="fa-solid fa-xmark me-1"></i>
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash-can me-1"></i>
                            Confirmar eliminação
                        </button>

                    </form>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>