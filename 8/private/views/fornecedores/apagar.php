<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erros = [];
$sucesso = '';
$fornecedor = null;
$total_equipamentos = 0;

if ($id <= 0) {

    $erros[] = 'Fornecedor inválido.';

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
             WHERE fornecedor_id = :id"
        );
        $stmt_total->execute([':id' => $id]);
        $total_equipamentos = (int) $stmt_total->fetchColumn();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($total_equipamentos > 0) {
                $erros[] = 'Este fornecedor não pode ser eliminado porque tem equipamentos associados.';
            } else {
                $stmt = $ligacao->prepare(
                    "DELETE FROM fornecedores
                     WHERE id = :id"
                );
                $stmt->execute([':id' => $id]);
                $sucesso = 'Fornecedor eliminado com sucesso.';
            }
        }

        $stmt = $ligacao->prepare(
            "SELECT *
             FROM fornecedores
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $fornecedor = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$fornecedor && empty($sucesso)) {
            $erros[] = 'Fornecedor não encontrado.';
        }

    } catch (PDOException $err) {

        $erros[] = 'Não foi possível eliminar o fornecedor.';
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

            <!-- Eliminar fornecedor -->
            <div class="mb-3">
                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-trash-can me-2"></i>
                    Eliminar Fornecedor
                </h2>

                <p class="page-subtitle">
                    Confirmação da remoção de um fornecedor registado no sistema.
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

            <?php if ($fornecedor && empty($sucesso)) : ?>

                <div class="content-card">

                    <div class="warning-box">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        <?php if ($total_equipamentos > 0) : ?>
                            Este fornecedor tem <?= $total_equipamentos ?> equipamento(s) associado(s) e não pode ser eliminado.
                        <?php else : ?>
                            Tem a certeza que pretende eliminar este fornecedor? Esta ação é permanente e não poderá ser revertida.
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        
                        <!-- Empresa -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Empresa</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->nome_empresa) ?>
                                </span>
                            </div>
                        </div>

                         <!-- NIF -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">NIF</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->nif) ?>
                                </span>
                            </div>
                        </div>

                         <!-- Email -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->email) ?>
                                </span>
                            </div>
                        </div>

                         <!-- Telefone -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Telefone</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->telefone) ?>
                                </span>
                            </div>
                        </div>

                    </div>

                     <!-- Botões "Cancelar" e "Confirmar eliminação" -->
                    <form action="apagar.php?id=<?= $fornecedor->id ?>" method="post">

                        <div class="d-flex gap-2 mt-3">

                            <a href="lista.php" class="btn btn-cancelar-custom">
                                <i class="fa-solid fa-xmark me-1"></i>
                                Cancelar
                            </a>

                            <button type="submit" class="btn btn-eliminar-custom">
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

