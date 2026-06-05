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

        /* Ligação à base de dados */
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
                ";dbname=" . MYSQL_DATABASE .
                ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /* Consulta do documento selecionado */
        $stmt = $ligacao->prepare(
            "SELECT
                d.*,
                e.codigo_inventario,
                e.designacao,
                f.nome_empresa
             FROM documentacao d
             INNER JOIN equipamentos e
                ON d.equipamento_id = e.id
             LEFT JOIN fornecedores f
                ON d.fornecedor_id = f.id
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

<style>
    .detalhes-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    .section-title {
        color: #1E3A5F;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 14px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 8px;
    }

    .info-item {
        margin-bottom: 12px;
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

    .btn-editar-custom {
        background: #2F5D8A;
        border-color: #2F5D8A;
        color: #ffffff;
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-editar-custom:hover {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #ffffff;
    }

    .btn-voltar-custom {
        background: #e5e7eb;
        border-color: #e5e7eb;
        color: #1E3A5F;
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-voltar-custom:hover {
        background: #d1d5db;
        border-color: #d1d5db;
        color: #1E3A5F;
    }

    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 detalhes-page">

            <div class="mb-3">

                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-eye me-2"></i>
                    Detalhes do Documento
                </h2>

                <p class="page-subtitle">
                    Consulta detalhada da documentação associada aos equipamentos médicos.
                </p>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

                <a href="lista.php" class="btn btn-voltar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>

            <?php else : ?>

                <div class="content-card">

                    <div class="row">

                        <div class="col-md-6">

                            <!-- Informação do Documento -->
                            <h5 class="section-title">
                                <i class="fa-solid fa-file-lines me-2"></i>
                                Informação do Documento
                            </h5>

                            <!-- Nome -->
                            <div class="info-item">
                                <span class="info-label">Nome</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($documento->nome_documento) ?>
                                </span>
                            </div>

                            <!-- Tipo -->
                            <div class="info-item">
                                <span class="info-label">Tipo</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($documento->tipo_documento) ?>
                                </span>
                            </div>

                            <!-- Data do Documento -->
                            <div class="info-item">
                                <span class="info-label">Data do Documento</span>
                                <span class="info-value">
                                    <?= !empty($documento->data_documento)
                                        ? date('d/m/Y', strtotime($documento->data_documento))
                                        : 'Não definida' ?>
                                </span>
                            </div>

                            <!-- Data de Validade -->
                            <div class="info-item">
                                <span class="info-label">Data de Validade</span>
                                <span class="info-value">
                                    <?= !empty($documento->data_validade)
                                        ? date('d/m/Y', strtotime($documento->data_validade))
                                        : 'Sem validade definida' ?>
                                </span>
                            </div>

                        </div>

                        <div class="col-md-6">

                            <!-- Associações -->
                            <h5 class="section-title">
                                <i class="fa-solid fa-link me-2"></i>
                                Associações
                            </h5>

                            <!-- ID do Documento -->
                            <div class="info-item">
                                <span class="info-label">ID do Documento</span>
                                <span class="info-value">
                                    <?= $documento->id ?>
                                </span>
                            </div>

                            <!-- Equipamento -->
                            <div class="info-item">
                                <span class="info-label">Equipamento</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($documento->codigo_inventario) ?>
                                    -
                                    <?= htmlspecialchars($documento->designacao) ?>
                                </span>
                            </div>

                            <!-- Fornecedor -->
                            <div class="info-item">
                                <span class="info-label">Fornecedor</span>
                                <span class="info-value">
                                    <?= !empty($documento->nome_empresa)
                                        ? htmlspecialchars($documento->nome_empresa)
                                        : 'Sem fornecedor associado' ?>
                                </span>
                            </div>

                        </div>

                    </div>

                    <div class="mt-4 d-flex gap-2">

                        <a href="lista.php" class="btn btn-voltar-custom">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar
                        </a>

                        <a href="editar.php?id=<?= $documento->id ?>"
                           class="btn btn-editar-custom">
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