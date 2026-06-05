<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erros = [];
$sucesso = '';

$nome_empresa = '';
$nif = '';
$telefone = '';
$email = '';
$morada = '';
$website = '';
$pessoa_contacto = '';
$telefone_contacto = '';
$tipo_fornecedor = '';
$observacoes = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /*Recolha dos dados enviados pelo formulário*/
    $nome_empresa = isset($_POST['nome_empresa']) ? trim($_POST['nome_empresa']) : '';
    $nif = isset($_POST['nif']) ? trim($_POST['nif']) : '';
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $morada = isset($_POST['morada']) ? trim($_POST['morada']) : '';
    $website = isset($_POST['website']) ? trim($_POST['website']) : '';
    $pessoa_contacto = isset($_POST['pessoa_contacto']) ? trim($_POST['pessoa_contacto']) : '';
    $telefone_contacto = isset($_POST['telefone_contacto']) ? trim($_POST['telefone_contacto']) : '';
    $tipo_fornecedor = isset($_POST['tipo_fornecedor']) ? trim($_POST['tipo_fornecedor']) : '';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

    /*Validações obrigatórias*/
    if (empty($nome_empresa)) {
        $erros[] = 'O nome da empresa é obrigatório.';
    }

    if (empty($nif)) {
        $erros[] = 'O NIF é obrigatório.';
    }

    if (empty($telefone)) {
        $erros[] = 'O telefone é obrigatório.';
    }

    if (empty($email)) {
        $erros[] = 'O email é obrigatório.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'O email introduzido não é válido.';
    }

    if (empty($tipo_fornecedor)) {
        $erros[] = 'O tipo de fornecedor é obrigatório.';
    }

    if (empty($erros)) {

        /*Normalização de alguns textos antes de guardar*/
        $nome_empresa = ucwords(strtolower($nome_empresa));
        $pessoa_contacto = ucwords(strtolower($pessoa_contacto));

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

            /*Inserção do novo fornecedor*/
            $sql = "INSERT INTO fornecedores
                    (nome_empresa, nif, telefone, email, morada, website,
                     pessoa_contacto, telefone_contacto, tipo_fornecedor, observacoes)
                    VALUES
                    (:nome_empresa, :nif, :telefone, :email, :morada, :website,
                     :pessoa_contacto, :telefone_contacto, :tipo_fornecedor, :observacoes)";

            $stmt = $ligacao->prepare($sql);

            $stmt->execute([
                ':nome_empresa' => $nome_empresa,
                ':nif' => $nif,
                ':telefone' => $telefone,
                ':email' => $email,
                ':morada' => $morada,
                ':website' => $website,
                ':pessoa_contacto' => $pessoa_contacto,
                ':telefone_contacto' => $telefone_contacto,
                ':tipo_fornecedor' => $tipo_fornecedor,
                ':observacoes' => $observacoes
            ]);

            $sucesso = 'Fornecedor inserido com sucesso.';

            /*Limpa os campos depois da inserção*/
            $nome_empresa = '';
            $nif = '';
            $telefone = '';
            $email = '';
            $morada = '';
            $website = '';
            $pessoa_contacto = '';
            $telefone_contacto = '';
            $tipo_fornecedor = '';
            $observacoes = '';

        } catch (PDOException $err) {

            $erros[] = 'Não foi possível inserir o fornecedor.';
        }

        $ligacao = null;
    }
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*Fundo da página*/
    .novo-page {
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

    /*Subtítulo da página*/
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /*Cartão branco que contém o formulário*/
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /*Labels dos campos*/
    .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.88rem;
    }

    /*Campos do formulário*/
    .form-control {
        border-radius: 10px;
        border: 1px solid #dbe3ec;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: #2F5D8A;
        box-shadow: 0 0 0 0.15rem rgba(47, 93, 138, 0.18);
    }

    /*Botão Cancelar*/
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

    /*Botão Guardar*/
    .btn-guardar-custom {
        background: #edf4ff;
        border: 1px solid #d6e7ff;
        color: #2F5D8A;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-guardar-custom:hover {
        background: #dcecff;
        color: #1E3A5F;
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

    /*Mensagem de sucesso*/
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

        <main class="col-md-9 col-lg-10 novo-page">

            <div class="mb-3">

                <!-- Título principal da página -->
                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-truck-medical me-2"></i>
                    Novo Fornecedor
                </h2>

                <!-- Subtítulo explicativo -->
                <p class="page-subtitle">
                    Registo de fornecedores associados aos equipamentos e serviços hospitalares.
                </p>

            </div>

            <?php if (!empty($erros)) : ?>

                <div class="mensagem-erro">
                    <strong>Foram encontrados os seguintes erros:</strong>

                    <ul class="mb-0 mt-2">
                        <?php foreach ($erros as $erro) : ?>
                            <li><?= htmlspecialchars($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>

                <div class="mensagem-sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

            <?php endif; ?>

            <div class="content-card">

                <form action="novo.php" method="post" novalidate>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome da Empresa</label>

                            <input type="text"
                                   name="nome_empresa"
                                   class="form-control"
                                   value="<?= htmlspecialchars($nome_empresa) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIF</label>

                            <input type="text"
                                   name="nif"
                                   class="form-control"
                                   value="<?= htmlspecialchars($nif) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>

                            <input type="text"
                                   name="telefone"
                                   class="form-control"
                                   value="<?= htmlspecialchars($telefone) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>

                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= htmlspecialchars($email) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Morada</label>

                            <input type="text"
                                   name="morada"
                                   class="form-control"
                                   value="<?= htmlspecialchars($morada) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Website</label>

                            <input type="text"
                                   name="website"
                                   class="form-control"
                                   value="<?= htmlspecialchars($website) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pessoa de Contacto</label>

                            <input type="text"
                                   name="pessoa_contacto"
                                   class="form-control"
                                   value="<?= htmlspecialchars($pessoa_contacto) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone da Pessoa de Contacto</label>

                            <input type="text"
                                   name="telefone_contacto"
                                   class="form-control"
                                   value="<?= htmlspecialchars($telefone_contacto) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Fornecedor</label>

                            <select name="tipo_fornecedor" class="form-control">
                                <option value="">Escolha uma opção</option>
                                <option value="Fabricante" <?= $tipo_fornecedor == 'Fabricante' ? 'selected' : '' ?>>Fabricante</option>
                                <option value="Distribuidor / Comercial" <?= $tipo_fornecedor == 'Distribuidor / Comercial' ? 'selected' : '' ?>>Distribuidor / Comercial</option>
                                <option value="Assistência Técnica" <?= $tipo_fornecedor == 'Assistência Técnica' ? 'selected' : '' ?>>Assistência Técnica</option>
                                <option value="Consumíveis / Acessórios" <?= $tipo_fornecedor == 'Consumíveis / Acessórios' ? 'selected' : '' ?>>Consumíveis / Acessórios</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Observações</label>

                            <textarea name="observacoes"
                                      rows="1"
                                      class="form-control"><?= htmlspecialchars($observacoes) ?></textarea>
                        </div>

                    </div>

                    <!-- Botões do formulário -->
                    <div class="d-flex gap-2 mt-2">

                        <a href="lista.php" class="btn btn-cancelar-custom">
                            <i class="fa-solid fa-xmark me-1"></i>
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-guardar-custom">
                            <i class="fa-regular fa-floppy-disk me-1"></i>
                            Guardar
                        </button>

                    </div>

                </form>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>