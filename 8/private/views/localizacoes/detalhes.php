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

        $stmt = $ligacao->prepare(
            "SELECT * FROM localizacoes
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

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>
                <i class="fa-solid fa-eye me-2"></i>
                Detalhes da Localização
            </h2>

            <hr>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

                <a href="lista.php" class="btn btn-secondary">
                    Voltar
                </a>

            <?php else : ?>

                <div class="card p-4">

                    <p>
                        <strong>Edifício:</strong>
                        <?= htmlspecialchars($localizacao->edificio) ?>
                    </p>

                    <p>
                        <strong>Piso:</strong>
                        <?= htmlspecialchars($localizacao->piso) ?>
                    </p>

                    <p>
                        <strong>Serviço / Departamento:</strong>
                        <?= htmlspecialchars($localizacao->servico) ?>
                    </p>

                    <p>
                        <strong>Sala:</strong>
                        <?= htmlspecialchars($localizacao->sala) ?>
                    </p>

                    <p>
                        <strong>Observações:</strong>
                        <?= htmlspecialchars($localizacao->observacoes) ?>
                    </p>

                    <div class="mt-3">

                        <a href="lista.php" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar
                        </a>

                        <a href="editar.php?id=<?= $localizacao->id ?>" class="btn btn-warning">
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