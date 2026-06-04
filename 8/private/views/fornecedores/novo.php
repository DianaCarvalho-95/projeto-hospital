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

        $nome_empresa = ucwords(strtolower($nome_empresa));
        $pessoa_contacto = ucwords(strtolower($pessoa_contacto));

        try {

            $ligacao = new PDO(
                "mysql:host=" . MYSQL_HOST .
                    ";dbname=" . MYSQL_DATABASE .
                    ";charset=utf8",
                MYSQL_USERNAME,
                MYSQL_PASSWORD
            );

            $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>
                <i class="fa-solid fa-truck-medical me-2"></i>
                Inserir Novo Fornecedor
            </h2>

            <hr>

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

            <form action="novo.php" method="post" novalidate>

                <div class="mb-3">
                    <label class="form-label">Nome da Empresa</label>
                    <input type="text" name="nome_empresa" class="form-control"
                           value="<?= htmlspecialchars($nome_empresa) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">NIF</label>
                    <input type="text" name="nif" class="form-control"
                           value="<?= htmlspecialchars($nif) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control"
                           value="<?= htmlspecialchars($telefone) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($email) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Morada</label>
                    <input type="text" name="morada" class="form-control"
                           value="<?= htmlspecialchars($morada) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control"
                           value="<?= htmlspecialchars($website) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Pessoa de Contacto</label>
                    <input type="text" name="pessoa_contacto" class="form-control"
                           value="<?= htmlspecialchars($pessoa_contacto) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Telefone da Pessoa de Contacto</label>
                    <input type="text" name="telefone_contacto" class="form-control"
                           value="<?= htmlspecialchars($telefone_contacto) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo de Fornecedor</label>
                    <select name="tipo_fornecedor" class="form-control">
                        <option value="">Escolha uma opção</option>
                        <option value="Fabricante" <?= $tipo_fornecedor == 'Fabricante' ? 'selected' : '' ?>>Fabricante</option>
                        <option value="Distribuidor / Comercial" <?= $tipo_fornecedor == 'Distribuidor / Comercial' ? 'selected' : '' ?>>Distribuidor / Comercial</option>
                        <option value="Assistência Técnica" <?= $tipo_fornecedor == 'Assistência Técnica' ? 'selected' : '' ?>>Assistência Técnica</option>
                        <option value="Consumíveis / Acessórios" <?= $tipo_fornecedor == 'Consumíveis / Acessórios' ? 'selected' : '' ?>>Consumíveis / Acessórios</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" rows="4" class="form-control"><?= htmlspecialchars($observacoes) ?></textarea>
                </div>

                <div class="mb-3">
                    <a href="lista.php" class="btn btn-secondary">
                        <i class="fa-solid fa-xmark me-1"></i>
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-success">
                        <i class="fa-regular fa-floppy-disk me-1"></i>
                        Guardar
                    </button>
                </div>

            </form>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>