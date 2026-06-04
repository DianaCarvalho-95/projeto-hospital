<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$fornecedor = null;
$erro = '';

if ($id <= 0) {

    $erro = 'Fornecedor inválido.';

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
            "SELECT * FROM fornecedores
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $fornecedor = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$fornecedor) {
            $erro = 'Fornecedor não encontrado.';
        }

    } catch (PDOException $err) {

        $erro = 'Aconteceu um erro ao consultar o fornecedor.';
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
                Detalhes do Fornecedor
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

                    <p><strong>Empresa:</strong> <?= htmlspecialchars($fornecedor->nome_empresa) ?></p>

                    <p><strong>NIF:</strong> <?= htmlspecialchars($fornecedor->nif) ?></p>

                    <p><strong>Email:</strong> <?= htmlspecialchars($fornecedor->email) ?></p>

                    <p><strong>Telefone:</strong> <?= htmlspecialchars($fornecedor->telefone) ?></p>

                    <p><strong>Morada:</strong> <?= htmlspecialchars($fornecedor->morada) ?></p>

                    <p><strong>Website:</strong> <?= htmlspecialchars($fornecedor->website) ?></p>

                    <p><strong>Pessoa de contacto:</strong> <?= htmlspecialchars($fornecedor->pessoa_contacto) ?></p>

                    <p><strong>Telefone de contacto:</strong> <?= htmlspecialchars($fornecedor->telefone_contacto) ?></p>

                    <p><strong>Tipo de fornecedor:</strong> <?= htmlspecialchars($fornecedor->tipo_fornecedor) ?></p>

                    <p><strong>Observações:</strong> <?= htmlspecialchars($fornecedor->observacoes) ?></p>

                    <div class="mt-3">

                        <a href="lista.php" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar
                        </a>

                        <a href="editar.php?id=<?= $fornecedor->id ?>" class="btn btn-warning">
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