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

        /*Ligação à base de dados*/
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
                ";dbname=" . MYSQL_DATABASE .
                ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /*Consulta do fornecedor selecionado*/
        $stmt = $ligacao->prepare(
            "SELECT *
             FROM fornecedores
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

<style>
    /*Fundo da página*/
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

    /*Subtítulo explicativo*/
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /*Cartão branco onde são apresentados os dados*/
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

    /*Campo de informação*/
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

    /*Links dentro dos valores, como email e website*/
    .info-value a {
        color: #2F5D8A;
        text-decoration: none;
        font-weight: 600;
    }

    .info-value a:hover {
        text-decoration: underline;
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

    .btn-editar-custom:hover {
        background: #fff0c2;
        color: #a97700;
    }

    /*Mensagem de erro*/
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

                <!-- Título principal -->
                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-eye me-2"></i>
                    Detalhes do Fornecedor
                </h2>

                <!-- Subtítulo -->
                <p class="page-subtitle">
                    Consulta detalhada dos dados de identificação, contacto e classificação do fornecedor.
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

                        <!-- Dados principais da empresa -->
                        <div class="col-md-6">

                            <h5 class="section-title">
                                <i class="fa-solid fa-building me-2"></i>
                                Dados da Empresa
                            </h5>

                            <div class="info-item">
                                <span class="info-label">Empresa</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->nome_empresa) ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">NIF</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($fornecedor->nif) ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Tipo de fornecedor</span>
                                <span class="info-value">
                                    <?= !empty($fornecedor->tipo_fornecedor)
                                        ? htmlspecialchars($fornecedor->tipo_fornecedor)
                                        : 'Não definido' ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Morada</span>
                                <span class="info-value">
                                    <?= !empty($fornecedor->morada)
                                        ? htmlspecialchars($fornecedor->morada)
                                        : 'Não definida' ?>
                                </span>
                            </div>

                        </div>

                        <!-- Dados de contacto -->
                        <div class="col-md-6">

                            <h5 class="section-title">
                                <i class="fa-solid fa-address-book me-2"></i>
                                Contactos
                            </h5>

                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value">
                                    <?php if (!empty($fornecedor->email)) : ?>

                                        <a href="mailto:<?= htmlspecialchars($fornecedor->email) ?>">
                                            <?= htmlspecialchars($fornecedor->email) ?>
                                        </a>

                                    <?php else : ?>

                                        Não definido

                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Telefone</span>
                                <span class="info-value">
                                    <?= !empty($fornecedor->telefone)
                                        ? htmlspecialchars($fornecedor->telefone)
                                        : 'Não definido' ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Pessoa de contacto</span>
                                <span class="info-value">
                                    <?= !empty($fornecedor->pessoa_contacto)
                                        ? htmlspecialchars($fornecedor->pessoa_contacto)
                                        : 'Não definida' ?>
                                </span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Telefone de contacto</span>
                                <span class="info-value">
                                    <?= !empty($fornecedor->telefone_contacto)
                                        ? htmlspecialchars($fornecedor->telefone_contacto)
                                        : 'Não definido' ?>
                                </span>
                            </div>

                        </div>

                    </div>

                    <!-- Website -->
                    <div class="mt-3">

                        <h5 class="section-title">
                            <i class="fa-solid fa-globe me-2"></i>
                            Website
                        </h5>

                        <div class="info-item mb-0">
                            <span class="info-value">

                                <?php if (!empty($fornecedor->website)) : ?>

                                    <a href="<?= htmlspecialchars($fornecedor->website) ?>"
                                        target="_blank">
                                        <?= htmlspecialchars($fornecedor->website) ?>
                                    </a>

                                <?php else : ?>

                                    Não definido

                                <?php endif; ?>

                            </span>
                        </div>

                    </div>

                    <!-- Observações -->
                    <div class="mt-3">

                        <h5 class="section-title">
                            <i class="fa-solid fa-note-sticky me-2"></i>
                            Observações
                        </h5>

                        <p class="mb-0">
                            <?= !empty($fornecedor->observacoes)
                                ? htmlspecialchars($fornecedor->observacoes)
                                : 'Sem observações registadas.' ?>
                        </p>

                    </div>

                    <!-- Botões de ação -->
                    <div class="mt-4 d-flex gap-2">

                        <a href="lista.php" class="btn btn-voltar-custom">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            Voltar
                        </a>

                        <a href="editar.php?id=<?= $fornecedor->id ?>" class="btn btn-editar-custom">
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