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

$tipos_fornecedor = ['Fabricante', 'Distribuidor', 'Assistência técnica', 'Distribuidor / Comercial', 'Consumíveis / Acessórios'];

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
        try {
            $ligacao = new PDO(
                "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
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

            $novo_fornecedor_id = (int) $ligacao->lastInsertId();
            registar_evento('Fornecedores', 'Criação', 'Fornecedor', $novo_fornecedor_id, $nome_empresa);

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

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function selecionado($valor_atual, $valor_opcao)
{
    return $valor_atual == $valor_opcao ? 'selected' : '';
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 novo-page">
            <div class="mb-3">
                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-truck-medical me-2"></i>
                    Novo fornecedor
                </h2>
                <p class="page-subtitle">Registo de fornecedores associados aos equipamentos e serviços hospitalares.</p>
            </div>

            <?php if (!empty($erros)) : ?>
                <div class="mensagem-erro">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= h($erro) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>
                <div class="mensagem-sucesso"><?= h($sucesso) ?></div>
            <?php endif; ?>

            <div class="supplier-strip">
                <div>
                    <div class="supplier-name">Dados do novo fornecedor</div>
                    <div class="supplier-path">Preencha os campos principais para identificar e contactar a entidade.</div>
                </div>
                <span class="supplier-badge">Novo registo</span>
            </div>

            <div class="content-card">
                <form action="novo.php" method="post" novalidate>
                    <div class="form-hint">Os campos obrigatórios ajudam a manter a listagem organizada e a garantir contacto rápido com o fornecedor.</div>

                    <div class="row g-3">
                        <div class="col-lg-8">
                            <h5 class="form-section-title">Dados da empresa</h5>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Nome da empresa</label>
                                    <input type="text" name="nome_empresa" class="form-control" value="<?= h($nome_empresa) ?>" placeholder="Indique o nome da empresa">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">NIF</label>
                                    <input type="text" name="nif" class="form-control" value="<?= h($nif) ?>" placeholder="Indique o NIF">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Morada</label>
                                    <input type="text" name="morada" class="form-control" value="<?= h($morada) ?>" placeholder="Indique a morada">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipo de fornecedor</label>
                                    <select name="tipo_fornecedor" class="form-select">
                                        <option value="">Escolha o tipo</option>
                                        <?php foreach ($tipos_fornecedor as $opcao) : ?>
                                            <option value="<?= h($opcao) ?>" <?= selecionado($tipo_fornecedor, $opcao) ?>><?= h($opcao) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <h5 class="form-section-title">Informação adicional</h5>
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" rows="5" class="form-control" placeholder="Opcional"><?= h($observacoes) ?></textarea>
                        </div>

                        <div class="col-12">
                            <h5 class="form-section-title">Contactos</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Telefone</label>
                                    <input type="text" name="telefone" class="form-control" value="<?= h($telefone) ?>" placeholder="Indique o telefone">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= h($email) ?>" placeholder="Indique o email">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Pessoa de contacto</label>
                                    <input type="text" name="pessoa_contacto" class="form-control" value="<?= h($pessoa_contacto) ?>" placeholder="Indique o contacto">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Telefone do contacto</label>
                                    <input type="text" name="telefone_contacto" class="form-control" value="<?= h($telefone_contacto) ?>" placeholder="Indique o telefone">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Website</label>
                                    <input type="text" name="website" class="form-control" value="<?= h($website) ?>" placeholder="Indique o website">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="lista.php" class="btn btn-cancelar-custom">
                            <i class="fa-solid fa-xmark me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-guardar-custom">
                            <i class="fa-regular fa-floppy-disk me-1"></i>
                            Guardar fornecedor
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

