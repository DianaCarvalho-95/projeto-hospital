<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$documento = null;
$erro = '';

if ($id <= 0) {

    $erro = 'Documento inválido.';

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
            "SELECT
                d.*,
                e.codigo_inventario,
                e.designacao,
                f.nome_empresa
             FROM documentacao d
             INNER JOIN equipamentos e ON d.equipamento_id = e.id
             LEFT JOIN fornecedores f ON d.fornecedor_id = f.id
             WHERE d.id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $documento = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$documento) {
            $erro = 'Documento não encontrado.';
        }

    } catch (PDOException $err) {

        $erro = 'Aconteceu um erro ao consultar o documento.';
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
                Detalhes do Documento
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

                    <p><strong>Nome:</strong> <?= htmlspecialchars($documento->nome_documento) ?></p>

                    <p><strong>Tipo:</strong> <?= htmlspecialchars($documento->tipo_documento) ?></p>

                    <p>
                        <strong>Equipamento:</strong>
                        <?= htmlspecialchars($documento->codigo_inventario) ?>
                        -
                        <?= htmlspecialchars($documento->designacao) ?>
                    </p>

                    <p>
                        <strong>Fornecedor:</strong>
                        <?php if (!empty($documento->nome_empresa)) : ?>
                            <?= htmlspecialchars($documento->nome_empresa) ?>
                        <?php else : ?>
                            Sem fornecedor associado
                        <?php endif; ?>
                    </p>

                    <p><strong>Data do documento:</strong> <?= htmlspecialchars($documento->data_documento) ?></p>

                    <p><strong>Validade:</strong> <?= htmlspecialchars($documento->data_validade) ?></p>

                    <p><strong>Ficheiro:</strong> <?= htmlspecialchars($documento->caminho_ficheiro) ?></p>

                    <div class="mt-3">

                        <a href="lista.php" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar
                        </a>

                        <a href="editar.php?id=<?= $documento->id ?>" class="btn btn-warning">
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