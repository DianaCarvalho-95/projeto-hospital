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

        /*
            Ligação à base de dados.
        */
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
                ";dbname=" . MYSQL_DATABASE .
                ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /*
            Se o formulário for submetido, o equipamento não é apagado
            fisicamente da base de dados. Apenas fica com estado "Inativo".
            Isto permite manter histórico e rastreabilidade.
        */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $stmt = $ligacao->prepare(
                "UPDATE equipamentos
                 SET estado = 'Inativo'
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id
            ]);

            registar_evento('Equipamentos', 'Arquivo', 'Equipamento', $id, 'Equipamento marcado como inativo.');

            $sucesso = 'Equipamento desativado com sucesso.';

        } else {

            /*
                Antes de confirmar a desativação, são carregados os dados
                do equipamento para o utilizador confirmar a operação.
            */
            $stmt = $ligacao->prepare(
                "SELECT *
                 FROM equipamentos
                 WHERE id = :id"
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

        $erros[] = 'Não foi possível desativar o equipamento.';
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
                    Desativar Equipamento
                </h2>

                <p class="page-subtitle">
                    Confirmação da desativação do equipamento no sistema.
                </p>

            </div>

            <?php if (!empty($erros)) : ?>

                <div class="mensagem-erro">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= htmlspecialchars($erro) ?></div>
                    <?php endforeach; ?>
                </div>

                <a href="lista.php" class="btn btn-cancelar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>

            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>

                <div class="mensagem-sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

                <a href="lista.php" class="btn btn-voltar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar à listagem
                </a>

            <?php endif; ?>

            <?php if ($equipamento && empty($sucesso)) : ?>

                <div class="content-card">

                    <div class="warning-box">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Tens a certeza que pretendes desativar este equipamento?
                        Esta ação altera o estado para <strong>Inativo</strong>, sem remover o registo da base de dados.
                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Código</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($equipamento->codigo_inventario) ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Designação</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($equipamento->designacao) ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Marca</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($equipamento->marca) ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Modelo</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($equipamento->modelo) ?>
                                </span>
                            </div>
                        </div>

                    </div>

                    <form action="apagar.php?id=<?= $equipamento->id ?>" method="post">

                        <div class="d-flex gap-2 mt-3">

                            <a href="lista.php" class="btn btn-cancelar-custom">
                                <i class="fa-solid fa-xmark me-1"></i>
                                Cancelar
                            </a>

                            <button type="submit" class="btn btn-desativar-custom">
                                <i class="fa-solid fa-trash-can me-1"></i>
                                Confirmar desativação
                            </button>

                        </div>

                    </form>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>
