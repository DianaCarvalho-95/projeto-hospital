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

<style>
    /*
        Fundo da página.
        Mantém a coerência com Dashboard, listagem, consulta e edição.
    */
    .apagar-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /*
        Título principal da página.
    */
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /*
        Subtítulo explicativo.
    */
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /*
        Cartão branco principal.
    */
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /*
        Caixa de aviso.
        Destaca a ação de desativação sem usar cores demasiado fortes.
    */
    .warning-box {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 18px;
        font-size: 0.92rem;
    }

    /*
        Campos resumidos do equipamento.
    */
    .info-item {
        margin-bottom: 10px;
        font-size: 0.92rem;
    }

    .info-label {
        display: block;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .info-value {
        color: #0f172a;
        font-weight: 500;
    }

    /*
        Botão Cancelar.
    */
    .btn-cancelar-custom {
        background: #eef2f7;
        border: 1px solid #dbe3ec;
        color: #475569;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-cancelar-custom:hover {
        background: #e2e8f0;
        color: #334155;
    }

    /*
        Botão Desativar.
        Usa vermelho suave para indicar uma ação sensível.
    */
    .btn-desativar-custom {
        background: #fdeaea;
        border: 1px solid #f8d3d3;
        color: #dc3545;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-desativar-custom:hover {
        background: #fbdcdc;
        color: #bb2d3b;
    }

    /*
        Botão Voltar depois da ação.
    */
    .btn-voltar-custom {
        background: #edf4ff;
        border: 1px solid #d6e7ff;
        color: #2F5D8A;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-voltar-custom:hover {
        background: #dcecff;
        color: #1E3A5F;
    }

    /*
        Mensagens de erro e sucesso.
    */
    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }

    .mensagem-sucesso {
        background: #e8f5ee;
        color: #198754;
        border: 1px solid #cfead9;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

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