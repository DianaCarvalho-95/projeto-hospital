<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erros = [];
$sucesso = '';

$equipamentos = [];
$fornecedores = [];

$nome_documento = '';
$tipo_documento = '';
$equipamento_id = '';
$fornecedor_id = '';
$data_documento = '';
$data_validade = '';
$caminho_ficheiro = '';

try {

    $ligacao_dados = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao_dados->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_equipamentos = $ligacao_dados->query(
        "SELECT id, codigo_inventario, designacao
         FROM equipamentos
         ORDER BY codigo_inventario"
    );

    $equipamentos = $stmt_equipamentos->fetchAll(PDO::FETCH_OBJ);

    $stmt_fornecedores = $ligacao_dados->query(
        "SELECT id, nome_empresa
         FROM fornecedores
         ORDER BY nome_empresa"
    );

    $fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $equipamentos = [];
    $fornecedores = [];
}

$ligacao_dados = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nome_documento = isset($_POST['nome_documento']) ? trim($_POST['nome_documento']) : '';
    $tipo_documento = isset($_POST['tipo_documento']) ? trim($_POST['tipo_documento']) : '';
    $equipamento_id = isset($_POST['equipamento_id']) ? intval($_POST['equipamento_id']) : '';
    $fornecedor_id = isset($_POST['fornecedor_id']) ? intval($_POST['fornecedor_id']) : '';
    $data_documento = isset($_POST['data_documento']) ? trim($_POST['data_documento']) : '';
    $data_validade = isset($_POST['data_validade']) ? trim($_POST['data_validade']) : '';
    $caminho_ficheiro = isset($_POST['caminho_ficheiro']) ? trim($_POST['caminho_ficheiro']) : '';

    if (empty($nome_documento)) {
        $erros[] = 'O nome do documento é obrigatório.';
    }

    if (empty($tipo_documento)) {
        $erros[] = 'O tipo de documento é obrigatório.';
    }

    if (empty($equipamento_id)) {
        $erros[] = 'O equipamento associado é obrigatório.';
    }

    if (!empty($data_documento)) {
        $partes_data = explode('-', $data_documento);

        if (
            count($partes_data) != 3 ||
            !checkdate(
                (int)$partes_data[1],
                (int)$partes_data[2],
                (int)$partes_data[0]
            )
        ) {
            $erros[] = 'A data do documento não é válida.';
        }
    }

    if (!empty($data_validade)) {
        $partes_data = explode('-', $data_validade);

        if (
            count($partes_data) != 3 ||
            !checkdate(
                (int)$partes_data[1],
                (int)$partes_data[2],
                (int)$partes_data[0]
            )
        ) {
            $erros[] = 'A data de validade não é válida.';
        }
    }

    if (empty($erros)) {

        $nome_documento = ucwords(strtolower($nome_documento));

        try {

            $ligacao = new PDO(
                "mysql:host=" . MYSQL_HOST .
                    ";dbname=" . MYSQL_DATABASE .
                    ";charset=utf8",
                MYSQL_USERNAME,
                MYSQL_PASSWORD
            );

            $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO documentacao
                    (tipo_documento, nome_documento, data_documento,
                     data_validade, caminho_ficheiro, equipamento_id, fornecedor_id)
                    VALUES
                    (:tipo_documento, :nome_documento, :data_documento,
                     :data_validade, :caminho_ficheiro, :equipamento_id, :fornecedor_id)";

            $stmt = $ligacao->prepare($sql);

            $stmt->execute([
                ':tipo_documento' => $tipo_documento,
                ':nome_documento' => $nome_documento,
                ':data_documento' => !empty($data_documento) ? $data_documento : null,
                ':data_validade' => !empty($data_validade) ? $data_validade : null,
                ':caminho_ficheiro' => $caminho_ficheiro,
                ':equipamento_id' => $equipamento_id,
                ':fornecedor_id' => !empty($fornecedor_id) ? $fornecedor_id : null
            ]);

            $sucesso = 'Documento inserido com sucesso.';

            $nome_documento = '';
            $tipo_documento = '';
            $equipamento_id = '';
            $fornecedor_id = '';
            $data_documento = '';
            $data_validade = '';
            $caminho_ficheiro = '';

        } catch (PDOException $err) {

            $erros[] = 'Não foi possível inserir o documento.';
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
                <i class="fa-solid fa-file-medical me-2"></i>
                Inserir Novo Documento
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
                    <label class="form-label">Nome do Documento</label>
                    <input type="text" name="nome_documento" class="form-control"
                           value="<?= htmlspecialchars($nome_documento) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo de Documento</label>
                    <select name="tipo_documento" class="form-control">
                        <option value="">Escolha uma opção</option>
                        <option value="Manual" <?= $tipo_documento == 'Manual' ? 'selected' : '' ?>>Manual</option>
                        <option value="Certificado" <?= $tipo_documento == 'Certificado' ? 'selected' : '' ?>>Certificado</option>
                        <option value="Contrato" <?= $tipo_documento == 'Contrato' ? 'selected' : '' ?>>Contrato</option>
                        <option value="Relatório" <?= $tipo_documento == 'Relatório' ? 'selected' : '' ?>>Relatório</option>
                        <option value="Ficha Técnica" <?= $tipo_documento == 'Ficha Técnica' ? 'selected' : '' ?>>Ficha Técnica</option>
                        <option value="Outro" <?= $tipo_documento == 'Outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Equipamento Associado</label>
                    <select name="equipamento_id" class="form-control">
                        <option value="">Escolha um equipamento</option>

                        <?php foreach ($equipamentos as $equipamento) : ?>
                            <option value="<?= $equipamento->id ?>" <?= $equipamento_id == $equipamento->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars(
                                    $equipamento->codigo_inventario .
                                    ' - ' .
                                    $equipamento->designacao
                                ) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Fornecedor Associado</label>
                    <select name="fornecedor_id" class="form-control">
                        <option value="">Sem fornecedor associado</option>

                        <?php foreach ($fornecedores as $fornecedor) : ?>
                            <option value="<?= $fornecedor->id ?>" <?= $fornecedor_id == $fornecedor->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fornecedor->nome_empresa) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Data do Documento</label>
                    <input type="date" name="data_documento" class="form-control"
                           value="<?= htmlspecialchars($data_documento) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Data de Validade</label>
                    <input type="date" name="data_validade" class="form-control"
                           value="<?= htmlspecialchars($data_validade) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nome / Caminho do Ficheiro</label>
                    <input type="text" name="caminho_ficheiro" class="form-control"
                           value="<?= htmlspecialchars($caminho_ficheiro) ?>"
                           placeholder="Ex.: docs/manual_eq001.pdf">
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