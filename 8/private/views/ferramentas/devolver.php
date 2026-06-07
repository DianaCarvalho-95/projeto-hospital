<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erros = [];
$sucesso = '';
$emprestimo = null;

if ($id <= 0) {

    $erros[] = 'Empréstimo inválido.';

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
                "UPDATE emprestimos
                 SET
                    estado = 'Devolvido',
                    data_devolucao = CURDATE()
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id
            ]);

            $sucesso = 'Devolução registada com sucesso.';
        }

        $stmt = $ligacao->prepare(
            "SELECT
                ep.*,
                e.codigo_inventario,
                e.designacao
             FROM emprestimos ep
             INNER JOIN equipamentos e
                ON ep.equipamento_id = e.id
             WHERE ep.id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $emprestimo = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$emprestimo) {
            $erros[] = 'Empréstimo não encontrado.';
        }

    } catch (PDOException $err) {

        $erros[] = 'Não foi possível registar a devolução.';
    }

    $ligacao = null;
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>

.devolver-page{
    background:#f5f7fa;
    min-height:100vh;
    padding:24px;
}

.page-title{
    font-weight:600;
    color:#1E3A5F;
}

.content-card{
    background:#fff;
    border-radius:16px;
    padding:24px;
    box-shadow:0 6px 16px rgba(15,23,42,.06);
    border:1px solid #e5e7eb;
}

.info-box{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:12px;
    margin-bottom:10px;
}

.info-label{
    display:block;
    font-size:.75rem;
    font-weight:600;
    color:#64748b;
}

.info-value{
    font-weight:500;
    color:#0f172a;
}

.btn-cancelar-custom{
    background:#eef2f7;
    border:1px solid #dbe3ec;
    color:#475569;
}

.btn-confirmar-custom{
    background:#edf4ff;
    border:1px solid #d6e7ff;
    color:#2F5D8A;
    font-weight:600;
}

.mensagem-erro{
    background:#fdeaea;
    border:1px solid #f8d3d3;
    color:#bb2d3b;
    border-radius:10px;
    padding:12px;
    margin-bottom:16px;
}

.mensagem-sucesso{
    background:#e8f5ee;
    border:1px solid #cfead9;
    color:#198754;
    border-radius:10px;
    padding:12px;
    margin-bottom:16px;
}

</style>

<div class="container-fluid">
<div class="row">

<?php include '../../includes/sidebar.php'; ?>

<main class="col-md-9 col-lg-10 devolver-page">

    <h2 class="page-title mb-3">
        <i class="fa-solid fa-arrow-rotate-left me-2"></i>
        Registar Devolução
    </h2>

    <?php if (!empty($erros)) : ?>

        <div class="mensagem-erro">

            <?php foreach ($erros as $erro) : ?>
                <div><?= htmlspecialchars($erro) ?></div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <?php if (!empty($sucesso)) : ?>

        <div class="mensagem-sucesso">
            <?= htmlspecialchars($sucesso) ?>
        </div>

        <a href="emprestimos.php" class="btn btn-secondary">
            Voltar à listagem
        </a>

    <?php endif; ?>

    <?php if ($emprestimo && empty($sucesso)) : ?>

    <div class="content-card">

        <p class="mb-4">
            Tem a certeza que pretende registar a devolução deste equipamento?
        </p>

        <div class="row">

            <div class="col-md-6">

                <div class="info-box">
                    <span class="info-label">Código</span>
                    <span class="info-value">
                        <?= htmlspecialchars($emprestimo->codigo_inventario) ?>
                    </span>
                </div>

            </div>

            <div class="col-md-6">

                <div class="info-box">
                    <span class="info-label">Equipamento</span>
                    <span class="info-value">
                        <?= htmlspecialchars($emprestimo->designacao) ?>
                    </span>
                </div>

            </div>

            <div class="col-md-6">

                <div class="info-box">
                    <span class="info-label">Origem</span>
                    <span class="info-value">
                        <?= htmlspecialchars($emprestimo->servico_origem) ?>
                    </span>
                </div>

            </div>

            <div class="col-md-6">

                <div class="info-box">
                    <span class="info-label">Destino</span>
                    <span class="info-value">
                        <?= htmlspecialchars($emprestimo->servico_destino) ?>
                    </span>
                </div>

            </div>

        </div>

        <form action="devolver.php?id=<?= $emprestimo->id ?>" method="post">

            <a href="emprestimos.php"
               class="btn btn-cancelar-custom">
                <i class="fa-solid fa-xmark me-1"></i>
                Cancelar
            </a>

            <button type="submit"
                    class="btn btn-confirmar-custom">
                <i class="fa-solid fa-check me-1"></i>
                Confirmar Devolução
            </button>

        </form>

    </div>

    <?php endif; ?>

</main>

</div>
</div>

<?php include '../../includes/footer.php'; ?>