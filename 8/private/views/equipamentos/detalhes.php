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

        /*Ligação à base de dados.*/
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /*Consulta do equipamento selecionado*/
        $stmt = $ligacao->prepare(
            "SELECT
                e.*,
                l.edificio,
                l.piso,
                l.servico,
                l.sala,
                f.nome_empresa
            FROM equipamentos e
            LEFT JOIN localizacoes l
                ON e.localizacao_id = l.id
            LEFT JOIN fornecedores f
                ON e.fornecedor_id = f.id
            WHERE e.id = :id"
        );

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

<style>
    /*Fundo da página.*/
    .detalhes-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /*Título principal*/
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /*Subtítulo*/
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /*Cartão principal*/
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /*Títulos das secções internas*/
    .section-title {
        color: #1E3A5F;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 14px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 8px;
    }

    /*Cada campo de informação*/
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

    /*Botão Editar*/
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

    /*Botão Voltar*/
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
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 detalhes-page">

            <div class="d-flex justify-content-between align-items-start mb-3">

                <div>

                    <!-- Título principal da página -->
                    <h2 class="page-title mb-1">
                        <i class="fa-solid fa-eye me-2"></i>
                        Detalhes do Equipamento
                    </h2>

                    <!-- Texto explicativo semelhante ao da Dashboard -->
                    <p class="page-subtitle">
                        Consulta detalhada dos dados técnicos, administrativos e logísticos do equipamento.
                    </p>

                </div>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro) ?>
                </div>

                <a href="lista.php" class="btn btn-voltar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>

            <?php else : ?>

                <div class="content-card">

                    <div class="row">

                        <!-- Dados gerais do equipamento -->
                        <div class="col-md-6">

                            <h5 class="section-title">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Dados Gerais
                            </h5>

                            <div class="info-item">
                                <span class="info-label">Código interno</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->codigo_inventario) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Designação</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->designacao) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Categoria</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->categoria) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Marca</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->marca) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Modelo</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->modelo) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Número de série</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->numero_serie) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Fabricante</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->fabricante) ?></span>
                            </div>

                        </div>

                        <!-- Estado, localização e dados administrativos -->
                        <div class="col-md-6">

                            <h5 class="section-title">
                                <i class="fa-solid fa-location-dot me-2"></i>
                                Estado e Localização
                            </h5>

                            <div class="info-item">
                                <span class="info-label">Estado</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->estado) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Criticidade</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->criticidade) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Localização</span>

                                <span class="info-value">
                                    <?php if (!empty($equipamento->edificio)) : ?>

                                        <?= htmlspecialchars(
                                            $equipamento->edificio .
                                            ' - ' .
                                            $equipamento->piso .
                                            ' - ' .
                                            $equipamento->servico .
                                            ' - ' .
                                            $equipamento->sala
                                        ) ?>

                                    <?php else : ?>

                                        Sem localização associada

                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Fornecedor</span>

                                <span class="info-value">
                                    <?php if (!empty($equipamento->nome_empresa)) : ?>

                                        <?= htmlspecialchars($equipamento->nome_empresa) ?>

                                    <?php else : ?>

                                        Sem fornecedor associado

                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Data de aquisição</span>
                                <span class="info-value">
                                    <?= !empty($equipamento->data_aquisicao)
                                        ? date('d/m/Y', strtotime($equipamento->data_aquisicao))
                                        : '-' ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Ano de fabrico</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->ano_fabrico) ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Custo de aquisição</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($equipamento->custo_aquisicao) ?> €
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Tipo de entrada</span>
                                <span class="info-value"><?= htmlspecialchars($equipamento->tipo_entrada) ?></span>
                            </div>

                        </div>

                    </div>

                    <!-- Observações -->
                    <div class="mt-3">

                        <h5 class="section-title">
                            <i class="fa-solid fa-note-sticky me-2"></i>
                            Observações
                        </h5>

                        <p class="mb-0">
                            <?= !empty($equipamento->observacoes)
                                ? htmlspecialchars($equipamento->observacoes)
                                : 'Sem observações registadas.' ?>
                        </p>

                    </div>

                    <!-- Botões de ação -->
                    <div class="mt-4 d-flex gap-2">

                        <a href="lista.php" class="btn btn-voltar-custom">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar
                        </a>

                        <a href="editar.php?id=<?= $equipamento->id ?>" class="btn btn-editar-custom">
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