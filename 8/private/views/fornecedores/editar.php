<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erros = [];
$sucesso = '';
$fornecedor = null;

if ($id <= 0) {
    $erros[] = 'Fornecedor inválido.';
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

                $sql = "UPDATE fornecedores SET
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
                        WHERE id = :id";

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
                    ':observacoes' => $observacoes,
                    ':id' => $id
                ]);

                $sucesso = 'Fornecedor atualizado com sucesso.';
            }
        }

        $stmt = $ligacao->prepare(
            "SELECT * FROM fornecedores
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $fornecedor = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$fornecedor) {
            $erros[] = 'Fornecedor não encontrado.';
        }

    } catch (PDOException $err) {

        $erros[] = 'Não foi possível atualizar o fornecedor.';
    }

    $ligacao = null;
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>
                <i class="fa-regular fa-pen-to-square me-2"></i>
                Editar Fornecedor
            </h2>

            <hr>

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
            <?php endif; ?>

            <?php if ($fornecedor) : ?>

                <form action="editar.php?id=<?= $fornecedor->id ?>" method="post" novalidate>

                    <div class="mb-3">
                        <label class="form-label">Nome da Empresa</label>
                        <input type="text" name="nome_empresa" class="form-control"
                               value="<?= htmlspecialchars($fornecedor->nome_empresa) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">NIF</label>
                        <input type="text" name="nif" class="form-control"
                               value="<?= htmlspecialchars($fornecedor->nif) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control"
                               value="<?= htmlspecialchars($fornecedor->telefone) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($fornecedor->email) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Morada</label>
                        <input type="text" name="morada" class="form-control"
                               value="<?= htmlspecialchars($fornecedor->morada) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" class="form-control"
                               value="<?= htmlspecialchars($fornecedor->website) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pessoa de Contacto</label>
                        <input type="text" name="pessoa_contacto" class="form-control"
                               value="<?= htmlspecialchars($fornecedor->pessoa_contacto) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telefone da Pessoa de Contacto</label>
                        <input type="text" name="telefone_contacto" class="form-control"
                               value="<?= htmlspecialchars($fornecedor->telefone_contacto) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de Fornecedor</label>
                        <select name="tipo_fornecedor" class="form-control">
                            <option value="">Escolha uma opção</option>
                            <option value="Fabricante" <?= $fornecedor->tipo_fornecedor == 'Fabricante' ? 'selected' : '' ?>>Fabricante</option>
                            <option value="Distribuidor / Comercial" <?= $fornecedor->tipo_fornecedor == 'Distribuidor / Comercial' ? 'selected' : '' ?>>Distribuidor / Comercial</option>
                            <option value="Assistência Técnica" <?= $fornecedor->tipo_fornecedor == 'Assistência Técnica' ? 'selected' : '' ?>>Assistência Técnica</option>
                            <option value="Consumíveis / Acessórios" <?= $fornecedor->tipo_fornecedor == 'Consumíveis / Acessórios' ? 'selected' : '' ?>>Consumíveis / Acessórios</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" rows="4" class="form-control"><?= htmlspecialchars($fornecedor->observacoes) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <a href="lista.php" class="btn btn-secondary">
                            <i class="fa-solid fa-xmark me-1"></i>
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-success">
                            <i class="fa-regular fa-floppy-disk me-1"></i>
                            Guardar alterações
                        </button>
                    </div>

                </form>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>