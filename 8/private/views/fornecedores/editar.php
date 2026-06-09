<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$erros = [];
$sucesso = '';
$fornecedor = null;

$tipos_fornecedor = ['Fabricante', 'Distribuidor', 'Distribuidor / Comercial', 'Assistência técnica', 'Assistência Técnica', 'Consumíveis / Acessórios'];

if ($id <= 0) {
    $erros[] = 'Fornecedor inválido.';
} else {
    try {
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );
        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erros[] = 'O email introduzido não é válido.';
            }

            if (empty($erros)) {
                $stmt = $ligacao->prepare(
                    "UPDATE fornecedores SET
                        nome_empresa = :nome_empresa,
                        nif = :nif,
                        telefone = :telefone,
                        email = :email,
                        morada = :morada,
                        website = :website,
                        pessoa_contacto = :pessoa_contacto,
                        telefone_contacto = :telefone_contacto,
                        tipo_fornecedor = :tipo_fornecedor,
                        observacoes = :observacoes
                     WHERE id = :id"
                );

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
                    ':observacoes' => $observacoes,
                    ':id' => $id
                ]);

                $sucesso = 'Dados do fornecedor atualizados com sucesso.';
            }
        }

        $stmt = $ligacao->prepare("SELECT * FROM fornecedores WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $fornecedor = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$fornecedor) {
            $erros[] = 'Fornecedor não encontrado.';
        }
    } catch (PDOException $err) {
        $erros[] = 'Não foi possível atualizar o fornecedor.';
    }

    $ligacao = null;
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

<style>
    .editar-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    .page-title {
        font-weight: 700;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .supplier-strip,
    .content-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
    }

    .supplier-strip {
        padding: 12px 14px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .supplier-name {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        margin-bottom: 2px;
    }

    .supplier-path {
        color: #52677d;
        font-size: 0.84rem;
    }

    .supplier-badge {
        background: #e8f1fb;
        color: #1E3A5F;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 0.76rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .content-card {
        padding: 16px;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #1E3A5F;
        font-weight: 800;
        font-size: 0.98rem;
        margin-bottom: 12px;
        padding-bottom: 9px;
        border-bottom: 1px solid #e8eef5;
    }

    .form-section-title::before {
        content: "";
        width: 4px;
        height: 18px;
        border-radius: 999px;
        background: #2F5D8A;
    }

    .form-hint {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        color: #52677d;
        font-size: 0.84rem;
        padding: 9px 11px;
        margin-bottom: 14px;
    }

    .form-label {
        font-weight: 800;
        color: #172033;
        font-size: 0.84rem;
        margin-bottom: 5px;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #dbe3ec;
        font-size: 0.88rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F5D8A;
        box-shadow: 0 0 0 0.15rem rgba(47, 93, 138, 0.18);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 76px;
    }

    .btn-cancelar-custom,
    .btn-guardar-custom {
        border-radius: 8px;
        font-weight: 700;
        padding: 8px 14px;
    }

    .btn-cancelar-custom {
        background: #fff;
        border: 1px solid #dbe3ec;
        color: #1E3A5F;
    }

    .btn-cancelar-custom:hover {
        background: #f6faff;
        color: #1E3A5F;
    }

    .btn-guardar-custom {
        background: #2F5D8A;
        border: 1px solid #2F5D8A;
        color: #ffffff;
    }

    .btn-guardar-custom:hover {
        background: #1E3A5F;
        color: #ffffff;
    }

    .mensagem-erro,
    .mensagem-sucesso {
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 14px;
    }

    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
    }

    .mensagem-sucesso {
        background: #e8f5ee;
        color: #198754;
        border: 1px solid #cfead9;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 editar-page">
            <div class="mb-3">
                <h2 class="page-title mb-1">
                    <i class="fa-regular fa-pen-to-square me-2"></i>
                    Editar dados do fornecedor
                </h2>
                <p class="page-subtitle">Atualização dos dados administrativos, comerciais e de contacto do fornecedor.</p>
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

            <?php if ($fornecedor) : ?>
                <div class="supplier-strip">
                    <div>
                        <div class="supplier-name"><?= h($fornecedor->nome_empresa) ?></div>
                        <div class="supplier-path"><?= h($fornecedor->tipo_fornecedor ?: 'Tipo não definido') ?> · <?= h($fornecedor->nif) ?></div>
                    </div>
                    <span class="supplier-badge">Fornecedor atual</span>
                </div>

                <div class="content-card">
                    <form action="editar.php?id=<?= $fornecedor->id ?>" method="post" novalidate>
                        <div class="form-hint">Os valores atuais já estão preenchidos. Altere apenas os campos necessários.</div>

                        <div class="row g-3">
                            <div class="col-lg-8">
                                <h5 class="form-section-title">Dados da empresa</h5>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Nome da empresa</label>
                                        <input type="text" name="nome_empresa" class="form-control" value="<?= h($fornecedor->nome_empresa) ?>" placeholder="Indique o nome da empresa">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">NIF</label>
                                        <input type="text" name="nif" class="form-control" value="<?= h($fornecedor->nif) ?>" placeholder="Indique o NIF">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Morada</label>
                                        <input type="text" name="morada" class="form-control" value="<?= h($fornecedor->morada) ?>" placeholder="Indique a morada">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de fornecedor</label>
                                        <select name="tipo_fornecedor" class="form-select">
                                            <option value="">Escolha o tipo</option>
                                            <?php foreach (array_unique($tipos_fornecedor) as $opcao) : ?>
                                                <option value="<?= h($opcao) ?>" <?= selecionado($fornecedor->tipo_fornecedor, $opcao) ?>><?= h($opcao) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <h5 class="form-section-title">Informação adicional</h5>
                                <label class="form-label">Observações</label>
                                <textarea name="observacoes" rows="5" class="form-control" placeholder="Opcional"><?= h($fornecedor->observacoes) ?></textarea>
                            </div>

                            <div class="col-12">
                                <h5 class="form-section-title">Contactos</h5>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Telefone</label>
                                        <input type="text" name="telefone" class="form-control" value="<?= h($fornecedor->telefone) ?>" placeholder="Indique o telefone">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= h($fornecedor->email) ?>" placeholder="Indique o email">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Pessoa de contacto</label>
                                        <input type="text" name="pessoa_contacto" class="form-control" value="<?= h($fornecedor->pessoa_contacto) ?>" placeholder="Indique o contacto">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Telefone do contacto</label>
                                        <input type="text" name="telefone_contacto" class="form-control" value="<?= h($fornecedor->telefone_contacto) ?>" placeholder="Indique o telefone">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Website</label>
                                        <input type="text" name="website" class="form-control" value="<?= h($fornecedor->website) ?>" placeholder="Indique o website">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <a href="detalhes.php?id=<?= $fornecedor->id ?>" class="btn btn-cancelar-custom">
                                <i class="fa-solid fa-xmark me-1"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-guardar-custom">
                                <i class="fa-regular fa-floppy-disk me-1"></i>
                                Guardar dados do fornecedor
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
