<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erros = [];
$sucesso = '';
$equipamento = null;

if ($id <= 0) {
    $erros[] = 'Equipamento inválido.';
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
                "UPDATE equipamentos SET estado = 'Inativo' WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id
            ]);

            $sucesso = 'Equipamento desativado com sucesso.';
        } else {

            $stmt = $ligacao->prepare(
                "SELECT * FROM equipamentos WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id
            ]);

            $equipamento = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$equipamento) {
                $erros[] = 'Equipamento não encontrado.';
            }
        }
    } catch (PDOException $err) {
        $erros[] = 'Não foi possível eliminar o equipamento.';
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
                Desativar Equipamento
            </h2>

            <hr>

            <?php if (!empty($erros)) : ?>
                <div class="mensagem-erro">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= htmlspecialchars($erro) ?></div>
                    <?php endforeach; ?>
                </div>

                <a href="lista.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>
            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>
                <div class="mensagem-sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

                <a href="lista.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar à listagem
                </a>
            <?php endif; ?>

            <?php if ($equipamento && empty($sucesso)) : ?>

                <div class="card p-4">

                    <p>
                        Tens a certeza que pretendes desativar este equipamento?
                    </p>

                    <p>
                        <strong>Código:</strong>
                        <?= htmlspecialchars($equipamento->codigo_inventario) ?>
                    </p>

                    <p>
                        <strong>Designação:</strong>
                        <?= htmlspecialchars($equipamento->designacao) ?>
                    </p>

                    <p>
                        <strong>Marca:</strong>
                        <?= htmlspecialchars($equipamento->marca) ?>
                    </p>

                    <p>
                        <strong>Modelo:</strong>
                        <?= htmlspecialchars($equipamento->modelo) ?>
                    </p>

                    <form action="apagar.php?id=<?= $equipamento->id ?>" method="post">

                        <a href="lista.php" class="btn btn-secondary">
                            <i class="fa-solid fa-xmark me-1"></i>
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash-can me-1"></i>
                            Confirmar desativação
                        </button>

                    </form>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>