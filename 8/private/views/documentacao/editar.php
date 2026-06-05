<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erros = [];
$sucesso = '';
$documento = null;

$equipamentos = [];
$fornecedores = [];

if ($id <= 0) {

    $erros[] = 'Documento inválido.';
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

        /* Carrega equipamentos */
        $stmt_equipamentos = $ligacao->query(
            "SELECT id, codigo_inventario, designacao
             FROM equipamentos
             ORDER BY codigo_inventario"
        );

        $equipamentos = $stmt_equipamentos->fetchAll(PDO::FETCH_OBJ);

        /* Carrega fornecedores */
        $stmt_fornecedores = $ligacao->query(
            "SELECT id, nome_empresa
             FROM fornecedores
             ORDER BY nome_empresa"
        );

        $fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_OBJ);

        /* Carrega o documento atual */
        $stmt = $ligacao->prepare(
            "SELECT *
             FROM documentacao
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $documento = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$documento) {
            $erros[] = 'Documento não encontrado.';
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $documento) {

            /* Recolha dos dados */
            $nome_documento = isset($_POST['nome_documento']) ? trim($_POST['nome_documento']) : '';
            $tipo_documento = isset($_POST['tipo_documento']) ? trim($_POST['tipo_documento']) : '';
            $equipamento_id = isset($_POST['equipamento_id']) ? intval($_POST['equipamento_id']) : '';
            $fornecedor_id = isset($_POST['fornecedor_id']) ? intval($_POST['fornecedor_id']) : '';
            $data_documento = isset($_POST['data_documento']) ? trim($_POST['data_documento']) : '';
            $data_validade = isset($_POST['data_validade']) ? trim($_POST['data_validade']) : '';

            /* Validações */
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

                /*
                    Atualiza apenas os dados informativos do documento.
                    O campo caminho_ficheiro deixa de ser usado.
                */
                $sql = "UPDATE documentacao SET
                            tipo_documento = :tipo_documento,
                            nome_documento = :nome_documento,
                            data_documento = :data_documento,
                            data_validade = :data_validade,
                            equipamento_id = :equipamento_id,
                            fornecedor_id = :fornecedor_id
                        WHERE id = :id";

                $stmt = $ligacao->prepare($sql);

                $stmt->execute([
                    ':tipo_documento' => $tipo_documento,
                    ':nome_documento' => $nome_documento,
                    ':data_documento' => !empty($data_documento) ? $data_documento : null,
                    ':data_validade' => !empty($data_validade) ? $data_validade : null,
                    ':equipamento_id' => $equipamento_id,
                    ':fornecedor_id' => !empty($fornecedor_id) ? $fornecedor_id : null,
                    ':id' => $id
                ]);

                $sucesso = 'Documento atualizado com sucesso.';

                /* Recarrega os dados atualizados */
                $stmt = $ligacao->prepare(
                    "SELECT *
                     FROM documentacao
                     WHERE id = :id"
                );

                $stmt->execute([
                    ':id' => $id
                ]);

                $documento = $stmt->fetch(PDO::FETCH_OBJ);
            }
        }
    } catch (PDOException $err) {

        $erros[] = 'Não foi possível atualizar o documento.';
    }

    $ligacao = null;
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

    .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.88rem;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #dbe3ec;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: #2F5D8A;
        box-shadow: 0 0 0 0.15rem rgba(47, 93, 138, 0.18);
    }

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

        <main class="col-md-9 col-lg-10 editar-page">

            <div class="mb-3">

                <h2 class="page-title mb-1">
                    <i class="fa-regular fa-pen-to-square me-2"></i>
                    Editar Documento
                </h2>

                <p class="page-subtitle">
                    Atualização dos dados da documentação técnica associada aos equipamentos médicos.
                </p>

            </div>

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

            <?php if ($documento) : ?>

                <div class="content-card">

                    <form action="editar.php?id=<?= $documento->id ?>"
                        method="post"
                        novalidate>

                        <div class="row">

                            <!-- Nome do Documento -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome do Documento</label>
                                <input type="text"
                                    name="nome_documento"
                                    class="form-control"
                                    value="<?= htmlspecialchars($documento->nome_documento) ?>">
                            </div>

                            <!-- Tipo do Documento -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Documento</label>

                                <select name="tipo_documento" class="form-control">
                                    <option value="">Escolha uma opção</option>
                                    <option value="Manual" <?= $documento->tipo_documento == 'Manual' ? 'selected' : '' ?>>Manual</option>
                                    <option value="Certificado" <?= $documento->tipo_documento == 'Certificado' ? 'selected' : '' ?>>Certificado</option>
                                    <option value="Contrato" <?= $documento->tipo_documento == 'Contrato' ? 'selected' : '' ?>>Contrato</option>
                                    <option value="Relatório" <?= $documento->tipo_documento == 'Relatório' ? 'selected' : '' ?>>Relatório</option>
                                    <option value="Ficha Técnica" <?= $documento->tipo_documento == 'Ficha Técnica' ? 'selected' : '' ?>>Ficha Técnica</option>
                                    <option value="Outro" <?= $documento->tipo_documento == 'Outro' ? 'selected' : '' ?>>Outro</option>
                                </select>
                            </div>

                            <!-- Equipamento Associado -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Equipamento Associado</label>

                                <select name="equipamento_id" class="form-control">
                                    <option value="">Escolha um equipamento</option>

                                    <?php foreach ($equipamentos as $equipamento) : ?>
                                        <option value="<?= $equipamento->id ?>" <?= $documento->equipamento_id == $equipamento->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(
                                                $equipamento->codigo_inventario .
                                                    ' - ' .
                                                    $equipamento->designacao
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <!-- Fornecedor Associado -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fornecedor Associado</label>

                                <select name="fornecedor_id" class="form-control">
                                    <option value="">Sem fornecedor associado</option>

                                    <?php foreach ($fornecedores as $fornecedor) : ?>
                                        <option value="<?= $fornecedor->id ?>" <?= $documento->fornecedor_id == $fornecedor->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fornecedor->nome_empresa) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <!-- Data do Documento -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data do Documento</label>
                                <input type="date"
                                    name="data_documento"
                                    class="form-control"
                                    value="<?= htmlspecialchars($documento->data_documento) ?>">
                            </div>

                            <!-- Data de Validade -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Validade</label>
                                <input type="date"
                                    name="data_validade"
                                    class="form-control"
                                    value="<?= htmlspecialchars($documento->data_validade) ?>">
                            </div>

                        </div>

                        <div class="d-flex gap-2 mt-2">

                            <a href="lista.php" class="btn btn-cancelar-custom">
                                <i class="fa-solid fa-xmark me-1"></i>
                                Cancelar
                            </a>

                            <button type="submit" class="btn btn-guardar-custom">
                                <i class="fa-regular fa-floppy-disk me-1"></i>
                                Guardar alterações
                            </button>

                        </div>

                    </form>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>