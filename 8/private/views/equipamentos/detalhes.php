<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$equipamento = null;
$erro = '';

if ($id <= 0) {
    $erro = 'Equipamento inválido.';
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

        $stmt = $ligacao->prepare("SELECT * FROM equipamentos WHERE id = :id");
        $stmt->execute([
            ':id' => $id
        ]);

        $equipamento = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$equipamento) {
            $erro = 'Equipamento não encontrado.';
        }

    } catch (PDOException $err) {
        $erro = 'Aconteceu um erro ao consultar o equipamento.';
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
                Detalhes do Equipamento
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

                    <p><strong>Código interno:</strong> <?= htmlspecialchars($equipamento->codigo_inventario) ?></p>
                    <p><strong>Designação:</strong> <?= htmlspecialchars($equipamento->designacao) ?></p>
                    <p><strong>Categoria:</strong> <?= htmlspecialchars($equipamento->categoria) ?></p>
                    <p><strong>Marca:</strong> <?= htmlspecialchars($equipamento->marca) ?></p>
                    <p><strong>Modelo:</strong> <?= htmlspecialchars($equipamento->modelo) ?></p>
                    <p><strong>Número de série:</strong> <?= htmlspecialchars($equipamento->numero_serie) ?></p>
                    <p><strong>Fabricante:</strong> <?= htmlspecialchars($equipamento->fabricante) ?></p>
                    <p><strong>Data de aquisição:</strong> <?= htmlspecialchars($equipamento->data_aquisicao) ?></p>
                    <p><strong>Ano de fabrico:</strong> <?= htmlspecialchars($equipamento->ano_fabrico) ?></p>
                    <p><strong>Custo de aquisição:</strong> <?= htmlspecialchars($equipamento->custo_aquisicao) ?> €</p>
                    <p><strong>Tipo de entrada:</strong> <?= htmlspecialchars($equipamento->tipo_entrada) ?></p>
                    <p><strong>Estado:</strong> <?= htmlspecialchars($equipamento->estado) ?></p>
                    <p><strong>Criticidade:</strong> <?= htmlspecialchars($equipamento->criticidade) ?></p>
                    <p><strong>Observações:</strong> <?= htmlspecialchars($equipamento->observacoes) ?></p>

                    <div class="mt-3">
                        <a href="lista.php" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar
                        </a>

                        <a href="editar.php?id=<?= $equipamento->id ?>" class="btn btn-warning">
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